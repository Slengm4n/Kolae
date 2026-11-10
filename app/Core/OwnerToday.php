<?php

namespace App\Core;

use App\Models\Conversation;
use App\Models\MatchRequest;
use App\Models\VenueAd;
use DateTimeImmutable;

/**
 * Bloco "Hoje" do painel do dono (D67, parte B): pedidos esperando (D64), mensagens não lidas
 * (D66), partidas de hoje e a situação do Kolae Premium e do anúncio (D61, D63). Cada número leva
 * à tela que resolve. Nada novo no banco: só junta o que as outras partes já guardam.
 */
class OwnerToday
{
    /**
     * Partidas de hoje: quantas há (nas quadras ativas) e qual é a próxima (a que está
     * acontecendo agora conta como a próxima). Sem acesso ao banco.
     * @param array $venues quadras do dono (id, name, status)
     * @param array $matchesByVenue venue_id => partidas do dia (Game::findScheduledOn)
     * @return array{count: int, next: ?array, done: bool}
     *   next = [venue_id, venue_name, start, end, ongoing]; done = houve partida, mas todas acabaram
     */
    public static function matches(array $venues, array $matchesByVenue, DateTimeImmutable $now): array
    {
        $count = 0;
        $next = null;
        $nowTs = $now->getTimestamp();
        foreach ($venues as $venue) {
            if (($venue['status'] ?? '') === 'unavailable' || !empty($venue['deleted_at'])) {
                continue; // quadra desativada não entra
            }
            foreach ($matchesByVenue[(int)$venue['id']] ?? [] as $m) {
                $count++;
                $start = strtotime($m['start_time']);
                $end = strtotime($m['end_time']);
                if ($end <= $nowTs) {
                    continue; // já acabou
                }
                if ($next === null || $start < $next['start_ts']) {
                    $next = [
                        'venue_id' => (int)$venue['id'],
                        'venue_name' => $venue['name'],
                        'start' => date('H:i', $start),
                        'end' => date('H:i', $end),
                        'ongoing' => $start <= $nowTs,
                        'start_ts' => $start,
                    ];
                }
            }
        }
        if ($next !== null) {
            unset($next['start_ts']);
        }
        return ['count' => $count, 'next' => $next, 'done' => $count > 0 && $next === null];
    }

    /**
     * O anúncio que interessa ao dono: o no ar primeiro; senão, um pausado. Sem acesso ao banco.
     * @param array $openByVenue venue_id => anúncio em aberto (ativo ou pausado) ou null
     * @param array $visibleIds ids das quadras com anúncio aparecendo hoje
     * @return array{state: string, venue_id: ?int, venue_name: ?string} state: 'running' | 'paused' | 'none'
     */
    public static function ad(array $venues, array $openByVenue, array $visibleIds): array
    {
        $paused = null;
        foreach ($venues as $venue) {
            $id = (int)$venue['id'];
            if (in_array($id, $visibleIds, true)) {
                return ['state' => 'running', 'venue_id' => $id, 'venue_name' => $venue['name']];
            }
            if ($paused === null && !empty($openByVenue[$id]) && ($openByVenue[$id]['status'] ?? '') === 'paused') {
                $paused = ['state' => 'paused', 'venue_id' => $id, 'venue_name' => $venue['name']];
            }
        }
        return $paused ?? ['state' => 'none', 'venue_id' => null, 'venue_name' => null];
    }

    /**
     * Tudo o que o bloco mostra. Se uma parte falhar (ex.: banco sem as tabelas), o número dela
     * fica 0 e o painel abre normalmente.
     */
    public static function load(array $user, array $venues, array $matchesByVenue, DateTimeImmutable $now): array
    {
        $ownerId = (int)$user['id'];
        $safe = function (callable $fn, $fallback) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                error_log('Painel (Hoje): ' . $e->getMessage());
                return $fallback;
            }
        };
        $active = array_values(array_filter($venues, fn($v) => ($v['status'] ?? '') !== 'unavailable' && empty($v['deleted_at'])));
        $ids = array_map(fn($v) => (int)$v['id'], $active);
        $today = $now->setTime(0, 0);

        $premiumState = PremiumPlan::state($user, $today);
        $ad = ['state' => 'none', 'venue_id' => null, 'venue_name' => null];
        if ($premiumState === 'premium' && $ids) {
            $ad = $safe(function () use ($active, $ids, $today) {
                $open = [];
                foreach ($ids as $id) {
                    $open[$id] = VenueAd::findOpenForVenue($id, $today);
                }
                return self::ad($active, $open, array_map('intval', array_keys(VenueAd::visibleByVenue($today, $ids))));
            }, $ad);
        }

        return [
            'requests' => $safe(fn() => MatchRequest::pendingForOwner($ownerId), 0),
            'messages' => $safe(fn() => Conversation::unreadForOwner($ownerId), 0),
            'matches' => self::matches($venues, $matchesByVenue, $now),
            'premium' => ['state' => $premiumState, 'until' => $user['plan_until'] ?? null],
            'ad' => $ad,
            // Sem anúncio, o cartão do Premium leva à aba Anúncio da primeira quadra ativa
            'first_venue_id' => $ids[0] ?? null,
        ];
    }
}
