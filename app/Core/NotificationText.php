<?php

namespace App\Core;

/**
 * Título e texto de uma notificação (D64), montados no idioma de quem lê. O banco guarda só o
 * tipo e os dados (`notifications.data`, JSON com ids e textos curtos), nunca o texto pronto.
 * Usado pela API (GET /me/notifications, pelo Accept-Language) e pelo sino do painel do dono.
 */
class NotificationText
{
    /** Tipos com texto próprio; os outros caem no texto genérico. */
    public const TYPES = ['request_new', 'request_approved', 'request_declined', 'request_cancelled', 'request_expired', 'ad_removed'];

    /**
     * @param array $notification linha de notifications (type e data em JSON ou array)
     * @param array $lang textos do idioma (lang/*.php)
     * @return array{title: string, body: string}
     */
    public static function render(array $notification, array $lang): array
    {
        $type = (string)($notification['type'] ?? '');
        $data = $notification['data'] ?? [];
        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }
        if (!in_array($type, self::TYPES, true)) {
            return ['title' => $lang['notif_generic_title'] ?? 'Kolae', 'body' => $lang['notif_generic_body'] ?? ''];
        }

        $vars = [
            '{venue}' => (string)($data['venue_name'] ?? ''),
            '{name}' => (string)($data['user_name'] ?? ''),
            '{date}' => self::date($data['start_time'] ?? null),
            '{start}' => self::time($data['start_time'] ?? null),
            '{end}' => self::time($data['end_time'] ?? null),
            '{title}' => (string)($data['ad_title'] ?? ''),
            '{reason}' => (string)($data['reason'] ?? $data['response_note'] ?? ''),
        ];
        $bodyKey = 'notif_' . $type . '_body';
        // Recusa ou remoção com motivo têm um texto que mostra o motivo
        if (in_array($type, ['request_declined', 'ad_removed'], true) && $vars['{reason}'] !== '') {
            $bodyKey .= '_reason';
        }
        return [
            'title' => $lang['notif_' . $type . '_title'] ?? $type,
            'body' => strtr($lang[$bodyKey] ?? '', $vars),
        ];
    }

    /** Idioma do cabeçalho Accept-Language (pt-BR, en-US...) para o código de lang/ (pt-br). */
    public static function langFromHeader(?string $header): string
    {
        $available = ['pt-br', 'en-us', 'es-es', 'it-it', 'hi-in', 'ja-jp', 'zh-cn'];
        foreach (explode(',', strtolower((string)$header)) as $part) {
            $code = trim(explode(';', $part)[0]);
            if (in_array($code, $available, true)) {
                return $code;
            }
            foreach ($available as $full) {
                if ($code !== '' && explode('-', $full)[0] === explode('-', $code)[0]) {
                    return $full; // "en" ou "en-GB" -> en-us
                }
            }
        }
        return 'pt-br';
    }

    private static function date(?string $dt): string
    {
        return $dt ? date('d/m', strtotime($dt)) : '';
    }

    private static function time(?string $dt): string
    {
        return $dt ? date('H:i', strtotime($dt)) : '';
    }
}
