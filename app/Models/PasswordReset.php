<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class PasswordReset
{
    /**
     * Encontra um token válido que ainda não expirou (válido por 1 hora).
     */
    public static function findValidToken($token)
    {
        $pdo = Database::getConnection();
        
        // Verifica se o token existe e se foi criado há menos de 1 hora
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$token]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria um novo registro de token de reset.
     * Agora recebe apenas email e token, já que o created_at é automático no banco.
     */
    public static function createToken($email, $token)
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token) VALUES (?, ?)");
            return $stmt->execute([$email, $token]);
        } catch (\Exception $e) {
            error_log("Erro ao criar token de reset: " . $e->getMessage());
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
        $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        return $stmt->execute([$email]);
    }
}