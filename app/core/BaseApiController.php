<?php
namespace App\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Models\User;

abstract class BaseApiController {

    protected $userId = null;
    protected $userRole = null;

    public function __construct() {
        header('Content-Type: application/json; charset=utf-8'); 
        // Especifica UTF-8
        // Configuração CORS (Cross-Origin Resource Sharing) - Ajuste para produção!
        // '*' permite qualquer origem (bom para teste local), mas inseguro em produção.
        // Troque '*' pela URL exata do seu protótipo em produção (ex: 'https://prototipo.seusite.com')
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Allow-Credentials: true'); // Se precisar de cookies/sessão (JWT não precisa)
        header('Access-Control-Max-Age: 86400');    // Cache de preflight OPTIONS por 1 dia

        // Resposta automática para requisições OPTIONS (preflight CORS)
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    protected function sendSuccess($data = null, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); // Melhora a legibilidade e lida com acentos
        exit;
    }

    protected function sendError($message, $errorCode = null, $statusCode = 400) {
        http_response_code($statusCode);
        $errorPayload = ['message' => $message];
        if ($errorCode) {
            $errorPayload['code'] = $errorCode;
        }
        // Não logar o erro aqui, logar onde ele acontece (no try-catch)
        echo json_encode(['success' => false, 'error' => $errorPayload], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Verifica o token JWT enviado no cabeçalho Authorization.
     * Popula $this->userId e $this->userRole se o token for válido.
     * Envia erro 401 e termina a execução se for inválido.
     */
    protected function authenticateRequest() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null; // Algumas configs de servidor usam REDIRECT_

        if (!$authHeader) {
            $this->sendError('Token de autorização ausente.', 'AUTH_MISSING_TOKEN', 401);
        }

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->sendError('Formato de token inválido. Use: Bearer <token>', 'AUTH_INVALID_FORMAT', 401);
        }

        $jwt = $matches[1];
        if (!$jwt) {
            $this->sendError('Token não encontrado no cabeçalho.', 'AUTH_MISSING_TOKEN', 401);
        }

        try {
            if (!defined('JWT_SECRET')) {
                throw new \Exception('Chave JWT não configurada no servidor.'); // Erro de configuração
            }
            $secretKey = JWT_SECRET;
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            // Token válido!
            $this->userId = $decoded->user_id ?? null;
            $this->userRole = $decoded->role ?? null;

            if (!$this->userId) {
                 throw new \Exception('Payload do token inválido (sem user_id).');
            }

            // Opcional: Revalidar se o usuário ainda existe e está ativo
            $user = User::findById($this->userId);
            if (!$user || $user['status'] !== 'active') {
               $this->sendError('Usuário associado ao token é inválido ou inativo.', 'AUTH_INVALID_USER', 401);
            }


        } catch (ExpiredException $e) {
            $this->sendError('Token expirado. Faça login novamente.', 'AUTH_TOKEN_EXPIRED', 401);
        } catch (SignatureInvalidException $e) {
            $this->sendError('Assinatura de token inválida.', 'AUTH_SIGNATURE_INVALID', 401);
        } catch (\Exception $e) { // Captura outros erros do JWT ou a falta de JWT_SECRET
             error_log("Erro na autenticação JWT: " . $e->getMessage()); // Loga o erro real
            $this->sendError('Token inválido ou erro interno.', 'AUTH_INVALID_TOKEN', 401);
        }
    }
}