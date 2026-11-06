<?php

namespace App\Core;

use DateTimeImmutable;
use PDO;

/**
 * Estatísticas da quadra (D62): registra visualizações e cliques em `venue_events` e monta os
 * números da tela "Estatísticas". Só contagens: nunca guarda IP nem "quem visitou".
 */
class VenueStats
{
    /** Tipos aceitos. `view` vem da página pública e do GET /venues/{id}; o resto, de cliques. */
    public const TYPES = ['view', 'whatsapp', 'instagram', 'tiktok', 'facebook', 'website', 'share', 'ad_view', 'ad_click'];
    public const CLICK_TYPES = ['whatsapp', 'instagram', 'tiktok', 'facebook', 'website', 'share', 'ad_view', 'ad_click'];
    public const SOURCES = ['site', 'app', 'qr'];
    public const SOCIAL_TYPES = ['instagram', 'tiktok', 'facebook', 'website'];

    /** Mesma visualização do mesmo visitante em menos disso não conta de novo. */
    public const VIEW_DEDUP_MINUTES = 30;
    /** Mesmo clique do mesmo visitante em menos disso não conta de novo (toque duplo). */
    public const CLICK_DEDUP_SECONDS = 60;
    /** Limite de eventos por visitante e quadra numa hora (POST /venues/{id}/events responde 429). */
    public const MAX_EVENTS_PER_HOUR = 60;

    /** Períodos da tela; o Grátis só vê o primeiro. */
    public const PERIODS = [7, 28, 90];

