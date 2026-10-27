<?php

namespace App\Core;

/**
 * Class ApiResponse
 * Ponto único de saída da /api/v1. Todo retorno da API (sucesso, erro, 404,
 * exceção não tratada) passa por aqui para seguir o contrato de docs/api.md:
 *
 *   { "success": true,  "data": ... }
 *   { "success": false, "error": { "message": "...", "code": "...", "fields": { ... } } }
 */
class ApiResponse
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    /**
     * Indica se a requisição atual é para a API (/api/v1/...).
     */
    public static function isApiRequest(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return strpos($path, '/api/') !== false;
    }

    public static function success($data = null, int $statusCode = 200): void
    {
        self::send(['success' => true, 'data' => $data], $statusCode);
    }

    /**
     * @param string     $message    Mensagem legível (pode ser exibida no app)
     * @param string     $code       Código estável em MAIÚSCULAS, para o app tratar (ex: INVALID_CREDENTIALS)
     * @param int        $statusCode Status HTTP
     * @param array|null $fields     Erros por campo, ex: ['email' => 'Obrigatório.']
     */
    public static function error(string $message, string $code = 'ERROR', int $statusCode = 400, ?array $fields = null): void
    {
        $error = ['message' => $message, 'code' => $code];
        if (!empty($fields)) {
            $error['fields'] = $fields;
        }
        self::send(['success' => false, 'error' => $error], $statusCode);
    }

    /**
     * Garante que erros do PHP na API saiam como JSON, e não como HTML.
     * Chamado no index.php antes do dispatch, só para requisições da API.
     */
    public static function registerErrorHandlers(): void
    {
        // Warnings/notices impressos no meio da resposta quebram o JSON no app
        ini_set('display_errors', '0');

        set_exception_handler(function (\Throwable $e) {
            error_log('Erro não tratado na API: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
            self::error('Erro interno no servidor.', 'INTERNAL_ERROR', 500);
        });

        // Erros fatais não passam pelo exception handler
        register_shutdown_function(function () {
            $err = error_get_last();
            if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true) && !headers_sent()) {
                self::error('Erro interno no servidor.', 'INTERNAL_ERROR', 500);
            }
        });
    }

    private static function send(array $payload, int $statusCode): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, self::JSON_FLAGS);
        exit;
    }
}
