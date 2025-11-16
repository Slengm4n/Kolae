<?php

// Declara que esta classe pertence ao namespace App\Core
namespace App\Core;

class AuthHelper
{
    /**
     * Garante que a sessão PHP seja iniciada.
     */
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
        self::start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Verifica se o usuário precisa trocar a senha
        if (isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] == 1) {

            // Lista de URIs permitidas durante a troca de senha
            $allowed_uris = [
                BASE_URL . '/dashboard/perfil/seguranca',
                BASE_URL . '/dashboard/perfil/seguranca/atualizar'
                // Adicione outras rotas se necessário, como a de logout
            ];

            // Pega a URL atual sem query strings para uma verificação mais robusta
            $current_uri = strtok($_SERVER['REQUEST_URI'], '?');

            if (!in_array($current_uri, $allowed_uris)) {
                header('Location: ' . BASE_URL . '/dashboard/perfil/seguranca');
                exit;
            }
        }
    }

    /**
     * Verifica se o usuário logado é um administrador.
     * Esta função já executa self::check(), garantindo que o usuário está logado.
     */
    public static function checkAdmin()
    {
        self::check(); // Garante que o usuário está logado antes de checar o 'role'

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); // Código "Forbidden"
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
