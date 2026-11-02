<?php

namespace App\Core;

use App\Models\User;

/**
 * Confirmação de e-mail, versão leve (D58): ninguém é bloqueado; quem não confirmou vê um
 * aviso no painel. O link não precisa de tabela: leva o id, a validade e uma assinatura
 * (HMAC com o JWT_SECRET) que inclui o e-mail. Não dá para forjar, e o link perde a validade
 * sozinho se o e-mail da conta mudar.
 */
class EmailVerification
{
    /** Validade do link. */
    public const HOURS = 24;

    /** Espera entre dois reenvios pelo painel (mesma ideia do "esqueci a senha", D52). */
    public const RESEND_MINUTES = 5;

    private static function signature(int $id, string $email, int $expires): string
    {
        return hash_hmac('sha256', $id . '|' . strtolower($email) . '|' . $expires, JWT_SECRET);
    }

    /** Link de confirmação para a conta. */
    public static function link(array $user): string
    {
        $id = (int)$user['id'];
        $expires = time() + self::HOURS * 3600;
        return BASE_URL . '/verificar-email?' . http_build_query([
            'u' => $id,
            'e' => $expires,
            's' => self::signature($id, $user['email'], $expires),
        ]);
    }

    /**
     * Confere o link e, se valer, marca a conta como confirmada.
     * @return string 'ok', 'already' (já estava confirmada), 'expired' ou 'invalid'
     */
    public static function verify($id, $expires, $signature): string
    {
        if (!ctype_digit((string)$id) || !ctype_digit((string)$expires) || !is_string($signature)) {
            return 'invalid';
        }
        $user = User::findById((int)$id);
        if (!$user || !hash_equals(self::signature((int)$id, $user['email'], (int)$expires), $signature)) {
            return 'invalid';
        }
        if (!empty($user['email_verified_at'])) {
            return 'already';
        }
        if ((int)$expires < time()) {
            return 'expired';
        }
        return User::markEmailVerified((int)$id) ? 'ok' : 'invalid';
    }

    /**
     * Envia o e-mail de confirmação. Respeita a espera entre envios (guardada na sessão).
     * @return string 'sent', 'wait' ou 'failed'
     */
    public static function send(array $user): string
    {
        $last = $_SESSION['verify_sent_at'] ?? 0;
        if ($last && time() - $last < self::RESEND_MINUTES * 60) {
            return 'wait';
        }
        if (!Mailer::sendEmailVerification($user['email'], $user['name'] ?? '', self::link($user))) {
            return 'failed';
        }
        $_SESSION['verify_sent_at'] = time();
        return 'sent';
    }
}
