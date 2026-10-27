<?php

namespace App\Controllers;

use App\Core\BaseApiController;
use App\Models\User;
use Firebase\JWT\JWT;
use Exception;

class AuthApiController extends BaseApiController
{
    /** Validade do token JWT (8 horas). */
    private const TOKEN_TTL_SECONDS = 60 * 60 * 8;

    /**
     * POST /api/v1/auth/login
     * Body: { "email": "...", "password": "..." }
     * Contrato em docs/api.md.
     */
    public function login()
    {
        $input = $this->getJsonInput();

        $email = trim((string)($input['email'] ?? ''));
        $password = (string)($input['password'] ?? '');

        //Validação dos campos (erros por campo em error.fields)
        $fields = [];
        if ($email === '') {
            $fields['email'] = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fields['email'] = 'Formato de e-mail inválido.';
        }
        if ($password === '') {
            $fields['password'] = 'A senha é obrigatória.';
        }
        if ($fields) {
            $this->sendError('Verifique os campos informados.', 'VALIDATION_ERROR', 422, $fields);
        }

        try {
            //Busca o usuário pelo email (findByEmail só retorna usuários ativos)
            $user = User::findByEmail($email);

            // A coluna é "password" (hash bcrypt); antes lia "password_hash" e nenhum login funcionava
            if (!$user || !password_verify($password, $user['password'])) {
                $this->sendError('E-mail ou senha incorretos.', 'INVALID_CREDENTIALS', 401);
            }

            if (!defined('JWT_SECRET') || JWT_SECRET === '') {
                throw new Exception('Chave secreta JWT não está configurada no servidor.');
            }

            $issuedAt = time();
            $expiresAt = $issuedAt + self::TOKEN_TTL_SECONDS;

            $payload = [
                'iat' => $issuedAt,
                'exp' => $expiresAt,
                'user_id' => (int)$user['id'],
                'role' => $user['role']
            ];

            $jwt = JWT::encode($payload, JWT_SECRET, 'HS256');

            $this->sendSuccess([
                'token' => $jwt,
                'token_type' => 'Bearer',
                'expires_at' => date(DATE_ATOM, $expiresAt),
                'user' => self::publicUser($user)
            ]);
        } catch (Exception $e) {
            //Captura erros gerais (ex: falha na conexão com DB, erro ao gerar JWT)
            error_log("Erro no login da API: " . $e->getMessage());
            $this->sendError('Ocorreu um erro interno durante o login.', 'LOGIN_FAILED', 500);
        }
    }

    /**
     * Dados do usuário que podem ir para o app (nunca senha, tokens ou CNPJ).
     */
    private static function publicUser(array $user): array
    {
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar_url' => !empty($user['avatar_path'])
                ? BASE_URL . '/uploads/avatars/' . $user['id'] . '/' . $user['avatar_path']
                : null
        ];
    }
}
