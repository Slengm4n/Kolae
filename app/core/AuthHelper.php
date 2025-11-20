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

        // 1. Verifica se está logado
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_DIR_URL . '/login');
            exit;
        }

        // 2. SEGURANÇA EXTRA: Verifica se o navegador mudou (Session Hijacking)
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

                    // Verifica se o usuário ainda existe (pode ter sido deletado)
                    if ($user) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_avatar'] = $user['avatar_path'];

                        // Opcional: Atualizar a data de expiração no banco para manter logado
                    }
                }
            }
        } catch (\Exception $e) {
            // Se der erro no banco, ignora silenciosamente e deixa o usuário logar manualmente
        }
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
