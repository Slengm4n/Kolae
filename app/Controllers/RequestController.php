<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\MatchRequests;
use App\Core\NotificationText;
use App\Core\ViewHelper;
use App\Models\Conversation;
use App\Models\MatchRequest;
use App\Models\Notification;

/**
 * "Notificações e solicitações" no painel do dono (D64, parte C): os pedidos de horário das
 * quadras dele (aprovar ou recusar) e os avisos (notifications). Quem pede usa o app
 * (RequestApiController). O dono vê o primeiro nome de quem pediu e, depois de aprovar, o WhatsApp
 * (se a pessoa cadastrou); nunca o e-mail.
 */
class RequestController
{
    public const TABS = ['solicitacoes', 'avisos'];

    /** Número do sino: pedidos pendentes e avisos não lidos (sem os de "novo pedido", já contados). */
    public static function bellCount(int $ownerId): int
    {
        try {
            return MatchRequest::pendingForOwner($ownerId) + Notification::unreadCount($ownerId, ['request_new']);
        } catch (\Throwable $e) {
            return 0; // sem as tabelas (banco antigo), o painel abre sem o número
        }
    }

    /** GET /dashboard/notificacoes?aba=solicitacoes|avisos */
    public function index()
    {
        $ownerId = $this->ownerId();
        $tab = in_array($_GET['aba'] ?? '', self::TABS, true) ? $_GET['aba'] : null;
        $requests = MatchRequest::forOwner($ownerId);
        // Sem aba na URL: abre nas solicitações se houver pedido esperando; senão, nos avisos
        $tab = $tab ?? ($requests['pending'] ? 'solicitacoes' : 'avisos');

        $lang = require BASE_PATH . '/includes/i18n.php';
        $list = Notification::forUser($ownerId, 1, 50);
        $notifications = array_map(fn($n) => $n + NotificationText::render($n, $lang), $list['items']);
        $unreadOther = Notification::unreadCount($ownerId, ['request_new']);
        if ($tab === 'avisos') {
            // Abrir os avisos marca como lidos (os da lista continuam destacados nesta visita)
            Notification::markRead($ownerId);
        }
        try {
            $chatUnread = Conversation::unreadForOwner($ownerId);
        } catch (\Throwable $e) {
            $chatUnread = 0;
        }

        ViewHelper::render('requests/index', [
            'tab' => $tab,
            'pending' => $requests['pending'],
            'history' => $requests['history'],
            'notifications' => $notifications,
            'unreadOther' => $unreadOther,
            'chatUnread' => $chatUnread,
        ]);
    }

    /** POST /dashboard/notificacoes/pedidos/{id}/aprovar */
    public function approve($id)
    {
        $ownerId = $this->ownerId();
        $request = $this->findMine($id, $ownerId);
        $result = MatchRequest::approve($request, $ownerId);
        if (!$result['ok']) {
            $code = ['not_pending' => 'req_not_pending', 'overlap' => 'req_overlap', 'outside_hours' => 'req_outside',
                'closed_day' => 'req_outside', 'in_past' => 'req_not_pending'][$result['error']] ?? 'req_failed';
            $this->redirect('solicitacoes', 'error=' . $code);
        }
        $this->redirect('solicitacoes', 'status=' . ($result['declined'] ? 'req_approved_others' : 'req_approved'));
    }

    /** POST /dashboard/notificacoes/pedidos/{id}/recusar  (motivo opcional: note) */
    public function decline($id)
    {
        $ownerId = $this->ownerId();
        $request = $this->findMine($id, $ownerId);
        [$note, $error] = MatchRequests::cleanNote($_POST['note'] ?? null);
        if ($error !== null) {
            $this->redirect('solicitacoes', 'error=req_note_long');
        }
        $ok = MatchRequest::decline($request, $note);
        $this->redirect('solicitacoes', $ok ? 'status=req_declined' : 'error=req_not_pending');
    }

    // --- Ajudantes ---

    /** Só o dono (o admin não aprova pedidos de ninguém). */
    private function ownerId(): int
    {
        AuthHelper::check();
        if (($_SESSION['user_role'] ?? '') !== 'owner') {
            header('Location: ' . BASE_URL . (AuthHelper::isAdmin() ? '/admin' : '/dashboard'));
            exit;
        }
        return (int)$_SESSION['user_id'];
    }

    /** Pedido de uma quadra do dono (de conta ativa e quadra não excluída); senão volta com o aviso. */
    private function findMine($id, int $ownerId): array
    {
        MatchRequest::expireDue();
        $request = ctype_digit((string)$id) ? MatchRequest::findById((int)$id) : null;
        if (!$request || (int)$request['venue_owner_id'] !== $ownerId || !empty($request['venue_deleted_at'])
            || ($request['user_status'] ?? '') !== 'active') {
            $this->redirect('solicitacoes', 'error=req_not_found');
        }
        return $request;
    }

    private function redirect(string $tab, string $query): void
    {
        header('Location: ' . BASE_URL . '/dashboard/notificacoes?aba=' . $tab . '&' . $query);
        exit;
    }
}
