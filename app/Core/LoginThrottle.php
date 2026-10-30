<?php

namespace App\Core;

/**
 * Class LoginThrottle
 * Limite de tentativas de login erradas (S5), usado pelo site e pela API.
 * Regras, numa janela de 15 minutos: 5 erros na mesma conta a partir do mesmo IP,
 * ou 20 erros do mesmo IP em qualquer conta. Login certo zera os erros daquela conta e IP.
 * Tabela login_attempts (patch 005). Se ela ainda não existir, não bloqueia ninguém.
 */
class LoginThrottle
{
    public const MAX_PER_ACCOUNT = 5;
    public const MAX_PER_IP = 20;
    public const WINDOW_MINUTES = 15;

    /**
     * Segundos até poder tentar de novo, ou null se a tentativa está liberada.
     */
    public static function retryAfter(string $email): ?int
    {
        try {
            $pdo = Database::getConnection();
            $window = self::WINDOW_MINUTES;
            $base = "SELECT COUNT(*) AS total, TIMESTAMPDIFF(SECOND, MIN(created_at), NOW()) AS age
                     FROM login_attempts WHERE created_at > NOW() - INTERVAL $window MINUTE";

            $stmt = $pdo->prepare("$base AND email_hash = ? AND ip = ?");
            $stmt->execute([self::emailHash($email), self::ip()]);
            $account = $stmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("$base AND ip = ?");
            $stmt->execute([self::ip()]);
            $byIp = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('LoginThrottle: ' . $e->getMessage());
            return null;
        }

        $wait = null;
        foreach ([[$account, self::MAX_PER_ACCOUNT], [$byIp, self::MAX_PER_IP]] as [$row, $max]) {
            if ((int)$row['total'] >= $max) {
                // Libera quando o erro mais antigo da janela vence
                $seconds = max(1, $window * 60 - (int)$row['age']);
                $wait = max($wait ?? 0, $seconds);
            }
        }
        return $wait;
    }

    /**
     * Registra um login errado.
     */
    public static function recordFailure(string $email): void
    {
        try {
            $pdo = Database::getConnection();
            $pdo->prepare("INSERT INTO login_attempts (email_hash, ip) VALUES (?, ?)")
                ->execute([self::emailHash($email), self::ip()]);
            // Limpeza: o que passou de um dia não serve para nada
            $pdo->exec("DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY");
        } catch (\Exception $e) {
            error_log('LoginThrottle: ' . $e->getMessage());
        }
    }

    /**
     * Login certo: apaga os erros desta conta neste IP.
     */
    public static function clear(string $email): void
    {
        try {
            Database::getConnection()->prepare("DELETE FROM login_attempts WHERE email_hash = ? AND ip = ?")
                ->execute([self::emailHash($email), self::ip()]);
        } catch (\Exception $e) {
            error_log('LoginThrottle: ' . $e->getMessage());
        }
    }

    /**
     * Minutos para mostrar na mensagem (arredonda para cima).
     */
    public static function minutes(int $seconds): int
    {
        return (int)ceil($seconds / 60);
    }

    /** O e-mail não fica no banco em texto: só o hash, em minúsculas. */
    private static function emailHash(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    private static function ip(): string
    {
        return substr($_SERVER['REMOTE_ADDR'] ?? 'desconhecido', 0, 45);
    }
}
