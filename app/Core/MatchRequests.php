<?php

namespace App\Core;

use DateTimeImmutable;

/**
 * Regras dos pedidos de horário (D64), sem acesso ao banco (testes em tests/match_requests_test.php).
 * O jogador pede pelo app; o dono aprova ou recusa no painel. A agenda continua do dono (D02):
 * pedido pendente não bloqueia o horário, e o aprovado vira uma partida normal.
 *
 * Contrato em docs/api.md (POST /venues/{id}/requests); regras em docs/proposta-premium.md.
 */
class MatchRequests
{
    public const STATUSES = ['pending', 'approved', 'declined', 'cancelled', 'expired'];
    public const MESSAGE_MAX = 200;
    public const NOTE_MAX = 200;
    /** O horário pode ser pedido até este número de dias à frente. */
    public const MAX_DAYS_AHEAD = 30;
    public const BLOCK_MINUTES = 30;
    public const MIN_MINUTES = 30;
    public const MAX_MINUTES = 240;
    public const MAX_PENDING_PER_USER = 3;
    public const MAX_PENDING_PER_VENUE = 1;
    /** Pedido aprovado pode ser cancelado pelo jogador até esta antecedência. */
    public const CANCEL_APPROVED_HOURS = 2;

    /** Textos dos erros de validação para a API (o app mostra como vierem). */
    public const MESSAGES = [
        'start_time.required' => 'Informe o início do horário.',
        'start_time.invalid' => 'Use data e hora no formato 2026-11-12T19:00:00-03:00.',
        'end_time.required' => 'Informe o fim do horário.',
        'end_time.invalid' => 'Use data e hora no formato 2026-11-12T20:00:00-03:00.',
        'start_time.in_past' => 'Escolha um horário no futuro.',
        'start_time.too_far' => 'Dá para pedir horário até 30 dias à frente.',
        'start_time.not_block' => 'Os horários começam e terminam em blocos de 30 minutos (ex.: 19:00 ou 19:30).',
        'end_time.not_block' => 'Os horários começam e terminam em blocos de 30 minutos (ex.: 19:00 ou 19:30).',
        'end_time.end_before_start' => 'O fim precisa ser depois do início.',
        'end_time.different_day' => 'O horário precisa começar e terminar no mesmo dia.',
        'end_time.too_short' => 'O horário precisa ter pelo menos 30 minutos.',
        'end_time.too_long' => 'O horário pode ter no máximo 4 horas.',
        'start_time.closed_day' => 'A quadra não abre neste dia.',
        'start_time.outside_hours' => 'O horário está fora do funcionamento da quadra.',
        'start_time.overlap' => 'Este horário já está ocupado. Escolha uma faixa livre.',
        'sport_id.invalid' => 'Esporte inválido.',
        'sport_id.not_offered' => 'A quadra não tem este esporte.',
        'message.invalid' => 'A mensagem precisa ser um texto.',
        'message.too_long' => 'A mensagem pode ter até 200 caracteres.',
    ];

    /**
     * Data e hora do contrato (ISO 8601 com fuso, ex.: 2026-11-12T19:00:00-03:00), convertida
     * para o horário de Brasília. Aceita sem os segundos. null se inválida.
     */
    public static function parseDateTime($value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        foreach (['Y-m-d\TH:i:sP', 'Y-m-d\TH:iP'] as $format) {
            $dt = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($dt && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $dt->setTimezone(VenueSchedule::timezone());
            }
        }
        return null;
    }

    /**
     * Confere o pedido. $hours e $sameDayMatches vêm do banco (VenueHour e Game::findScheduledOn
     * do dia pedido); $venueSportIds são os esportes da quadra.
     * @return array{values: array, errors: array<string, string>} errors = campo => código
     */
    public static function validate(array $input, array $hours, array $sameDayMatches, array $venueSportIds, DateTimeImmutable $now): array
    {
        $errors = [];
        $start = self::parseDateTime($input['start_time'] ?? null);
        $end = self::parseDateTime($input['end_time'] ?? null);
        if (!isset($input['start_time']) || $input['start_time'] === '') {
            $errors['start_time'] = 'required';
        } elseif ($start === null) {
            $errors['start_time'] = 'invalid';
        }
        if (!isset($input['end_time']) || $input['end_time'] === '') {
            $errors['end_time'] = 'required';
        } elseif ($end === null) {
            $errors['end_time'] = 'invalid';
        }

        if ($start && $end) {
            $errors += self::validateTimes($start, $end, $hours, $sameDayMatches, $now);
        }

        $sportId = null;
        if (array_key_exists('sport_id', $input) && $input['sport_id'] !== null && $input['sport_id'] !== '') {
            $raw = $input['sport_id'];
            if (!(is_int($raw) || (is_string($raw) && ctype_digit($raw))) || (int)$raw < 1) {
                $errors['sport_id'] = 'invalid';
            } elseif (!in_array((int)$raw, array_map('intval', $venueSportIds), true)) {
                $errors['sport_id'] = 'not_offered';
            } else {
                $sportId = (int)$raw;
            }
        }

        $message = null;
        if (array_key_exists('message', $input) && $input['message'] !== null) {
            if (!is_string($input['message'])) {
                $errors['message'] = 'invalid';
            } else {
                $message = trim(preg_replace('/\s+/u', ' ', $input['message']));
                if (mb_strlen($message) > self::MESSAGE_MAX) {
                    $errors['message'] = 'too_long';
                }
                $message = $message === '' ? null : $message;
            }
        }

        return [
            'values' => $errors ? [] : [
                'start_time' => $start->format('Y-m-d H:i:s'),
                'end_time' => $end->format('Y-m-d H:i:s'),
                'sport_id' => $sportId,
                'message' => $message,
            ],
            'errors' => $errors,
        ];
    }

