<?php

namespace App\Controllers;

use App\Core\ApiFormat;
use App\Core\BaseApiController;
use App\Core\ImageHelper;
use App\Core\Mailer;
use App\Core\MatchRequests;
use App\Core\NotificationText;
use App\Core\VenueSchedule;
use App\Models\Game;
use App\Models\MatchRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueHour;
use App\Models\VenueSport;

/**
 * Pedidos de horário e notificações do app (D64). Todas as rotas exigem token. O dono aprova ou
 * recusa no painel do site (RequestController). Contrato em docs/api.md.
 */
class RequestApiController extends BaseApiController
{
    private const PER_PAGE_DEFAULT = 20;
    private const PER_PAGE_MAX = 100;

    /** POST /api/v1/venues/{id}/requests */
    public function store($id)
    {
        $this->authenticateRequest();
        $venue = ctype_digit((string)$id) ? Venue::findForApi((int)$id) : false;
        if (!$venue) {
            $this->sendError('Quadra não encontrada.', 'NOT_FOUND', 404);
        }
        if ((int)$venue['user_id'] === $this->userId) {
            $this->sendError('Você é o responsável por esta quadra: marque o horário pela agenda do painel.', 'OWN_VENUE', 403);
        }
        if (empty($this->user['email_verified_at'])) {
            $this->sendError('Confirme seu e-mail para pedir horário.', 'EMAIL_NOT_VERIFIED', 403);
        }
        $input = $this->getJsonInput();

        // As regras precisam do dia pedido (horário daquele dia e partidas já marcadas)
        $venueId = (int)$venue['id'];
        $start = MatchRequests::parseDateTime($input['start_time'] ?? null);
        $sameDay = $start ? (Game::findScheduledOn([$venueId], $start->format('Y-m-d'))[$venueId] ?? []) : [];
        $sports = array_map(fn($s) => (int)$s['id'], VenueSport::findByVenueIds([$venueId])[$venueId] ?? []);
        $result = MatchRequests::validate($input, VenueHour::findByVenueId($venueId), $sameDay, $sports, VenueSchedule::now());
        if ($result['errors']) {
            $messages = [];
            foreach ($result['errors'] as $field => $code) {
                $messages[$field] = MatchRequests::message($field, $code);
            }
            $this->failIfInvalid($messages);
        }

        MatchRequest::expireDue(); // pedido vencido não conta no limite
        $counts = MatchRequest::pendingCounts($this->userId, $venueId);
        $limit = MatchRequests::limitReached($counts['user'], $counts['venue']);
        if ($limit !== null) {
            $this->sendError($limit === 'venue'
                ? 'Você já tem um pedido esperando resposta nesta quadra.'
                : 'Você já tem ' . MatchRequests::MAX_PENDING_PER_USER . ' pedidos esperando resposta. Espere uma resposta ou cancele um deles.', 'REQUEST_LIMIT', 409);
        }

        $request = MatchRequest::create($venue, $this->user, $result['values']);
        // E-mail ao dono (o aviso no sino já foi criado); falhar não desfaz o pedido
        $owner = User::findById((int)$venue['user_id']);
        if ($owner && $owner['status'] === 'active' && !empty($owner['email'])) {
            Mailer::sendRequestNew($owner['email'], (string)$owner['name'], $request);
        }
        $this->sendSuccess(self::format($request), 201);
    }

    /** GET /api/v1/me/requests?page=&per_page=&status= */
    public function index()
    {
        $this->authenticateRequest();
        [$page, $perPage, $errors] = self::pagination($_GET);
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
        if ($status !== null && (!is_string($status) || !in_array($status, MatchRequests::STATUSES, true))) {
            $errors['status'] = 'Use ' . implode(', ', MatchRequests::STATUSES) . '.';
        }
        $this->failIfInvalid($errors);
        $list = MatchRequest::forUser($this->userId, $status, $page, $perPage);
        $this->sendSuccess(array_map([self::class, 'format'], $list['items']), 200, self::meta($page, $perPage, $list['total']));
    }

    /** DELETE /api/v1/me/requests/{id} */
    public function cancel($id)
    {
        $this->authenticateRequest();
        MatchRequest::expireDue();
        $request = ctype_digit((string)$id) ? MatchRequest::findById((int)$id) : null;
        if (!$request || (int)$request['user_id'] !== $this->userId) {
            $this->sendError('Pedido não encontrado.', 'NOT_FOUND', 404);
        }
        if (!MatchRequests::canCancel($request, VenueSchedule::now()) || !MatchRequest::cancel($request)) {
            $this->sendError($request['status'] === 'approved'
                ? 'Só dá para cancelar um horário aprovado até ' . MatchRequests::CANCEL_APPROVED_HOURS . ' horas antes. Fale com o responsável pela quadra.'
                : 'Este pedido não pode mais ser cancelado.', 'CANNOT_CANCEL', 409);
        }
        $this->sendSuccess(self::format(MatchRequest::findById((int)$request['id'])));
    }

