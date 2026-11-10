<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\ChatRules;
use App\Core\ViewHelper;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Venue;

/**
 * "Mensagens" no painel do dono (D66, parte D): as conversas das quadras dele, responder,
 * bloquear e desbloquear. Quem pergunta usa o app (ChatApiController). O dono vê o nome e a foto
 * de quem pergunta, nunca o e-mail. Quadra desativada: a conversa fica só para leitura.
 *
 * A tela da conversa consulta /novas a cada 10 s e envia pelo fetch (JSON); sem JavaScript, o
 * formulário funciona com redirecionamento normal.
 */
class ChatController
{
    /** Mensagens que a tela da conversa mostra ao abrir (as mais recentes). */
    private const SHOW_LAST = 50;

    /** GET /dashboard/mensagens (?quadra={id} filtra uma quadra) */
    public function index()
    {
        $ownerId = $this->ownerId();
        // Quadras do filtro: as excluídas ficam de fora (as conversas delas também, em forOwner)
        $venues = array_values(array_filter(Venue::findByUserId($ownerId) ?: [], fn($v) => empty($v['deleted_at'])));
        $venueId = ctype_digit((string)($_GET['quadra'] ?? '')) ? (int)$_GET['quadra'] : null;
        if ($venueId !== null && !in_array($venueId, array_map(fn($v) => (int)$v['id'], $venues), true)) {
            $venueId = null; // quadra de outra pessoa: mostra todas
        }
        ViewHelper::render('chat/index', [
            'conversations' => Conversation::forOwner($ownerId, $venueId),
            'venues' => $venues,
            'venueId' => $venueId,
        ]);
    }

    /** GET /dashboard/mensagens/{id}: abre a conversa e marca como lida */
    public function show($id)
    {
        $ownerId = $this->ownerId();
        $conversation = $this->findMine($id, $ownerId);
        $messages = Message::list((int)$conversation['id'], null, null, self::SHOW_LAST);
        if ($messages) {
            Conversation::markRead($conversation, Conversation::SIDE_OWNER, (int)end($messages)['id']);
        }
        $person = User::findById((int)$conversation['user_id']);
        ViewHelper::render('chat/show', [
            'conversation' => $conversation,
            'messages' => $messages,
            'person' => ['id' => (int)$person['id'], 'name' => $person['name'], 'avatar_path' => $person['avatar_path'] ?? null],
            'ownerId' => $ownerId,
            'readOnly' => !Conversation::venueAvailable($conversation),
        ]);
    }

    /** POST /dashboard/mensagens/{id}: responder (JSON quando vem do fetch) */
    public function reply($id)
    {
        $ownerId = $this->ownerId();
        $conversation = $this->findMine($id, $ownerId);
        if (!Conversation::venueAvailable($conversation)) {
            $this->fail($conversation, 'chat_readonly', 409);
        }
        [$body, $error] = ChatRules::cleanBody($_POST['body'] ?? null);
        if ($error !== null) {
            $this->fail($conversation, 'chat_' . $error, 422);
        }
        $wait = Message::waitSeconds($ownerId);
        if ($wait !== null) {
            header('Retry-After: ' . $wait);
            $this->fail($conversation, 'chat_rate', 429);
        }
        $message = Message::create($conversation, $ownerId, Conversation::SIDE_OWNER, $body);
        if ($this->wantsJson()) {
            $this->json(['message' => self::formatMessage($message, $ownerId)], 201);
        }
        $this->redirect('/dashboard/mensagens/' . (int)$conversation['id'] . '#fim');
    }

    /** GET /dashboard/mensagens/{id}/novas?after_id=: mensagens novas (consulta a cada 10 s) */
    public function poll($id)
    {
        $ownerId = $this->ownerId(true);
        $conversation = $this->findMine($id, $ownerId, true);
        $after = ctype_digit((string)($_GET['after_id'] ?? '')) ? (int)$_GET['after_id'] : 0;
        $messages = Message::list((int)$conversation['id'], $after, null, ChatRules::PER_PAGE_MAX);
        if ($messages) {
            Conversation::markRead($conversation, Conversation::SIDE_OWNER, (int)end($messages)['id']);
        }
        $this->json([
            'messages' => array_map(fn($m) => self::formatMessage($m, $ownerId), $messages),
            'blocked' => !empty($conversation['blocked_at']),
            'read_only' => !Conversation::venueAvailable($conversation),
        ]);
    }

    /** POST /dashboard/mensagens/{id}/bloquear */
    public function block($id)
    {
        $conversation = $this->findMine($id, $this->ownerId());
        Conversation::setBlocked((int)$conversation['id'], true);
        $this->redirect('/dashboard/mensagens/' . (int)$conversation['id'] . '?status=chat_blocked');
    }

    /** POST /dashboard/mensagens/{id}/desbloquear */
    public function unblock($id)
    {
        $conversation = $this->findMine($id, $this->ownerId());
        Conversation::setBlocked((int)$conversation['id'], false);
        $this->redirect('/dashboard/mensagens/' . (int)$conversation['id'] . '?status=chat_unblocked');
    }

    /** Mensagem para a tela: texto, de quem é e a hora (o JS monta o balão). */
    public static function formatMessage(array $m, int $ownerId): array
    {
        return [
            'id' => (int)$m['id'],
            'body' => $m['body'],
            'mine' => (int)$m['sender_id'] === $ownerId,
            'time' => date('H:i', strtotime($m['created_at'])),
            'day' => date('Y-m-d', strtotime($m['created_at'])),
        ];
    }

    // --- Ajudantes ---

    /** Só o dono usa as Mensagens (o admin não lê conversas de ninguém). */
    private function ownerId(bool $json = false): int
    {
        AuthHelper::check();
        if (($_SESSION['user_role'] ?? '') !== 'owner') {
            if ($json) {
                $this->json(['error' => 'not_found'], 404);
            }
            $this->redirect(AuthHelper::isAdmin() ? '/admin' : '/dashboard');
        }
        return (int)$_SESSION['user_id'];
    }

    /** Conversa de uma quadra do dono (e de conta ativa); senão volta à lista com o aviso. */
    private function findMine($id, int $ownerId, bool $json = false): array
    {
        $conversation = ctype_digit((string)$id) ? Conversation::findById((int)$id) : null;
        if (!$conversation || Conversation::side($conversation, $ownerId) !== Conversation::SIDE_OWNER
            || !empty($conversation['venue_deleted_at'])) {
            if ($json || $this->wantsJson()) {
                $this->json(['error' => 'not_found'], 404);
            }
            $this->redirect('/dashboard/mensagens?error=chat_not_found');
        }
        return $conversation;
    }

    /** Erro ao responder: JSON (com o texto) para o fetch, ou volta à conversa com o aviso. */
    private function fail(array $conversation, string $code, int $httpStatus): void
    {
        if ($this->wantsJson()) {
            $lang = require BASE_PATH . '/includes/i18n.php';
            $this->json(['error' => $code, 'message' => $lang['chat_err_' . substr($code, 5)] ?? $lang['chat_err_generic']], $httpStatus);
        }
        $this->redirect('/dashboard/mensagens/' . (int)$conversation['id'] . '?error=' . $code);
    }

    private function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function json(array $data, int $httpStatus = 200): void
    {
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}
