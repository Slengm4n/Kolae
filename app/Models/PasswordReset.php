<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PasswordReset
{
    /**
     * Encontra um token válido que ainda não expirou.
     */
    public static function findValidToken($token)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo registro de token de reset.
     */
    public static function createToken($email, $token, $expires_at)
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            return $stmt->execute([$email, $token, $expires_at]);
        } catch (\Exception $e) {
            // Lidar com o erro, talvez logar
            return false;
        }
    }

    /**
     * Deleta todos os tokens associados a um e-mail.
     * (Usado para invalidar tokens antigos ou após o reset).
     */
    public static function deleteTokensForEmail($email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        return $stmt->execute([$email]);
    }
}