    /** GET /api/v1/me/notifications?page=&per_page= */
    public function notifications()
    {
        $this->authenticateRequest();
        MatchRequest::expireDue(); // o aviso de "expirado" aparece já na próxima consulta
        [$page, $perPage, $errors] = self::pagination($_GET);
        $this->failIfInvalid($errors);
        $lang = require BASE_PATH . '/lang/' . NotificationText::langFromHeader($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null) . '.php';
        $list = Notification::forUser($this->userId, $page, $perPage);
        $items = array_map(function ($n) use ($lang) {
            $text = NotificationText::render($n, $lang);
            return [
                'id' => (int)$n['id'],
                'type' => $n['type'],
                'title' => $text['title'],
                'body' => $text['body'],
                'data' => self::notificationData($n['data']),
                'read_at' => ApiFormat::datetime($n['read_at']),
                'created_at' => ApiFormat::datetime($n['created_at']),
            ];
        }, $list['items']);
        $this->sendSuccess($items, 200, self::meta($page, $perPage, $list['total']) + ['unread' => Notification::unreadCount($this->userId)]);
    }

    /** POST /api/v1/me/notifications/read  { "ids": [1, 2] } ou { "all": true } */
    public function readNotifications()
    {
        $this->authenticateRequest();
        $input = $this->getJsonInput();
        if (($input['all'] ?? null) === true) {
            Notification::markRead($this->userId);
        } else {
            $ids = $input['ids'] ?? null;
            $valid = is_array($ids) && $ids && count($ids) <= 100
                && !array_filter($ids, fn($id) => !is_int($id) || $id < 1);
            if (!$valid) {
                $this->failIfInvalid(['ids' => 'Envie "ids" com até 100 números de notificação, ou "all": true.']);
            }
            Notification::markRead($this->userId, $ids);
        }
        http_response_code(204);
        exit;
    }

    // --- Ajudantes ---

    /** Objeto "request" do contrato. */
    public static function format(array $r): array
    {
        $cover = ApiFormat::uploadUrl('venues/' . (int)$r['venue_id'], $r['venue_cover'] ?? null);
        return [
            'id' => (int)$r['id'],
            'venue' => [
                'id' => (int)$r['venue_id'],
                'name' => $r['venue_name'],
                'cover_image_url' => $cover ? ImageHelper::thumbUrl($cover) : null,
            ],
            'sport_id' => $r['sport_id'] !== null ? (int)$r['sport_id'] : null,
            'start_time' => ApiFormat::datetime($r['start_time']),
            'end_time' => ApiFormat::datetime($r['end_time']),
            'message' => $r['message'],
            'status' => $r['status'],
            'response_note' => $r['response_note'],
            'created_at' => ApiFormat::datetime($r['created_at']),
            'responded_at' => ApiFormat::datetime($r['responded_at']),
        ];
    }

    /** O app recebe só os ids para abrir a tela certa (o pedido ou a quadra), nunca nomes de outros. */
    private static function notificationData(array $data): array
    {
        return array_filter([
            'request_id' => isset($data['request_id']) ? (int)$data['request_id'] : null,
            'venue_id' => isset($data['venue_id']) ? (int)$data['venue_id'] : null,
        ], fn($v) => $v !== null);
    }

    /** ?page= e ?per_page=, como no GET /venues. */
    private static function pagination(array $query): array
    {
        $errors = [];
        $page = 1;
        $perPage = self::PER_PAGE_DEFAULT;
        if (isset($query['page']) && $query['page'] !== '') {
            if (is_string($query['page']) && ctype_digit($query['page']) && (int)$query['page'] >= 1) {
                $page = (int)$query['page'];
            } else {
                $errors['page'] = 'Use uma página a partir de 1.';
            }
        }
        if (isset($query['per_page']) && $query['per_page'] !== '') {
            $v = $query['per_page'];
            if (is_string($v) && ctype_digit($v) && (int)$v >= 1 && (int)$v <= self::PER_PAGE_MAX) {
                $perPage = (int)$v;
            } else {
                $errors['per_page'] = 'Use um número de 1 a ' . self::PER_PAGE_MAX . '.';
            }
        }
        return [$page, $perPage, $errors];
    }

    private static function meta(int $page, int $perPage, int $total): array
    {
        return [
            'current_page' => $page,
            'last_page' => max(1, (int)ceil($total / $perPage)),
            'per_page' => $perPage,
            'total' => $total,
        ];
    }
}
