<?php

namespace App\Core;

use DateTimeImmutable;

/**
 * Anúncios das quadras Premium (D63): as regras, sem acesso ao banco (o banco fica no VenueAd).
 * Uma quadra tem no máximo um anúncio "em aberto" (ativo ou pausado, ainda no período).
 * O anúncio só aparece para o jogador com o dono Premium, o anúncio ativo, dentro do período
 * e a quadra não desativada (D34). Premium vencido: o anúncio continua salvo, mas some.
 */
class VenueAds
{
    public const TITLE_MAX = 60;
    public const BODY_MAX = 140;
    /** Período máximo, contando o primeiro dia (10/11 a 09/12 = 30 dias). */
    public const MAX_DAYS = 30;
    /** Quantos "Patrocinado" aparecem no topo da home e da /quadras. */
    public const SITE_LIMIT = 3;
    /** Quantos vêm no GET /ads (carrossel "Destaques" do app). */
    public const API_LIMIT = 5;

    public const STATUSES = ['active', 'paused', 'ended', 'removed'];

    /** O que cada ação faz com o status: [de quais status, para qual]. */
    private const ACTIONS = [
        'pause' => [['active'], 'paused'],
        'resume' => [['paused'], 'active'],
        'end' => [['active', 'paused'], 'ended'],
        'remove' => [['active', 'paused', 'ended'], 'removed'],
    ];

    /**
     * Valida o formulário do anúncio (título, texto, início e fim).
     * Ao editar ($current), um início que já passou pode ficar como estava; um início novo
     * precisa ser hoje ou depois.
     * @return array{values: array, errors: array<string, string>} errors: campo => código
     */
    public static function validate(array $input, DateTimeImmutable $today, ?array $current = null): array
    {
        $title = self::cleanText($input['title'] ?? '');
        $body = self::cleanText($input['body'] ?? '');
        $startsOn = trim((string)($input['starts_on'] ?? ''));
        $endsOn = trim((string)($input['ends_on'] ?? ''));
        $todayStr = $today->format('Y-m-d');
        $errors = [];

        if ($title === '') {
            $errors['title'] = 'required';
        } elseif (mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'too_long';
        }
        if ($body === '') {
            $errors['body'] = 'required';
        } elseif (mb_strlen($body) > self::BODY_MAX) {
            $errors['body'] = 'too_long';
        }

        $start = self::parseDate($startsOn);
        $end = self::parseDate($endsOn);
        $keptStart = $current !== null && $startsOn === ($current['starts_on'] ?? null);
        if ($start === null) {
            $errors['starts_on'] = 'invalid';
        } elseif ($startsOn < $todayStr && !$keptStart) {
            $errors['starts_on'] = 'in_past';
        }
        if ($end === null) {
            $errors['ends_on'] = 'invalid';
        } elseif ($start !== null) {
            if ($endsOn < $startsOn) {
                $errors['ends_on'] = 'before_start';
            } elseif ($endsOn > self::lastDay($startsOn)) {
                $errors['ends_on'] = 'too_long';
            } elseif ($endsOn < $todayStr) {
                $errors['ends_on'] = 'in_past';
            }
        }

        return [
            'values' => $errors ? [] : ['title' => $title, 'body' => $body, 'starts_on' => $startsOn, 'ends_on' => $endsOn],
            'errors' => $errors,
        ];
    }

    /** Último dia possível para um anúncio que começa em $startsOn (YYYY-MM-DD). */
    public static function lastDay(string $startsOn): string
    {
        return (new DateTimeImmutable($startsOn))->modify('+' . (self::MAX_DAYS - 1) . ' days')->format('Y-m-d');
    }

    /**
     * O anúncio aparece hoje para o jogador (site e app)?
     * $ad precisa de status, starts_on e ends_on; $venue, de status e deleted_at.
     */
    public static function isVisible(array $ad, bool $ownerPremium, array $venue, DateTimeImmutable $today): bool
    {
        $day = $today->format('Y-m-d');
        return $ownerPremium
            && ($ad['status'] ?? '') === 'active'
            && ($ad['starts_on'] ?? '9999-12-31') <= $day
            && ($ad['ends_on'] ?? '0000-00-00') >= $day
            && ($venue['status'] ?? '') !== 'unavailable'
            && empty($venue['deleted_at']);
    }

    /**
     * Anúncio "em aberto": ativo ou pausado e ainda no período. É o que impede criar outro
     * na mesma quadra (um por vez, D63). Encerrado, removido ou vencido libera um novo.
     */
    public static function isOpen(array $ad, DateTimeImmutable $today): bool
    {
        return in_array($ad['status'] ?? '', ['active', 'paused'], true)
            && ($ad['ends_on'] ?? '0000-00-00') >= $today->format('Y-m-d');
    }

    /**
     * Situação para a tela do dono:
     * 'running' (aparecendo), 'scheduled' (começa depois), 'paused', 'premium_expired'
     * (ativo, mas o Premium venceu), 'finished' (o período acabou), 'ended' (o dono encerrou)
     * ou 'removed' (o admin removeu).
     */
    public static function ownerState(array $ad, bool $ownerPremium, DateTimeImmutable $today): string
    {
        $day = $today->format('Y-m-d');
        $status = $ad['status'] ?? '';
        if ($status === 'removed' || $status === 'ended') {
            return $status;
        }
        if (($ad['ends_on'] ?? '') < $day) {
            return 'finished';
        }
        if ($status === 'paused') {
            return 'paused';
        }
        if (!$ownerPremium) {
            return 'premium_expired';
        }
        return ($ad['starts_on'] ?? '') > $day ? 'scheduled' : 'running';
    }

    /**
     * Status depois de uma ação (pause, resume, end, remove), ou null se a ação não vale
     * para o status atual (ex: retomar um anúncio que não está pausado).
     */
    public static function nextStatus(string $current, string $action): ?string
    {
        if (!isset(self::ACTIONS[$action])) {
            return null;
        }
        [$from, $to] = self::ACTIONS[$action];
        return in_array($current, $from, true) ? $to : null;
    }

    /**
     * Sorteio do dia: a mesma ordem o dia todo (para a home não trocar a cada recarga) e outra
     * ordem no dia seguinte. Cada item precisa de 'id'.
     */
    public static function dailyPick(array $items, int $limit, DateTimeImmutable $today): array
    {
        $day = $today->format('Y-m-d');
        usort($items, fn($a, $b) => [crc32($day . '#' . $a['id']), $a['id']] <=> [crc32($day . '#' . $b['id']), $b['id']]);
        return array_slice($items, 0, max(0, $limit));
    }

    // --- Ajudantes ---

    /** Uma linha só: junta espaços e quebras de linha (o anúncio é um texto curto). */
    private static function cleanText($value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string)$value));
    }

    /** "2026-11-10" válido, ou null. */
    private static function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
