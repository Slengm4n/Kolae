<?php

namespace App\Core;

/**
 * Class ApiFormat
 * Converte valores do banco para os tipos do contrato da API (docs/api.md):
 * datas em ISO 8601 com fuso e o objeto "user" público, igual em todos os endpoints.
 */
class ApiFormat
{
    private const TIMEZONE = 'America/Sao_Paulo';

    /**
     * Data e hora do banco ("2026-10-28 20:00:00") → ISO 8601 com fuso ("2026-10-28T20:00:00-03:00").
     * O Database fixa a sessão do MySQL em -03:00, então o valor lido já está no horário de Brasília.
     */
    public static function datetime(?string $value): ?string
    {
        if ($value === null || $value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }
        return (new \DateTimeImmutable($value, new \DateTimeZone(self::TIMEZONE)))->format(DATE_ATOM);
    }

    /**
     * Timestamp Unix → ISO 8601 com fuso.
     */
    public static function fromTimestamp(int $timestamp): string
    {
        return (new \DateTimeImmutable('@' . $timestamp))
            ->setTimezone(new \DateTimeZone(self::TIMEZONE))
            ->format(DATE_ATOM);
    }

    /**
     * Dados do usuário que podem ir para o app (nunca senha, tokens ou CNPJ).
     */
    public static function user(array $user): array
    {
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'birthdate' => $user['birthdate'] ?: null,
            'role' => $user['role'],
            'avatar_url' => !empty($user['avatar_path'])
                ? BASE_URL . '/uploads/avatars/' . $user['id'] . '/' . $user['avatar_path']
                : null,
            'created_at' => self::datetime($user['created_at'] ?? null)
        ];
    }
}