    /** Robôs e prévias de link (WhatsApp, Facebook etc.) não contam como visita. */
    public static function isBot(?string $userAgent): bool
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }
        // O app não passa por aqui: eventos com source "app" não são filtrados por navegador
        return (bool)preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|headless|lighthouse|curl|wget|python|httpclient/i', $userAgent);
    }

    /** Hash do visitante no dia: IP + navegador + data, com segredo. Não dá para voltar ao IP. */
    public static function visitor(?string $ip, ?string $userAgent, ?DateTimeImmutable $now = null): string
    {
        $day = ($now ?? VenueSchedule::now())->format('Y-m-d');
        $secret = defined('JWT_SECRET') ? JWT_SECRET : 'kolae';
        return substr(hash_hmac('sha256', ($ip ?? '') . '|' . ($userAgent ?? '') . '|' . $day, $secret), 0, 16);
    }

    /**
     * Registra um evento, aplicando os filtros (robô, dono, admin, repetição).
     * @param array $venue linha da quadra (precisa de id e user_id)
     * @param ?array $viewer usuário logado (id e role) ou null
     * @return bool true se o evento foi gravado
     */
    public static function record(array $venue, string $type, string $source, ?array $viewer = null): bool
    {
        if (!in_array($type, self::TYPES, true) || !in_array($source, self::SOURCES, true)) {
            return false;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if ($source !== 'app' && self::isBot($ua)) {
            return false;
        }
        if ($viewer && ((int)$viewer['id'] === (int)$venue['user_id'] || ($viewer['role'] ?? '') === 'admin')) {
            return false; // o dono e o admin não inflam os números
        }
        $visitor = self::visitor($_SERVER['REMOTE_ADDR'] ?? null, $ua);
        try {
            $pdo = Database::getConnection();
            $window = $type === 'view' || $type === 'ad_view'
                ? 'INTERVAL ' . self::VIEW_DEDUP_MINUTES . ' MINUTE'
                : 'INTERVAL ' . self::CLICK_DEDUP_SECONDS . ' SECOND';
            $stmt = $pdo->prepare("SELECT 1 FROM venue_events WHERE venue_id = ? AND type = ? AND visitor = ? AND created_at > NOW() - $window LIMIT 1");
            $stmt->execute([(int)$venue['id'], $type, $visitor]);
            if ($stmt->fetchColumn()) {
                return false;
            }
            $stmt = $pdo->prepare("INSERT INTO venue_events (venue_id, type, source, visitor) VALUES (?, ?, ?, ?)");
            return $stmt->execute([(int)$venue['id'], $type, $source, $visitor]);
        } catch (\Throwable $e) {
            // Estatística nunca derruba a página nem a API
            error_log('Estatísticas: não registrou o evento: ' . $e->getMessage());
            return false;
        }
    }

    /** O visitante passou do limite de eventos por hora nesta quadra? (429 na API) */
    public static function overLimit(int $venueId): bool
    {
        $visitor = self::visitor($_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
        try {
            $stmt = Database::getConnection()->prepare(
                "SELECT COUNT(*) FROM venue_events WHERE venue_id = ? AND visitor = ? AND created_at > NOW() - INTERVAL 1 HOUR"
            );
            $stmt->execute([$venueId, $visitor]);
            return (int)$stmt->fetchColumn() >= self::MAX_EVENTS_PER_HOUR;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Período válido (7, 28 ou 90); o Grátis fica sempre em 7. */
    public static function period($input, bool $premium): int
    {
        $days = (int)$input;
        if (!$premium || !in_array($days, self::PERIODS, true)) {
            return self::PERIODS[0];
        }
        return $days;
    }

    /**
     * Números do período (últimos $days dias, contando hoje) e do período anterior, para comparar.
     * @return array ['current' => totais, 'previous' => totais, 'daily' => [...], 'sources' => [...], 'from', 'to']
     */
    public static function summary(int $venueId, int $days, ?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? VenueSchedule::now())->setTime(0, 0);
        $from = $today->modify('-' . ($days - 1) . ' days');
        $end = $today->modify('+1 day');
        $prevFrom = $from->modify("-$days days");

        $current = self::totals($venueId, $from, $end);
        $previous = self::totals($venueId, $prevFrom, $from);
        $current += self::occupancy($venueId, $from, $end);
        $previous += self::occupancy($venueId, $prevFrom, $from);

        return [
            'from' => $from->format('Y-m-d'),
            'to' => $today->format('Y-m-d'),
            'current' => $current,
            'previous' => $previous,
            'daily' => self::daily($venueId, $from, $end),
            'sources' => self::sources($venueId, $from, $end),
            'heatmap' => self::heatmap($venueId, $from, $end),
        ];
    }

    /** Totais por tipo, visitantes únicos e taxa de contato. */
    private static function totals(int $venueId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT type, COUNT(*) AS n, COUNT(DISTINCT visitor) AS u
               FROM venue_events WHERE venue_id = ? AND created_at >= ? AND created_at < ? GROUP BY type"
        );
        $stmt->execute([$venueId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        $t = array_fill_keys(self::TYPES, 0);
        $visitors = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $t[$r['type']] = (int)$r['n'];
            if ($r['type'] === 'view') {
                $visitors = (int)$r['u'];
            }
        }
        $social = array_sum(array_intersect_key($t, array_flip(self::SOCIAL_TYPES)));
        return [
            'views' => $t['view'],
            'visitors' => $visitors,
            'whatsapp' => $t['whatsapp'],
            'social' => $social,
            'share' => $t['share'],
            'ad_views' => $t['ad_view'],
            'ad_clicks' => $t['ad_click'],
            'by_type' => $t,
            'contact_rate' => $t['view'] > 0 ? round($t['whatsapp'] / $t['view'] * 100, 1) : 0.0,
        ];
    }

    /** Horas agendadas e partidas no período (da agenda, D02). */
    private static function occupancy(int $venueId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT COUNT(*) AS n, COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0) AS m
               FROM matches WHERE venue_id = ? AND status = 'scheduled' AND deleted_at IS NULL
                AND start_time >= ? AND start_time < ?"
        );
        $stmt->execute([$venueId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return ['matches' => (int)$r['n'], 'hours' => round((int)$r['m'] / 60, 1)];
    }

    /** Visualizações e contatos (WhatsApp) por dia, com os dias sem nada em zero. */
    private static function daily(int $venueId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT DATE(created_at) AS d, SUM(type = 'view') AS views, SUM(type = 'whatsapp') AS contacts
               FROM venue_events WHERE venue_id = ? AND created_at >= ? AND created_at < ? GROUP BY DATE(created_at)"
        );
        $stmt->execute([$venueId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        $byDay = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $byDay[$r['d']] = ['views' => (int)$r['views'], 'contacts' => (int)$r['contacts']];
        }
        $out = [];
        for ($d = $from; $d < $to; $d = $d->modify('+1 day')) {
            $key = $d->format('Y-m-d');
            $out[] = ['date' => $key] + ($byDay[$key] ?? ['views' => 0, 'contacts' => 0]);
        }
        return $out;
    }

    /** De onde vieram as visualizações: site, app ou QR Code. */
    private static function sources(int $venueId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $stmt = Database::getConnection()->prepare(
            "SELECT source, COUNT(*) AS n FROM venue_events
              WHERE venue_id = ? AND type = 'view' AND created_at >= ? AND created_at < ? GROUP BY source"
        );
        $stmt->execute([$venueId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        $out = array_fill_keys(self::SOURCES, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $out[$r['source']] = (int)$r['n'];
        }
        return $out;
    }

    /**
     * Ocupação por dia da semana (0 = domingo) e hora (0–23), em minutos agendados no período.
     * Uma partida das 19:30 às 21:00 soma 30 min às 19h e 60 min às 20h.
     */
    private static function heatmap(int $venueId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $grid = array_fill(0, 7, array_fill(0, 24, 0));
        $stmt = Database::getConnection()->prepare(
            "SELECT start_time, end_time FROM matches WHERE venue_id = ? AND status = 'scheduled' AND deleted_at IS NULL
                AND start_time >= ? AND start_time < ?"
        );
        $stmt->execute([$venueId, $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $t = strtotime($m['start_time']);
            $end = strtotime($m['end_time']);
            while ($t < $end) {
                $hourEnd = min($end, (int)(floor($t / 3600) * 3600) + 3600);
                $grid[(int)date('w', $t)][(int)date('G', $t)] += (int)round(($hourEnd - $t) / 60);
                $t = $hourEnd;
            }
        }
        return $grid;
    }

    /** Variação em % em relação ao período anterior (null quando antes era zero). */
    public static function delta($current, $previous): ?float
    {
        if ((float)$previous == 0.0) {
            return null;
        }
        return round(((float)$current - (float)$previous) / (float)$previous * 100);
    }
}
