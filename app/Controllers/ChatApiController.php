<?php

namespace App\Controllers;

use App\Core\ApiFormat;
use App\Core\BaseApiController;
use App\Core\ChatRules;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Venue;

/**
 * Chat do app (D66): a pessoa conversa com o responsável pela quadra, que responde pelo painel
 * do site. Todas as rotas exigem token. Contrato em docs/api.md, seção "Chat".
 *
 * Pela API, a pessoa só acessa as conversas em que é quem pergunta: uma conversa de outra
 * pessoa, ou de uma quadra dela mesma (o dono responde pelo site), dá 404 NOT_FOUND.
 */
class ChatApiController extends BaseApiController
{
    /** Textos dos erros do campo body (ChatRules::cleanBody). */
    private const BODY_ERRORS = [
        'required' => 'Escreva uma mensagem.',
        'too_long' => 'A mensagem pode ter até ' . ChatRules::BODY_MAX . ' caracteres.',
        'link' => 'Mensagens não podem ter links.',
    ];

    /** GET /api/v1/conversations */
    public function index()
    {
        $this->authenticateRequest();
        $list = array_map(
            fn($c) => $this->format($c, $c['venue_cover'] ?? null, $c['last_message'], $c['unread_count']),
            Conversation::forUser($this->userId)
        );
        $this->sendSuccess($list);
    }

    /** POST /api/v1/venues/{id}/conversations */
    public function open($id)
    {
        $this->authenticateRequest();
        $venue = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$venue) {
            $this->sendError('Quadra não encontrada.', 'VENUE_NOT_FOUND', 404);
        }
        if ((int)$venue['user_id'] === $this->userId) {
            $this->sendError('Você é o responsável por esta quadra: responda as mensagens pelo painel do site.', 'CHAT_OWN_VENUE', 422);
        }
        [$conversation, $created] = Conversation::findOrCreate((int)$venue['id'], $this->userId);
        $last = Message::list((int)$conversation['id'], null, null, 1)[0] ?? null;
        $unread = $last ? Conversation::unreadCount($conversation, $this->userId, Conversation::SIDE_USER) : 0;
        $this->sendSuccess($this->format($conversation, $venue['image_path'] ?? null, $last, $unread), $created ? 201 : 200);
    }

    /** GET /api/v1/conversations/{id}/messages?after_id=|before_id=&per_page= */
    public function messages($id)
    {
        $this->authenticateRequest();
        $conversation = $this->findMine($id);
        $params = ChatRules::listParams($_GET);
        $this->failIfInvalid($params['errors']);
        $list = Message::list((int)$conversation['id'], $params['after'], $params['before'], $params['per_page']);
        $this->sendSuccess(array_map(fn($m) => $this->formatMessage($m), $list));
    }

    /** POST /api/v1/conversations/{id}/messages  { "body": "..." } */
    public function send($id)
    {
        $this->authenticateRequest();
        $conversation = $this->findMine($id);
        $input = $this->getJsonInput();
        if (!empty($conversation['blocked_at'])) {
            $this->sendError('O responsável pela quadra bloqueou esta conversa.', 'CHAT_BLOCKED', 403);
        }
        if (!Conversation::venueAvailable($conversation)) {
            $this->sendError('A quadra foi desativada: a conversa fica só para leitura.', 'VENUE_NOT_FOUND', 404);
        }
        [$body, $error] = ChatRules::cleanBody($input['body'] ?? null);
        if ($error !== null) {
            $this->failIfInvalid(['body' => self::BODY_ERRORS[$error]]);
        }
        $wait = Message::waitSeconds($this->userId);
        if ($wait !== null) {
            header('Retry-After: ' . $wait);
            $this->sendError('Muitas mensagens em pouco tempo. Espere um pouco e tente de novo.', 'TOO_MANY_ATTEMPTS', 429);
        }
        $message = Message::create($conversation, $this->userId, Conversation::SIDE_USER, $body);
        $this->sendSuccess($this->formatMessage($message), 201);
    }

    /** POST /api/v1/conversations/{id}/read  { "last_message_id": 341 } */
    public function read($id)
    {
        $this->authenticateRequest();
        $conversation = $this->findMine($id);
        $input = $this->getJsonInput();
        $lastId = $input['last_message_id'] ?? null;
        // Inteiro positivo (número no JSON ou texto só com dígitos)
        $valid = (is_int($lastId) || (is_string($lastId) && ctype_digit($lastId))) && (int)$lastId >= 1;
        if (!$valid) {
            $this->failIfInvalid(['last_message_id' => 'Use o id (número inteiro positivo) da última mensagem lida.']);
        }
        Conversation::markRead($conversation, Conversation::SIDE_USER, (int)$lastId);
        http_response_code(204);
        exit;
    }

    // --- Ajudantes ---

    /** A conversa, se a pessoa logada for quem pergunta; senão 404 (não revela que existe). */
    private function findMine($id): array
    {
        $conversation = ctype_digit((string)$id) ? Conversation::findById((int)$id) : null;
        if (!$conversation || Conversation::side($conversation, $this->userId) !== Conversation::SIDE_USER) {
            $this->sendError('Conversa não encontrada.', 'NOT_FOUND', 404);
        }
        return $conversation;
    }

    /** Objeto "conversa" do contrato. */
    private function format(array $c, ?string $coverFile, ?array $last, int $unread): array
    {
        return [
            'id' => (int)$c['id'],
            'venue' => [
                'id' => (int)$c['venue_id'],
                'name' => $c['venue_name'],
                'cover_image_url' => ApiFormat::uploadUrl('venues/' . (int)$c['venue_id'], $coverFile),
            ],
            'last_message' => $last ? $this->formatMessage($last) : null,
            'unread_count' => $unread,
            'blocked' => !empty($c['blocked_at']),
        ];
    }

    /** Objeto "mensagem" do contrato. */
    private function formatMessage(array $m): array
    {
        return [
            'id' => (int)$m['id'],
            'body' => $m['body'],
            'mine' => (int)$m['sender_id'] === $this->userId,
            'created_at' => ApiFormat::datetime($m['created_at']),
        ];
    }
}
