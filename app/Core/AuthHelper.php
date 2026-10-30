<?php

// Declara que esta classe pertence ao namespace App\Core
namespace App\Core;

use App\Models\User;

class AuthHelper
{

    public static function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se o usuário está logado.
     * Se não estiver, redireciona para a página de login.
     * Também lida com a troca forçada de senha.
     */
    public static function check()
    {
        // Garante que a sessão está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        //Verifica se está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_DIR_URL . '/login');
            exit;
        }

        // SEGURANÇA EXTRA: Verifica se o navegador mudou (Session Hijacking)
        // Se não tiver salvo o navegador na sessão ainda, salva agora (primeiro acesso)
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }

        // Se o navegador atual for diferente do navegador que criou a sessão, bloqueia a sessão
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            // Destrói a sessão suspeita
            session_unset();
            session_destroy();

            // Manda pro login
            header('Location: ' . BASE_DIR_URL . '/login?error=session_hijack');
            exit;
        }
    }

    public static function checkRememberMe()
    {
        // Se já está logado, não faz nada
        if (isset($_SESSION['user_id'])) {
            return;
        }

        // Se não tem cookie, não faz nada
        if (!isset($_COOKIE['remember_me'])) {
            return;
        }

        // Separa o selector e o validator do cookie COM SEGURANÇA
        $parts = explode(':', $_COOKIE['remember_me']);

        // SE O COOKIE ESTIVER QUEBRADO, NÃO FAZ NADA
        if (count($parts) !== 2) {
            return;
        }

        list($selector, $validator) = $parts;

        try {
            // Busca no banco pelo selector e se não expirou
            $sql = "SELECT * FROM user_tokens WHERE selector = ? AND expiry > NOW() LIMIT 1";
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute([$selector]);
            $token = $stmt->fetch();

            if ($token) {
                // Verifica se o validador bate com o hash do banco
                if (hash_equals($token['hashed_validator'], hash('sha256', $validator))) {

                    // SUCESSO! Loga o usuário automaticamente
                    $user = User::findById($token['user_id']);

                    // Verifica se o usuário ainda existe e está ativo
                    if ($user && ($user['status'] ?? 'active') === 'active') {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_avatar'] = $user['avatar_path'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Se der erro no banco, ignora silenciosamente e deixa o usuário logar manualmente
        }
    }

    /** Validade do "lembrar de mim". */
    private const REMEMBER_DAYS = 30;

    /**
     * Cria o token do "lembrar de mim" em user_tokens e grava o cookie "selector:validator".
     * No banco fica só o hash do validator.
     */
    public static function issueRememberToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $expiry = time() + self::REMEMBER_DAYS * 86400;

        try {
            $sql = "INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)";
            Database::getConnection()->prepare($sql)
                ->execute([$userId, $selector, hash('sha256', $validator), date('Y-m-d H:i:s', $expiry)]);
        } catch (\Exception $e) {
            error_log('Erro ao salvar o token do lembrar de mim: ' . $e->getMessage());
            return;
        }

        setcookie('remember_me', $selector . ':' . $validator, [
            'expires' => $expiry,
            'path' => '/',
            'secure' => session_get_cookie_params()['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Apaga o token do "lembrar de mim" deste navegador (logout).
     */
    public static function forgetRememberToken(): void
    {
        if (!isset($_COOKIE['remember_me'])) {
            return;
        }

        $selector = explode(':', $_COOKIE['remember_me'])[0];
        try {
            Database::getConnection()->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$selector]);
        } catch (\Exception $e) {
            error_log('Erro ao apagar o token do lembrar de mim: ' . $e->getMessage());
        }

        setcookie('remember_me', '', ['expires' => time() - 3600, 'path' => '/']);
        unset($_COOKIE['remember_me']);
    }

    /**
     * Verifica se o usuário logado é um administrador.
     */
    public static function checkAdmin()
    {
        self::check(); // Garante que o usuário está logado antes de checar o 'role'

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            // Renderiza uma view de acesso negado para uma aparência melhor
            // Se não tiver uma view, o echo é uma alternativa.
            ViewHelper::render('errors/403');
            exit;
        }
    }

    /**
     * Apenas VERIFICA se o usuário logado é um admin.
     * Esta é uma "check" (verificação) - ela retorna true/false, mas não bloqueia.
     * Útil para lógicas condicionais dentro de controllers.
     * * @return bool
     */
    public static function isAdmin(): bool
    {
        // Garante que a sessão existe e que o 'role' é 'admin'
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
