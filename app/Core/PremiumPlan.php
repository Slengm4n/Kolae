<?php

namespace App\Core;

use App\Models\Notification;
use App\Models\User;
use DateTimeImmutable;

/**
 * Kolae Premium (D61): o plano é do **dono** (perfil), como o CNPJ, e vale para todas as quadras
 * dele. Ativado pelo admin, sem pagamento no Kolae. Vencida a validade, a conta volta a contar
 * como Grátis sozinha (nada precisa rodar à noite). Anúncios e estatísticas continuam por quadra.
 */
class PremiumPlan
{
    public const FREE = 'free';
    public const PREMIUM = 'premium';

    /** Validade padrão ao ativar (D61). */
    public const DEFAULT_DAYS = 30;

    /** Donos já consultados nesta requisição (forVenue é chamado várias vezes por página). */
    private static array $owners = [];

    /** O Premium vale hoje para este usuário? (plano premium e validade vazia ou ainda não vencida) */
    public static function isPremium(?array $user, ?DateTimeImmutable $today = null): bool
    {
        if (!$user || ($user['plan'] ?? self::FREE) !== self::PREMIUM) {
            return false;
        }
        $until = $user['plan_until'] ?? null;
        if ($until === null || $until === '') {
            return true;
        }
        $today = $today ?? VenueSchedule::now();
        return $until >= $today->format('Y-m-d');
    }

    /** A quadra é de um dono Premium? (anúncios e estatísticas completas, D62–D63) */
    public static function forVenue(array $venue): bool
    {
        $ownerId = (int)($venue['user_id'] ?? 0);
        if (!array_key_exists($ownerId, self::$owners)) {
            self::$owners[$ownerId] = $ownerId ? (User::findById($ownerId) ?: null) : null;
        }
        return self::isPremium(self::$owners[$ownerId]);
    }

    /**
     * Situação para a tela: 'premium' (vale hoje), 'expired' (era Premium e venceu),
     * 'requested' (Grátis com pedido em aberto) ou 'free'.
     */
    public static function state(array $user, ?DateTimeImmutable $today = null): string
    {
        if (self::isPremium($user, $today)) {
            return 'premium';
        }
        if (($user['plan'] ?? self::FREE) === self::PREMIUM) {
            return 'expired';
        }
        return !empty($user['plan_requested_at']) ? 'requested' : 'free';
    }

    /** Data de validade padrão (hoje + DEFAULT_DAYS), no formato YYYY-MM-DD. */
    public static function defaultUntil(?DateTimeImmutable $today = null): string
    {
        return ($today ?? VenueSchedule::now())->modify('+' . self::DEFAULT_DAYS . ' days')->format('Y-m-d');
    }

    /** O dono pede o Premium. Não faz nada se já for Premium ou se o pedido já estiver em aberto. */
    public static function request(array $user): bool
    {
        if (self::isPremium($user) || !empty($user['plan_requested_at'])) {
            return false;
        }
        $stmt = Database::getConnection()->prepare("UPDATE users SET plan_requested_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$user['id']]);
    }

    /**
     * O admin ativa o Premium até $until (YYYY-MM-DD, hoje ou depois) ou sem prazo ($until = null).
     * Fecha o pedido e avisa o dono.
     */
    public static function activate(array $user, ?string $until): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE users SET plan = 'premium', plan_until = ?, plan_requested_at = NULL WHERE id = ?"
        );
        if (!$stmt->execute([$until, (int)$user['id']])) {
            return false;
        }
        Notification::create((int)$user['id'], 'plan_activated', ['until' => $until]);
        return true;
    }

    /** O admin volta a conta para o Grátis (e descarta um pedido em aberto). */
    public static function deactivate(array $user): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE users SET plan = 'free', plan_until = NULL, plan_requested_at = NULL WHERE id = ?"
        );
        return $stmt->execute([(int)$user['id']]);
    }

    /**
     * Valida a data de validade enviada pelo admin. Devolve [ok?, data ou null].
     * "Sem prazo" = null; senão precisa ser uma data real de hoje em diante.
     */
    public static function parseUntil(?string $input, bool $noLimit, ?DateTimeImmutable $today = null): array
    {
        if ($noLimit) {
            return [true, null];
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$input);
        if (!$date || $date->format('Y-m-d') !== $input) {
            return [false, null];
        }
        $today = ($today ?? VenueSchedule::now())->format('Y-m-d');
        return $input >= $today ? [true, $input] : [false, null];
    }

    /** Donos com pedido de Premium em aberto e os Premium, com quantas quadras têm (painel do admin). */
    public static function adminList(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT u.id, u.name, u.email, u.plan, u.plan_until, u.plan_requested_at,
                    (SELECT COUNT(*) FROM venues v WHERE v.user_id = u.id AND v.status <> 'unavailable') AS venues
               FROM users u
              WHERE u.status = 'active' AND (u.plan_requested_at IS NOT NULL OR u.plan = 'premium')
              ORDER BY (u.plan_requested_at IS NULL), u.plan_requested_at, u.plan_until"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
