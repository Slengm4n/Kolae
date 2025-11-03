<?php

namespace App\Controllers;

use App\Core\BaseApiController; // Importa a classe base que criámos
use App\Models\User;          // Importa o Model de Usuário existente
use Firebase\JWT\JWT;         // Importa a classe principal da biblioteca JWT     // Importa a classe Key para a chave secreta (necessário na versão 6+ da biblioteca)
use Exception;                // Para capturar erros genéricos

class AuthApiController extends BaseApiController
{

    /**
     * Processa a tentativa de login via API.
     * Endpoint: POST /api/v1/auth/login
     * Recebe: JSON {"email": "...", "password": "..."}
     * Retorna: JSON {"token": "..."} ou erro.
     */
    public function login()
    {
        // 1. Tenta obter os dados do corpo da requisição JSON
        $input = json_decode(file_get_contents('php://input'), true);

        // Verifica se o JSON foi decodificado corretamente
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->sendError('Corpo da requisição JSON inválido.', 'INVALID_JSON_BODY', 400);
            return; // Garante que a execução para aqui
        }

        $email = filter_var($input['email'] ?? null, FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? null;

        // 2. Validação básica dos dados recebidos
        if (empty($email) || empty($password)) {
            $this->sendError('Email e senha são obrigatórios.', 'MISSING_CREDENTIALS', 400);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Formato de email inválido.', 'INVALID_EMAIL_FORMAT', 400);
            return;
        }

        try {
            // 3. Busca o usuário pelo email
            $user = User::findByEmail($email); // Reutiliza o método do seu Model

            // 4. Verifica se o usuário existe e se a senha está correta
            if ($user && password_verify($password, $user['password_hash'])) { // Reutiliza a lógica de verificação

                // 5. Credenciais válidas -> Gerar o Token JWT
                if (!defined('JWT_SECRET')) {
                    throw new Exception('Chave secreta JWT não está configurada no servidor.'); // Erro de configuração
                }
                $secretKey = JWT_SECRET; // Pega a chave do config.php
                $issuedAt = time();
                $expire = $issuedAt + (60 * 60 * 8); // Token expira em 8 horas (ajuste conforme necessário)

                $payload = [
                    'iat' => $issuedAt,          // Timestamp: Quando o token foi emitido
                    'exp' => $expire,            // Timestamp: Quando o token expira
                    'user_id' => (int)$user['id'], // ID do usuário (importante converter para int)
                    'role' => $user['role']       // Papel/Função do usuário
                ];

                // Gera o token usando a chave e o algoritmo HS256
                $jwt = JWT::encode($payload, $secretKey, 'HS256');

                // 6. Envia a resposta de sucesso com o token
                $this->sendSuccess(['token' => $jwt]);
            } else {
                // Usuário não encontrado ou senha incorreta
                $this->sendError('Credenciais inválidas.', 'INVALID_CREDENTIALS', 401); // 401 Unauthorized
            }
        } catch (Exception $e) {
            // Captura erros gerais (ex: falha na conexão com DB, erro ao gerar JWT)
            error_log("Erro no login da API: " . $e->getMessage()); // Loga o erro real no servidor
            $this->sendError('Ocorreu um erro interno durante o login.', 'LOGIN_FAILED', 500); // 500 Internal Server Error
        }
    }

    // Você pode adicionar outros métodos relacionados à autenticação aqui se precisar no futuro
    // Ex: refresh token, forgot password API, etc.
}
