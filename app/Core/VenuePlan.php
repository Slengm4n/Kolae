<?php

namespace App\Core;

use App\Models\Notification;
use DateTimeImmutable;

/**
 * Plano da quadra (D61): Grátis ou Premium, ativado pelo admin, sem pagamento no Kolae.
 * Vencida a validade, a quadra volta a contar como Grátis sozinha (nada precisa rodar à noite).
 */
class VenuePlan
{
    public const FREE = 'free';
    public const PREMIUM = 'premium';

    /** Validade padrão ao ativar (D61). */
    public const DEFAULT_DAYS = 30;

    /** O Premium vale hoje? (plano premium e validade vazia ou ainda não vencida) */
    public static function isPremium(array $venue, ?DateTimeImmutable $today = null): bool
    {
        if (($venue['plan'] ?? self::FREE) !== self::PREMIUM) {
            return false;
        }
        $until = $venue['plan_until'] ?? null;
        if ($until === null || $until === '') {
            return true;
        }
        $today = $today ?? VenueSchedule::now();
        return $until >= $today->format('Y-m-d');
    }

    /**
     * Situação para a tela: 'premium' (vale hoje), 'expired' (era Premium e venceu),
     * 'requested' (Grátis com pedido em aberto) ou 'free'.
     */
    public static function state(array $venue, ?DateTimeImmutable $today = null): string
    {
        if (self::isPremium($venue, $today)) {
            return 'premium';
        }
        if (($venue['plan'] ?? self::FREE) === self::PREMIUM) {
            return 'expired';
        }
        return !empty($venue['plan_requested_at']) ? 'requested' : 'free';
    }

    /** Data de validade padrão (hoje + DEFAULT_DAYS), no formato YYYY-MM-DD. */
    public static function defaultUntil(?DateTimeImmutable $today = null): string
    {
        return ($today ?? VenueSchedule::now())->modify('+' . self::DEFAULT_DAYS . ' days')->format('Y-m-d');
    }

    /** O dono pede o Premium. Não faz nada se já for Premium ou se o pedido já estiver em aberto. */
    public static function request(array $venue): bool
    {
        if (self::isPremium($venue) || !empty($venue['plan_requested_at'])) {
            return false;
        }
        $stmt = Database::getConnection()->prepare("UPDATE venues SET plan_requested_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$venue['id']]);
    }

    /**
     * O admin ativa o Premium até $until (YYYY-MM-DD, hoje ou depois) ou sem prazo ($until = null).
     * Fecha o pedido e avisa o dono.
     */
    public static function activate(array $venue, ?string $until): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE venues SET plan = 'premium', plan_until = ?, plan_requested_at = NULL WHERE id = ?"
        );
        if (!$stmt->execute([$until, (int)$venue['id']])) {
            return false;
        }
        Notification::create((int)$venue['user_id'], 'plan_activated', [
            'venue_id' => (int)$venue['id'],
            'venue_name' => $venue['name'],
            'until' => $until,
        ]);
        return true;
    }

    /** O admin volta a quadra para o Grátis (e descarta um pedido em aberto). */
    public static function deactivate(array $venue): bool
    {
        $stmt = Database::getConnection()->prepare(
            "UPDATE venues SET plan = 'free', plan_until = NULL, plan_requested_at = NULL WHERE id = ?"
        );
        return $stmt->execute([(int)$venue['id']]);
    }

    /**
     * Valida a data de validade enviada pelo admin. Devolve [ok?, data ou null].
     * Vazio com "sem prazo" = null; senão precisa ser uma data real de hoje em diante.
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

    /** Quadras com pedido de Premium em aberto e as Premium, para o painel do admin. */
    public static function adminList(): array
    {
        $stmt = Database::getConnection()->query(
            "SELECT v.id, v.name, v.plan, v.plan_until, v.plan_requested_at, u.name AS owner_name
               FROM venues v JOIN users u ON u.id = v.user_id
              WHERE v.status <> 'unavailable' AND (v.plan_requested_at IS NOT NULL OR v.plan = 'premium')
              ORDER BY (v.plan_requested_at IS NULL), v.plan_requested_at, v.plan_until"
        );
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
