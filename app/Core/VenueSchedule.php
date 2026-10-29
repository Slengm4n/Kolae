<?php

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Class VenueSchedule
 * Regra do status da quadra e da agenda do dia (docs/modelo-status.md, seções 3 e 4),
 * e as validações do horário de funcionamento e das partidas que o dono cadastra no site.
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
     * Confere o horário de funcionamento enviado pelo formulário (D21, D22).
     * @param array $input [weekday => ['open' => '1', 'opens_at' => 'HH:MM', 'closes_at' => 'HH:MM']]
     *                     Dia sem 'open' = fechado.
     * @return array{hours: array, errors: array} hours no formato de $hours (só os dias abertos);
     *               errors [weekday => 'invalid_time' | 'closes_before_opens']
     */
    public static function validateHours(array $input): array
    {
        $hours = [];
        $errors = [];
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            $day = $input[$weekday] ?? [];
            if (!is_array($day) || empty($day['open'])) {
                continue;
            }

            $opens = self::parseTime($day['opens_at'] ?? '');
            $closes = self::parseTime($day['closes_at'] ?? '');
            if ($opens === null || $closes === null) {
                $errors[$weekday] = 'invalid_time';
            } elseif ($closes <= $opens) {
                // Sem virar a meia-noite (D22): fecha no máximo às 23:59
                $errors[$weekday] = 'closes_before_opens';
            } else {
                $hours[$weekday] = ['opens_at' => $opens . ':00', 'closes_at' => $closes . ':00'];
            }
        }
        return ['hours' => $hours, 'errors' => $errors];
    }

    /**
     * Confere uma partida nova antes de gravar (D27, docs/modelo-status.md seção 2.3).
     * Partida que já começou pode ser cadastrada (ex: aluguel feito na hora); só recusa
     * a que já terminou.
     * @param array $matches partidas scheduled do mesmo dia (formato de $matches)
     * @return string|null null se pode gravar; senão o motivo:
     *   'end_before_start' | 'different_day' | 'in_past' | 'closed_day' | 'outside_hours' | 'overlap'
     */
    public static function validateMatch(array $hours, array $matches, DateTimeImmutable $start, DateTimeImmutable $end, DateTimeImmutable $now): ?string
    {
        if ($end <= $start) {
            return 'end_before_start';
        }
        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return 'different_day';
        }
        if ($end <= $now) {
            return 'in_past';
        }

        [$opens, $closes] = self::openingOn($hours, $start);
        if ($opens === null) {
            return 'closed_day';
        }
        if ($start < $opens || $end > $closes) {
            return 'outside_hours';
        }

        // Encostar é permitido (uma termina 19:00, a outra começa 19:00)
        foreach ($matches as $m) {
            $mStart = new DateTimeImmutable($m['start_time'], self::timezone());
            $mEnd = new DateTimeImmutable($m['end_time'], self::timezone());
            if ($start < $mEnd && $end > $mStart) {
                return 'overlap';
            }
        }
        return null;
    }

    /**
     * Partidas que deixam de caber no horário de funcionamento (ex: depois de o dono mudar
     * o horário). O site só avisa, sem bloquear (D27).
     * @param array $matches partidas com 'start_time' e 'end_time' (outras chaves são mantidas)
     * @return array as partidas de $matches que ficaram de fora, na mesma ordem
     */
    public static function matchesOutsideHours(array $hours, array $matches): array
    {
        $outside = [];
        foreach ($matches as $m) {
            $start = new DateTimeImmutable($m['start_time'], self::timezone());
            $end = new DateTimeImmutable($m['end_time'], self::timezone());
            [$opens, $closes] = self::openingOn($hours, $start);
            if ($opens === null || $start < $opens || $end > $closes) {
                $outside[] = $m;
            }
        }
        return $outside;
    }

    /**
     * Junta data ('Y-m-d') e hora ('HH:MM') do formulário num instante de Brasília.
     * Retorna null se algum dos dois for inválido (ex: 31/02, 25:00).
     */
    public static function at(string $date, string $time): ?DateTimeImmutable
    {
        $time = self::parseTime($time);
        if ($time === null) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time", self::timezone());
        return ($dt && $dt->format('Y-m-d H:i') === "$date $time") ? $dt : null;
    }

    /**
     * 'HH:MM' ou 'HH:MM:SS' (como vem do banco) → 'HH:MM'; null se inválido.
     */
    private static function parseTime($time): ?string
    {
        if (!is_string($time) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', $time, $m)) {
            return null;
        }
        return $m[1] . ':' . $m[2];
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
