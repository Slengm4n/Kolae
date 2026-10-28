<?php

namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use App\Models\User;

abstract class BaseApiController
{
    /** Validade do token JWT (contrato: 30 dias, sem renovação). */
    protected const TOKEN_TTL_SECONDS = 60 * 60 * 24 * 30;

    protected $userId = null;
    protected $userRole = null;
    /** @var array|null Usuário do token, carregado por authenticateRequest() */
    protected $user = null;

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
        // CORS: o app mobile não precisa, mas permite testar a API pelo navegador
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Max-Age: 86400');    // Cache de preflight OPTIONS por 1 dia

        // Resposta automática para requisições OPTIONS (preflight CORS)
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    protected function sendSuccess($data = null, $statusCode = 200, ?array $meta = null)
    {
        ApiResponse::success($data, $statusCode, $meta);
    }

    /**
     * @param array|null $errors Erros por campo (validação), ex: ['email' => 'Obrigatório.']
     */
    protected function sendError($message, $errorCode = 'ERROR', $statusCode = 400, ?array $errors = null)
    {
        // Não logar o erro aqui, logar onde ele acontece (no try-catch)
        ApiResponse::error($message, $errorCode ?? 'ERROR', $statusCode, $errors);
    }

    /**
     * Responde 422 com os erros por campo, se houver algum.
     * @param array $errors ['campo' => 'mensagem', ...]
     */
    protected function failIfInvalid(array $errors): void
    {
        if ($errors) {
            $this->sendError('Verifique os campos informados.', 'VALIDATION_ERROR', 422, $errors);
        }
    }

    /**
     * Lê o corpo da requisição como JSON. Responde 400 se não for um objeto JSON válido.
     */
    protected function getJsonInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $this->sendError('Corpo da requisição deve ser um JSON válido.', 'INVALID_JSON_BODY', 400);
        }
        return $input;
    }

    /**
     * Gera o token JWT de um usuário e monta a resposta de login/cadastro.
     */
    protected function tokenResponse(array $user): array
    {
        if (!defined('JWT_SECRET') || JWT_SECRET === '') {
            throw new \RuntimeException('Chave secreta JWT não está configurada no servidor.');
        }

        $issuedAt = time();
        $expiresAt = $issuedAt + self::TOKEN_TTL_SECONDS;

        $jwt = JWT::encode([
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'user_id' => (int)$user['id'],
            'role' => $user['role']
        ], JWT_SECRET, 'HS256');

        return [
            'token' => $jwt,
            'token_type' => 'Bearer',
            'expires_at' => ApiFormat::fromTimestamp($expiresAt),
            'user' => ApiFormat::user($user)
        ];
    }

    /**
     * Verifica o token JWT enviado no cabeçalho Authorization.
     * Popula $this->user, $this->userId e $this->userRole se o token for válido.
     * Envia erro 401 e termina a execução se for inválido.
     */
    protected function authenticateRequest()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null; // Algumas configs de servidor usam REDIRECT_

        if (!$authHeader) {
            $this->sendError('Token de autorização ausente.', 'AUTH_MISSING_TOKEN', 401);
        }

        if (!preg_match('/^Bearer\s+(\S+)$/', trim($authHeader), $matches)) {
            $this->sendError('Formato de token inválido. Use: Bearer <token>', 'AUTH_INVALID_FORMAT', 401);
        }

        try {
            if (!defined('JWT_SECRET') || JWT_SECRET === '') {
                throw new \RuntimeException('Chave JWT não configurada no servidor.'); // Erro de configuração
            }
            $decoded = JWT::decode($matches[1], new Key(JWT_SECRET, 'HS256'));
        } catch (ExpiredException $e) {
            $this->sendError('Sessão expirada. Faça login novamente.', 'AUTH_TOKEN_EXPIRED', 401);
        } catch (\Throwable $e) { // Assinatura inválida, token malformado ou falta de JWT_SECRET
            error_log("Erro na autenticação JWT: " . $e->getMessage());
            $this->sendError('Token inválido. Faça login novamente.', 'AUTH_INVALID_TOKEN', 401);
        }

        $userId = (int)($decoded->user_id ?? 0);
        // Revalida se o usuário ainda existe e está ativo (o token pode ser anterior a uma desativação)
        $user = $userId ? User::findById($userId) : null;
        if (!$user || $user['status'] !== 'active') {
            $this->sendError('Usuário associado ao token é inválido ou inativo.', 'AUTH_INVALID_USER', 401);
        }

        $this->user = $user;
        $this->userId = (int)$user['id'];
        $this->userRole = $user['role'];
    }
}
