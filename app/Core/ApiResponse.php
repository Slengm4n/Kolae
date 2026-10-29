<?php

namespace App\Core;

/**
 * Class ApiResponse
 * Ponto único de saída da /api/v1. Todo retorno da API (sucesso, erro, 404,
 * exceção não tratada) passa por aqui para seguir o contrato de docs/api.md,
 * no padrão do Laravel (para o Kolae 2.0 herdar sem mudar o app):
 *
 *   { "data": ..., "meta": { ... } }                                  (meta só em listas paginadas)
 *   { "message": "...", "code": "...", "errors": { "campo": ["..."] } } (errors só em validação)
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

    /**
     * @param mixed      $data       Conteúdo da resposta (null quando não há conteúdo)
     * @param int        $statusCode Status HTTP
     * @param array|null $meta       Paginação: ['current_page', 'last_page', 'per_page', 'total']
     */
    public static function success($data = null, int $statusCode = 200, ?array $meta = null): void
    {
        $payload = ['data' => $data];
        if ($meta !== null) {
            $payload['meta'] = $meta;
        }
        self::send($payload, $statusCode);
    }

    /**
     * @param string     $message    Mensagem legível (pode ser exibida no app)
     * @param string     $code       Código estável em MAIÚSCULAS, para o app tratar (ex: INVALID_CREDENTIALS)
     * @param int        $statusCode Status HTTP
     * @param array|null $errors     Erros por campo. Aceita ['email' => 'msg'] ou ['email' => ['msg', ...]];
     *                               sempre sai como lista, como no Laravel.
     */
    public static function error(string $message, string $code = 'ERROR', int $statusCode = 400, ?array $errors = null): void
    {
        $payload = ['message' => $message, 'code' => $code];
        if (!empty($errors)) {
            $payload['errors'] = array_map(fn($msgs) => array_values((array)$msgs), $errors);
        }
        self::send($payload, $statusCode);
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
        // Números decimais com o menor número de dígitos (-23.5428, não -23.542799999999999...).
        // A produção (InfinityFree) usa serialize_precision = 17; o padrão do PHP é -1.
        ini_set('serialize_precision', '-1');
        echo json_encode($payload, self::JSON_FLAGS);
        exit;
    }
}
