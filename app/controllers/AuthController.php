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
                // Troca o ID da sessão para um novo e deleta o antigo.
                // Isso impede que alguém use um cookie antigo roubado.
                session_regenerate_id(true);
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

            // CORREÇÃO PRINCIPAL AQUI:
            // Verifica se 'password' é igual a 'password_confirmation' (que alteramos no HTML)
            if ($_POST['password'] !== $_POST['password_confirmation']) {
                header('Location: ' . BASE_URL . '/register?error=password_mismatch');
                exit;
            }

            if (!$this->isOver18($_POST['birthdate'])) {
                header('Location: ' . BASE_URL . '/register?error=underage');
                exit;
            }

            if (User::findByEmail($_POST['email'])) {
                header('Location: ' . BASE_URL . '/register?error=email_exists');
                exit;
            }

            $userData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'birthdate' => $_POST['birthdate'],
                'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => 'user'
            ];

            if (User::create($userData)) {
                header('Location: ' . BASE_URL . '/login?status=registered');
                exit;
            } else {
                header('Location: ' . BASE_URL . '/register?error=generic');
                exit;
            }
        }
    }

    /**
     * Exibe o formulário "Esqueci minha senha".
     */
    public function showForgotPasswordForm()
    {
        ViewHelper::render('auth/forgot-password');
    }

    /**
     * Processa a solicitação de redefinição de senha e envia o e-mail.
     */
    public function handleForgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/forgot-password');
            exit;
        }

        $email = $_POST['email'] ?? '';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . BASE_URL . '/forgot-password?error=invalid_email');
            exit;
        }

        $user = User::findByEmail($email);

        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $expires_at = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

                PasswordReset::deleteTokensForEmail($email);
                PasswordReset::createToken($email, $token, $expires_at);

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $user['name']);

                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de Senha - Kolae';
                $reset_link = BASE_URL . '/reset-password?token=' . $token;

                $mail->Body = "Olá " . htmlspecialchars($user['name']) . ",<br><br>"
                    . "Recebemos uma solicitação para redefinir sua senha no Kolae.<br>"
                    . "Clique no link abaixo para criar uma nova senha:<br><br>"
                    . "<a href='$reset_link' style='color: #06b6d4; text-decoration: none; font-weight: bold;'>Redefinir Minha Senha</a><br><br>"
                    . "Este link expira em 1 hora.";

                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }

        header('Location: ' . BASE_URL . '/forgot-password?status=sent');
        exit;
    }

    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';
        $resetRequest = PasswordReset::findValidToken($token);

        if (!$resetRequest) {
            header('Location: ' . BASE_URL . '/login?error=invalid_token');
            exit;
        }

        ViewHelper::render('auth/reset-password', ['token' => $token]);
    }

    /**
     * Processa a submissão da nova senha.
     */
    public function handleResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? ''; // Confirmação aqui também

        $resetRequest = PasswordReset::findValidToken($token);
        if (!$resetRequest) {
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=invalid_token');
            exit;
        }

        if (empty($password) || $password !== $password_confirmation) {
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=password_mismatch');
            exit;
        }

        $email = $resetRequest['email'];
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

        if (User::updatePassword($email, $newPasswordHash)) {
            PasswordReset::deleteTokensForEmail($email);
            header('Location: ' . BASE_URL . '/login?status=password_reset');
            exit;
        } else {
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=generic');
            exit;
        }
    }

    /**
     * Processa o logout do usuário.
     */
    public function logout()
    {
        if (isset($_COOKIE['remember_me'])) {
            $parts = explode(':', $_COOKIE['remember_me']);

            if (count($parts) === 2) {
                list($selector,) = $parts;
                try {
                    // Usa namespace completo para evitar erros
                    $conn = \App\Core\Database::getConnection();
                    $sql = "DELETE FROM user_tokens WHERE selector = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$selector]);
                } catch (\Exception $e) {
                    error_log("Erro ao limpar token de logout: " . $e->getMessage());
                }
            }
            setcookie('remember_me', '', time() - 3600, '/');
            unset($_COOKIE['remember_me']);
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    private function isOver18($birthdate): bool
    {
        if (empty($birthdate)) return false;
        try {
            $birthDateObj = new DateTime($birthdate);
            $today = new DateTime();
            $age = $today->diff($birthDateObj);
            return $age->y >= 18;
        } catch (Exception $e) {
            return false;
        }
    }
}