    /**
     * Regras do horário: blocos de 30 min, 30 min a 4 h, no futuro, até 30 dias, dentro do
     * funcionamento e sem conflito (as duas últimas, as mesmas da agenda do dono).
     * Também usada na aprovação, para conferir o conflito de novo.
     * @return array<string, string> campo => código
     */
    public static function validateTimes(DateTimeImmutable $start, DateTimeImmutable $end, array $hours, array $sameDayMatches, DateTimeImmutable $now): array
    {
        $errors = [];
        if (!self::onBlock($start)) {
            $errors['start_time'] = 'not_block';
        }
        if (!self::onBlock($end)) {
            $errors['end_time'] = 'not_block';
        }
        if ($end <= $start) {
            return $errors + ['end_time' => 'end_before_start'];
        }
        if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
            return $errors + ['end_time' => 'different_day'];
        }
        if ($start <= $now) {
            return $errors + ['start_time' => 'in_past'];
        }
        if ($start > $now->modify('+' . self::MAX_DAYS_AHEAD . ' days')) {
            return $errors + ['start_time' => 'too_far'];
        }
        $minutes = (int)(($end->getTimestamp() - $start->getTimestamp()) / 60);
        if ($minutes < self::MIN_MINUTES) {
            $errors += ['end_time' => 'too_short'];
        } elseif ($minutes > self::MAX_MINUTES) {
            $errors += ['end_time' => 'too_long'];
        }
        if ($errors) {
            return $errors;
        }
        $schedule = VenueSchedule::validateMatch($hours, $sameDayMatches, $start, $end, $now);
        if ($schedule !== null) {
            $errors['start_time'] = $schedule; // closed_day | outside_hours | overlap
        }
        return $errors;
    }

    /** Texto do erro para a API ("campo.código"). */
    public static function message(string $field, string $code): string
    {
        return self::MESSAGES[$field . '.' . $code] ?? 'Valor inválido.';
    }

    /**
     * O jogador pode cancelar? Pendente: sempre. Aprovado: até 2 horas antes do início.
     * Recusado, cancelado ou expirado: não.
     */
    public static function canCancel(array $request, DateTimeImmutable $now): bool
    {
        $status = $request['status'] ?? '';
        if ($status === 'pending') {
            return true;
        }
        if ($status !== 'approved') {
            return false;
        }
        $start = new DateTimeImmutable($request['start_time'], VenueSchedule::timezone());
        return $start->getTimestamp() - $now->getTimestamp() >= self::CANCEL_APPROVED_HOURS * 3600;
    }

    /** Pendente que chegou ao início do horário sem resposta: expira sozinho. */
    public static function isDue(array $request, DateTimeImmutable $now): bool
    {
        return ($request['status'] ?? '') === 'pending'
            && new DateTimeImmutable($request['start_time'], VenueSchedule::timezone()) <= $now;
    }

    /** Dois horários se sobrepõem? (encostar não conta: 19:00–20:00 e 20:00–21:00 cabem) */
    public static function overlaps(array $a, array $b): bool
    {
        return strtotime($a['start_time']) < strtotime($b['end_time']) && strtotime($a['end_time']) > strtotime($b['start_time']);
    }

    /**
     * Limite de pedidos pendentes. null se pode pedir; senão o motivo ('user' ou 'venue').
     */
    public static function limitReached(int $pendingByUser, int $pendingAtVenue): ?string
    {
        if ($pendingAtVenue >= self::MAX_PENDING_PER_VENUE) {
            return 'venue';
        }
        if ($pendingByUser >= self::MAX_PENDING_PER_USER) {
            return 'user';
        }
        return null;
    }

    /** Motivo da recusa (opcional): uma linha, até 200 caracteres; vazio = null. */
    public static function cleanNote($input): array
    {
        if ($input === null || $input === '') {
            return [null, null];
        }
        if (!is_string($input)) {
            return [null, 'invalid'];
        }
        $note = trim(preg_replace('/\s+/u', ' ', $input));
        if (mb_strlen($note) > self::NOTE_MAX) {
            return [null, 'too_long'];
        }
        return [$note === '' ? null : $note, null];
    }

    /** Primeiro nome de quem pediu (o dono não vê o nome completo nem o e-mail). */
    public static function firstName(?string $name): string
    {
        $name = trim((string)$name);
        return $name === '' ? '' : (preg_split('/\s+/u', $name)[0]);
    }

    /** Hora no múltiplo de 30 minutos, sem segundos. */
    private static function onBlock(DateTimeImmutable $dt): bool
    {
        return (int)$dt->format('s') === 0 && (int)$dt->format('i') % self::BLOCK_MINUTES === 0;
    }
}
