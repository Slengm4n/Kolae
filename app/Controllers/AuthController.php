<?php

namespace App\Controllers;

use App\Core\AuthHelper;
use App\Core\LoginThrottle;
use App\Core\Mailer;
use App\Core\ViewHelper;
use App\Models\User;
use DateTime;
use Exception;
use App\Models\PasswordReset;

class AuthController
{
    // Exibe o formulário de login
    public function index()
    {
        ViewHelper::render('auth/login');
    }

    // Processa login do usuário
    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Muitos erros seguidos: nem confere a senha (LoginThrottle)
            $wait = LoginThrottle::retryAfter($email);
            if ($wait !== null) {
                header('Location: ' . BASE_URL . '/login?error=too_many&minutes=' . LoginThrottle::minutes($wait));
                exit;
            }

            $user = User::findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                LoginThrottle::clear($email);
                
                // Impede fixação de sessão usando cookie antigo
                session_regenerate_id(true);
                
                // Inicia a sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_avatar'] = $user['avatar_path'];

                // "Lembrar de mim": token em user_tokens, lido por AuthHelper::checkRememberMe
                if (isset($_POST['remember-me'])) {
                    AuthHelper::issueRememberToken((int)$user['id']);
                }

                // Redireciona dependendo do nível de acesso
                $redirect_to = ($user['role'] === 'admin') ? '/admin' : '/dashboard';
                header('Location: ' . BASE_URL . $redirect_to);
                exit;
            } else {
                LoginThrottle::recordFailure($email);
                header('Location: ' . BASE_URL . '/login?error=credentials');
                exit;
            }
        }
    }

    // Exibe o formulário de registro
    public function register()
    {
        ViewHelper::render('auth/register');
    }

    // Processa o registro de um novo usuário
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Verifica se 'password' é igual a 'password_confirmation'
            if ($_POST['password'] !== $_POST['password_confirmation']) {
                header('Location: ' . BASE_URL . '/register?error=password_mismatch');
                exit;
            }

            if (mb_strlen($_POST['password'] ?? '') < User::PASSWORD_MIN_LENGTH) {
                header('Location: ' . BASE_URL . '/register?error=weak_password');
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

            // DADOS DO USUÁRIO ADAPTADOS:
            // Campo 'password' corrigido e 'role' alterado para 'player' (atendendo ao ENUM do banco)
            $userData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'birthdate' => $_POST['birthdate'],
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => 'player' 
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

    // Exibe o formulário "Esqueci minha senha"
    public function showForgotPasswordForm()
    {
        ViewHelper::render('auth/forgot-password');
    }

    // Processa a solicitação de redefinição de senha e envia o e-mail
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

        // Mesma resposta exista ou não a conta (não revela quem tem cadastro)
        if ($user) {
            Mailer::sendPasswordReset($email, $user['name']);
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

    // Processa a submissão da nova senha
    public function handleResetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $password_confirmation = $_POST['password_confirmation'] ?? '';

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
        if (mb_strlen($password) < User::PASSWORD_MIN_LENGTH) {
            header('Location: ' . BASE_URL . '/reset-password?token=' . urlencode($token) . '&error=weak_password');
            exit;
        }

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

    // Processa o logout do usuário
    public function logout()
    {
        AuthHelper::forgetRememberToken();

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

    // Função para verificar maioridade
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