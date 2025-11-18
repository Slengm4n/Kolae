<?php

namespace App\Controllers;

use App\Core\ViewHelper;
use App\Models\User;
use DateTime;
use Exception;
use App\Models\PasswordReset;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class AuthController
{
    /**
     * Exibe o formulário de login.
     */
    public function index()
    {
        ViewHelper::render('auth/login');
    }

    /**
     * Processa a tentativa de login.
     */
    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = User::findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                // 1. Inicia a sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_avatar'] = $user['avatar_path'];

                // 2. Lógica "Lembrar de mim"
                if (isset($_POST['remember-me'])) {
                    $selector = bin2hex(random_bytes(16));
                    $validator = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 dias
                    $hashedValidator = hash('sha256', $validator);

                    try {
                        $conn = \App\Core\Database::getConnection();
                        $sql = "INSERT INTO user_tokens (user_id, selector, hashed_validator, expiry) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute([$user['id'], $selector, $hashedValidator, $expiry]);

                        setcookie(
                            'remember_me',
                            $selector . ':' . $validator,
                            time() + (30 * 24 * 60 * 60),
                            '/',
                            '',
                            false, // Mude para true em produção com HTTPS
                            true   // HttpOnly
                        );
                    } catch (\Exception $e) {
                        error_log("Erro ao criar token remember me: " . $e->getMessage());
                    }
                }

                // 3. Redireciona
                $redirect_to = ($user['role'] === 'admin') ? '/admin' : '/dashboard';
                header('Location: ' . BASE_URL . $redirect_to);
                exit;
            } else {
                header('Location: ' . BASE_URL . '/login?error=credentials');
                exit;
            }
        }
    }

    /**
     * Exibe o formulário de registro.
     */
    public function register()
    {
        ViewHelper::render('auth/register');
    }

    /**
     * Processa o registro de um novo usuário.
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if ($_POST['password'] !== $_POST['password_confirmation']) {
                header('Location: ' . BASE_URL . '/register?error=password_mismatch');
                exit;
            }

            if (!$this->
