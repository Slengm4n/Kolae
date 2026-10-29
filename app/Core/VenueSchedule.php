<?php

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Class VenueSchedule
 * Regra do status da quadra e da agenda do dia (docs/modelo-status.md, seções 3 e 4).
 * Não acessa o banco: recebe o horário de funcionamento e as partidas já carregados,
 * para a regra ficar num lugar só (D03) e poder ser testada isoladamente.
 *
 * Formatos de entrada:
 *   $hours   [weekday => ['opens_at' => '08:00:00', 'closes_at' => '22:00:00'], ...]  (0 = domingo)
 *   $matches [['start_time' => 'Y-m-d H:i:s', 'end_time' => 'Y-m-d H:i:s'], ...]   (só partidas scheduled)
 */
class VenueSchedule
{
    public const TIMEZONE = 'America/Sao_Paulo';

    /** Quantos dias à frente procurar a próxima abertura. */
    private const NEXT_OPENING_DAYS = 7;

    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone(self::TIMEZONE);
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::timezone());
    }

    /**
     * Status da quadra num instante.
     * @param string $venueStatus venues.status ('available' segue o horário; 'maintenance' = fechada pelo dono)
     * @return array{status: string, status_until: ?DateTimeImmutable}
     */
    public static function status(string $venueStatus, array $hours, array $matches, DateTimeImmutable $now): array
    {
        // 1. Fechada pelo dono: sem previsão de reabrir
        if ($venueStatus !== 'available') {
            return self::result('closed', null);
        }

        // 2. Horário de funcionamento de hoje
        [$opens, $closes] = self::openingOn($hours, $now);
        if ($opens === null || $now >= $closes) {
            return self::result('closed', self::nextOpening($hours, $now));
        }
        if ($now < $opens) {
            return self::result('closed', $opens);
        }

        // 3. Dentro do horário: há partida agora?
        $intervals = self::busyIntervals($matches, $opens, $closes);
        foreach ($intervals as [$start, $end]) {
            if ($start <= $now && $now < $end) {
                return self::result('in_use', $end);
            }
            if ($start > $now) {
                return self::result('available', $start); // próxima partida de hoje
            }
        }
        return self::result('available', $closes);
    }

    /**
     * Agenda de um dia: faixas livres e ocupadas dentro do horário de funcionamento.
     * @return array{opens_at: ?string, closes_at: ?string, slots: array}
     */
    public static function daySlots(string $venueStatus, array $hours, array $matches, DateTimeImmutable $day): array
    {
        [$opens, $closes] = $venueStatus === 'available' ? self::openingOn($hours, $day) : [null, null];
        if ($opens === null) {
            return ['opens_at' => null, 'closes_at' => null, 'slots' => []];
        }

        $slots = [];
        $cursor = $opens;
        foreach (self::busyIntervals($matches, $opens, $closes) as [$start, $end]) {
            if ($start > $cursor) {
                $slots[] = self::slot($cursor, $start, 'available');
            }
            $slots[] = self::slot($start, $end, 'in_use');
            $cursor = $end;
        }
        if ($cursor < $closes) {
            $slots[] = self::slot($cursor, $closes, 'available');
        }

        return [
            'opens_at' => $opens->format('H:i'),
            'closes_at' => $closes->format('H:i'),
            'slots' => $slots
        ];
    }

    /**
     * Abertura e fechamento num dia, ou [null, null] se a quadra não abre nesse dia.
     * @return array{0: ?DateTimeImmutable, 1: ?DateTimeImmutable}
     */
    private static function openingOn(array $hours, DateTimeImmutable $day): array
    {
        $row = $hours[(int)$day->format('w')] ?? null;
        if (!$row) {
            return [null, null];
        }
        return [self::atTime($day, $row['opens_at']), self::atTime($day, $row['closes_at'])];
    }

    /**
     * Próxima abertura a partir do dia seguinte, ou null se não houver nos próximos dias.
     */
    private static function nextOpening(array $hours, DateTimeImmutable $now): ?DateTimeImmutable
    {
        for ($i = 1; $i <= self::NEXT_OPENING_DAYS; $i++) {
            [$opens] = self::openingOn($hours, $now->modify("+$i day"));
            if ($opens !== null) {
                return $opens;
            }
        }
        return null;
    }

    /**
     * Partidas recortadas ao horário de funcionamento, em ordem, com as que se
     * encostam ou se sobrepõem juntadas num intervalo só (partidas em sequência).
     * @return array<int, array{0: DateTimeImmutable, 1: DateTimeImmutable}>
     */
    private static function busyIntervals(array $matches, DateTimeImmutable $opens, DateTimeImmutable $closes): array
    {
        $intervals = [];
        foreach ($matches as $m) {
            $start = max(new DateTimeImmutable($m['start_time'], self::timezone()), $opens);
            $end = min(new DateTimeImmutable($m['end_time'], self::timezone()), $closes);
            if ($start < $end) {
                $intervals[] = [$start, $end];
            }
        }
        usort($intervals, fn($a, $b) => $a[0] <=> $b[0]);

        $merged = [];
        foreach ($intervals as [$start, $end]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }
        return $merged;
    }

    private static function atTime(DateTimeImmutable $day, string $time): DateTimeImmutable
    {
        [$h, $i] = array_map('intval', explode(':', $time));
        return $day->setTime($h, $i, 0);
    }

    private static function slot(DateTimeImmutable $start, DateTimeImmutable $end, string $status): array
    {
        return ['start' => $start->format(DATE_ATOM), 'end' => $end->format(DATE_ATOM), 'status' => $status];
    }

    private static function result(string $status, ?DateTimeImmutable $until): array
    {
        return ['status' => $status, 'status_until' => $until];
    }
}
