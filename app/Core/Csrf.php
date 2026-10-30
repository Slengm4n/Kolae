<?php

namespace App\Core;

/**
 * Class Csrf
 * Proteção contra CSRF nos formulários do site (S5). Um token por sessão, enviado no campo
 * escondido "_csrf" de todo formulário POST e conferido em index.php antes do roteador.
 * A API (/api/v1) fica de fora: ela autentica pelo cabeçalho Authorization, não por cookie.
 */
class Csrf
{
    public const FIELD = '_csrf';

    /**
     * Token da sessão atual (cria na primeira vez).
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Campo escondido para colocar dentro de cada <form method="POST">.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . self::token() . '">';
    }

    /**
     * Confere o token de um POST do site. Se faltar ou não bater, responde 403 com a página
     * "Página expirada" e para. (403 e não 419: o Apache troca status desconhecido por 500.)
     * Também cai aqui um envio maior que o post_max_size do PHP, porque o $_POST chega vazio.
     */
    public static function verifyOrFail(): void
    {
        $sent = $_POST[self::FIELD] ?? '';
        if (is_string($sent) && $sent !== '' && hash_equals(self::token(), $sent)) {
            return;
        }

        http_response_code(403);
        ViewHelper::render('errors/csrf');
        exit;
    }
}
