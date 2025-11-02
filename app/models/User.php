<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Class User
 * Gerencia todas as operações de banco de dados para a entidade de utilizador.
 */
class User
{
    // --- MÉTODOS DE CONTAGEM E BUSCA RECENTE ---

    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $query = "SELECT COUNT(id) FROM users WHERE status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function getRecent(int $limit): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT id, name, email, role, status, created_at 
                  FROM users 
                  WHERE status = 'active'
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- MÉTODOS DE CRUD ---

    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM users ORDER BY created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['cpf'])) {
            $user['cpf'] = self::decryptCpf($user['cpf']);
        }
        return $user;
    }

    public static function findByEmail(string $email)
    {
        $pdo = Database::getConnection();
        $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create(array $data)
    {
        $pdo = Database::getConnection();
        $data['status'] = 'active';
        $data['force_password_change'] = 1;

        $query = "INSERT INTO users (name, email, birthdate, password_hash, role, status, force_password_change) 
                  VALUES (:name, :email, :birthdate, :password_hash, :role, :status, :force_password_change)";

        $stmt = $pdo->prepare($query);
        return $stmt->execute($data);
    }

    public static function update(int $id, array $data): bool
    {
        if (isset($data['cpf'])) {
            $encryptedCpf = self::encryptCpf($data['cpf']);
            if ($encryptedCpf === false) {
                // Se a encriptação falhar, não continue para não salvar um valor inválido
                return false;
            }
            $data['cpf'] = $encryptedCpf;
        }

        if (empty($data)) {
            return true;
        }

        $pdo = Database::getConnection();
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = :$key";
        }
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $pdo->prepare($query);
        $data['id'] = $id;

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        return self::update($id, ['status' => 'inactive']);
    }

    // --- MÉTODOS DE CPF ---

    public static function isCpfInUse(string $cpf, int $excludeUserId): bool
    {
        $pdo = Database::getConnection();
        $query = "SELECT id, cpf FROM users WHERE id != :exclude_id AND cpf IS NOT NULL";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':exclude_id', $excludeUserId);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $decryptedCpf = self::decryptCpf($user['cpf']);
            if ($decryptedCpf === $cpf) {
                return true;
            }
        }
        return false;
    }

    // --- MÉTODOS DE SENHA ---

    public static function clearPasswordChangeFlag(int $userId): bool
    {
        return self::update($userId, ['force_password_change' => 0]);
    }

    public static function updatePassword($email, $newPasswordHash)
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            return $stmt->execute([$newPasswordHash, $email]);
        } catch (\Exception $e) {
            return false;
        }
    }

    // --- MÉTODOS DE CRIPTOGRAFIA (COM A CORREÇÃO FINAL) ---

    private static function getIvBinary()
    {
        if (!defined('ENCRYPTION_IV') || strlen(ENCRYPTION_IV) !== 32 || !ctype_xdigit(ENCRYPTION_IV)) {
            // Lançar um erro ou logar, pois o IV não é um hexadecimal de 32 caracteres.
            error_log("ENCRYPTION_IV não está definida ou não é um hexadecimal de 32 caracteres.");
            return false;
        }
        return hex2bin(ENCRYPTION_IV);
    }

    private static function encryptCpf(string $cpf)
    {
        $iv = self::getIvBinary();
        if ($iv === false || !extension_loaded('openssl') || !defined('ENCRYPTION_KEY')) {
            return false;
        }
        return openssl_encrypt($cpf, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    }

    private static function decryptCpf(?string $encrypted_cpf)
    {
        $iv = self::getIvBinary();
        if ($iv === false || empty($encrypted_cpf) || !extension_loaded('openssl') || !defined('ENCRYPTION_KEY')) {
            return null;
        }
        $decrypted = openssl_decrypt($encrypted_cpf, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
        return $decrypted === false ? null : $decrypted;
    }
}
