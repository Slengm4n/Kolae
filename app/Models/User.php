<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function countAll(): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(id) FROM users WHERE status = 'active'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function getRecent(int $limit): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, role, status, created_at 
                               FROM users 
                               WHERE status = 'active'
                               ORDER BY created_at DESC 
                               LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['cnpj'])) {
            $user['cnpj'] = self::decryptCnpj($user['cnpj']);
        }
        return $user;
    }

    public static function findByEmail(string $email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND status = 'active'");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): bool
    {
        $pdo = Database::getConnection();
        $data['status'] = 'active';
        $data['force_password_change'] = 1;

        $stmt = $pdo->prepare("INSERT INTO users (name, email, birthdate, password, role, status, force_password_change) 
                               VALUES (:name, :email, :birthdate, :password, :role, :status, :force_password_change)");

        return $stmt->execute($data);
    }

    public static function update(int $id, array $data): bool
    {
        if (isset($data['cnpj'])) {
            $encrypted = self::encryptCnpj($data['cnpj']);
            if ($encrypted === false) {
                return false;
            }
            $data['cnpj'] = $encrypted;
        }

        if (empty($data)) {
            return true;
        }

        $pdo = Database::getConnection();
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "$key = :$key";
        }

        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id");
        $data['id'] = $id;

        return $stmt->execute($data);
    }

    public static function delete(int $id): bool
    {
        return self::update($id, ['status' => 'inactive']);
    }

    public static function isCnpjInUse(string $cnpj, int $excludeUserId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, cnpj FROM users WHERE id != :exclude_id AND cnpj IS NOT NULL");
        $stmt->bindParam(':exclude_id', $excludeUserId);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            if (self::decryptCnpj($user['cnpj']) === $cnpj) {
                return true;
            }
        }
        return false;
    }

    public static function clearPasswordChangeFlag(int $userId): bool
    {
        return self::update($userId, ['force_password_change' => 0]);
    }

    public static function updatePassword(string $email, string $newPasswordHash): bool
    {
        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            return $stmt->execute([$newPasswordHash, $email]);
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function getIvBinary()
    {
        if (!defined('ENCRYPTION_IV') || strlen(ENCRYPTION_IV) !== 32 || !ctype_xdigit(ENCRYPTION_IV)) {
            error_log("ENCRYPTION_IV não está definida ou não é um hexadecimal de 32 caracteres.");
            return false;
        }
        return hex2bin(ENCRYPTION_IV);
    }

    private static function encryptCnpj(string $cnpj)
    {
        $iv = self::getIvBinary();
        if ($iv === false || !extension_loaded('openssl') || !defined('ENCRYPTION_KEY')) {
            return false;
        }
        return openssl_encrypt($cnpj, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    }

    private static function decryptCnpj(?string $encryptedCnpj)
    {
        $iv = self::getIvBinary();
        if ($iv === false || empty($encryptedCnpj) || !extension_loaded('openssl') || !defined('ENCRYPTION_KEY')) {
            return null;
        }
        $decrypted = openssl_decrypt($encryptedCnpj, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
        return $decrypted === false ? null : $decrypted;
    }
}