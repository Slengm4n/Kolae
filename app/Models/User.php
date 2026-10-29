<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    /** Tamanho mínimo de senha (API; ver docs/api.md). */
    public const PASSWORD_MIN_LENGTH = 8;

    /** Idade mínima para ter conta (mesma regra do cadastro no site). */
    public const MIN_AGE = 18;

    /**
     * WhatsApp do dono (D24) como o banco guarda: só dígitos, com DDI e DDD (5511999999999).
     * Aceita o número digitado de qualquer jeito ("(11) 99999-0000", "+55 11 ...").
     * Sem o 55, assume Brasil. Retorna null se não for um celular/fixo brasileiro válido.
     */
    public static function normalizeWhatsapp(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        // 55 + DDD (11 a 99) + 8 dígitos (fixo) ou 9 dígitos começando com 9 (celular)
        return preg_match('/^55[1-9][1-9](9\d{8}|[2-8]\d{7})$/', $digits) ? $digits : null;
    }

    /**
     * 5511999990000 → (11) 99999-0000, para mostrar no formulário.
     */
    public static function formatWhatsapp(?string $digits): string
    {
        if (!$digits || !preg_match('/^55(\d{2})(\d{4,5})(\d{4})$/', $digits, $m)) {
            return (string)$digits;
        }
        return "($m[1]) $m[2]-$m[3]";
    }

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

    /**
     * Verifica se o e-mail já está cadastrado, inclusive em contas inativas
     * (o findByEmail só enxerga contas ativas, e o e-mail é UNIQUE no banco).
     */
    public static function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = :email AND id != :exclude LIMIT 1");
        $stmt->execute([':email' => $email, ':exclude' => $excludeUserId ?? 0]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Data no formato YYYY-MM-DD, válida e com idade mínima de MIN_AGE anos.
     */
    public static function isValidAdultBirthdate(?string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', (string)$date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            return false;
        }
        return $d <= new \DateTimeImmutable('-' . self::MIN_AGE . ' years');
    }

    /**
     * Cria o usuário. Retorna o ID criado ou false.
     */
    public static function create(array $data)
    {
        $pdo = Database::getConnection();
        $data['status'] = 'active';
        $data['force_password_change'] = 1;

        $stmt = $pdo->prepare("INSERT INTO users (name, email, birthdate, password, role, status, force_password_change)
                               VALUES (:name, :email, :birthdate, :password, :role, :status, :force_password_change)");

        return $stmt->execute($data) ? (int)$pdo->lastInsertId() : false;
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