<?php

namespace App\Core;

/**
 * Class Router
 * Um roteador simples que mapeia URLs para controllers e métodos.
 */
class Router
{
    private $routes = [];
    private $prefix = '';

    /**
     * Adiciona uma rota para o método GET.
     * @param string $path
     * @param mixed $handler
     */
    public function get(string $path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Adiciona uma rota para o método POST.
     * @param string $path
     * @param mixed $handler
     */
    public function post(string $path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Adiciona uma rota ao array de rotas, considerando o prefixo do grupo.
     * @param string $method
     * @param string $path
     * @param mixed $handler
     */
    private function addRoute(string $method, string $path, $handler)
    {
        // Garante que o caminho completo comece com uma barra
        $fullPath = rtrim($this->prefix, '/') . '/' . ltrim($path, '/');
        // Garante que a rota final não tenha uma barra no final, a menos que seja a raiz
        if (strlen($fullPath) > 1) {
            $fullPath = rtrim($fullPath, '/');
        }

        $this->routes[strtoupper($method)][$fullPath] = $handler;
    }

    /**
     * Agrupa rotas sob um prefixo comum.
     * @param string $prefix
     * @param callable $callback
     */
    public function group(string $prefix, callable $callback)
    {
        $previousPrefix = $this->prefix;
        $this->prefix .= $prefix;
        call_user_func($callback, $this);
        $this->prefix = $previousPrefix;
    }

    /**
     * Encontra a rota correspondente à requisição atual e a executa.
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove a pasta base (BASE_DIR_URL) da URI
        $uri = '/';
        if (defined('BASE_DIR_URL') && !empty(BASE_DIR_URL) && strpos($requestUri, BASE_DIR_URL) === 0) {
            // Se rodando no localhost, remove o /colae
            $uri = substr($requestUri, strlen(BASE_DIR_URL));
        } else {
            // Se rodando na produção (ou se BASE_DIR_URL for vazio), a URI é o próprio requestUri
            $uri = $requestUri;
        }

        // Normaliza a URI
        if (empty($uri) || $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        if (strlen($uri) > 1) {
            $uri = rtrim($uri, '/');
        }

        // Tenta encontrar uma rota estática primeiro
        if (isset($this->routes[$method][$uri])) {
            $this->executeHandler($this->routes[$method][$uri]);
            return;
        }

        // Procura por rotas com parâmetros dinâmicos
        if (!empty($this->routes[$method])) {
            foreach ($this->routes[$method] as $routePath => $handler) {
                if (strpos($routePath, '{') !== false) {
                    $pattern = preg_replace('/\\{([a-zA-Z0-9_]+)\\}/', '(?P<$1>[^/]+)', $routePath);
                    $pattern = '#^' . $pattern . '$#';

                    if (preg_match($pattern, $uri, $matches)) {
                        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                        $this->executeHandler($handler, $params);
                        return;
                    }
                }
            }
        }

        $this->sendNotFound();
    }

    /**
     * Executa o handler (controller/método) de uma rota.
     * @param mixed $handler
     * @param array $params
     */
    private function executeHandler($handler, array $params = [])
    {
        if (is_array($handler) && isset($handler[0]) && class_exists($handler[0])) {
            $controller = new $handler[0]();
            $methodName = $handler[1];

            if (method_exists($controller, $methodName)) {
                call_user_func_array([$controller, $methodName], $params);
            } else {
                $this->sendNotFound("Método '{$methodName}' não encontrado no controller '{$handler[0]}'.");
            }
        } else {
            $this->sendNotFound("Handler da rota não é válido.");
        }
    }

    /**
     * Envia uma resposta 404 Not Found.
     * @param string $message
     */
    private function sendNotFound(string $message = "Página não encontrada")
    {
        http_response_code(404);
        // Pode criar uma view bonita para o 404 aqui
        echo "<h1>404 - {$message}</h1>";
    }
}
