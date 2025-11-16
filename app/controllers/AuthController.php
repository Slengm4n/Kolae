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
    /*** Exibe o formulário de login.*/
    public function index()
    {
        // Renderiza a view usando a classe auxiliar
        ViewHelper::render('auth/login');
    }

    /*** Processa a tentativa de login.*/
    public function authenticate()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = User::findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Inicia a sessão e armazena os dados
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Redireciona com base na role
                $redirect_to = ($user['role'] === 'admin') ? '/admin' : '/dashboard';
                header('Location: ' . BASE_URL . $redirect_to);
                exit;
            } else {
                // Falha no login
                header('Location: ' . BASE_URL . '/login?error=credentials');
                exit;
            }
        }
    }

    /*** Exibe o formulário de registro.*/
    public function register()
    {
        ViewHelper::render('auth/register');
    }

    /*** Processa o registro de um novo usuário.*/
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // --- VALIDAÇÃO ---
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

            // --- CORREÇÃO APLICADA AQUI ---
            // Agrupe os dados em um array associativo
            $userData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'birthdate' => $_POST['birthdate'],
                'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'role' => 'user' // Define a role padrão
            ];

            // Passe o array único para o método create
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
        // Seu HTML que você já tem (vamos supor que está em 'auth/forgot-password')
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

        $user = User::findByEmail($email); // Lembre-se que o usuário precisa estar 'active'

        if ($user) {
            try {
                // 1. Gerar e armazenar o token
                $token = bin2hex(random_bytes(32));
                $expires_at = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

                PasswordReset::deleteTokensForEmail($email); // Invalida tokens antigos
                PasswordReset::createToken($email, $token, $expires_at);

                // 2. Enviar o e-mail com PHPMailer
                $mail = new PHPMailer(true);

                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // REMOVIDO
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = SMTP_USER;
                $mail->Password   = SMTP_PASS;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->Port       = SMTP_PORT;
                $mail->CharSet    = 'UTF-8';

                // Destinatários
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $user['name']);

                // Conteúdo
                $mail->isHTML(true);
                $mail->Subject = 'Recuperação de Senha - Kolae';
                $reset_link = BASE_URL . '/reset-password?token=' . $token;

                $mail->Body = "Olá " . htmlspecialchars($user['name']) . ",<br><br>"
                    . "Recebemos uma solicitação para redefinir sua senha no Kolae.<br>"
                    . "Clique no link abaixo para criar uma nova senha:<br><br>"
                    . "<a href='$reset_link' style='color: #06b6d4; text-decoration: none; font-weight: bold;'>Redefinir Minha Senha</a><br><br>"
                    . "Este link expira em 1 hora.<br><br>"
                    . "Se você não solicitou isso, ignore este e-mail.";

                $mail->send();

                // echo "E-mail enviado...!"; // REMOVIDO
            } catch (Exception $e) {
                // echo "<h1>Ocorreu um Erro!</h1>"; // REMOVIDO
                // var_dump($e->getMessage()); // REMOVIDO

                // Apenas logue o erro silenciosamente
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }

        // Resposta de segurança: SEMPRE mostre sucesso,
        // mesmo que o e-mail não exista, para evitar enumeração de usuários.
        header('Location: ' . BASE_URL . '/forgot-password?status=sent'); // REATIVADO
        exit; // REATIVADO
    }

    public function showResetForm()
    {
        $token = $_GET['token'] ?? '';

        // Verifica se o token é válido e não expirou
        $resetRequest = PasswordReset::findValidToken($token);

        if (!$resetRequest) {
            // Token inválido ou expirado
            header('Location: ' . BASE_URL . '/login?error=invalid_token');
            exit;
        }

        // Token é válido, mostre o formulário de reset
        // Passamos o token para a view para incluí-lo em um campo oculto
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
        $password_confirmation = $_POST['password_confirmation'] ?? '';

        // 1. Validar token novamente
        $resetRequest = PasswordReset::findValidToken($token);
        if (!$resetRequest) {
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=invalid_token');
            exit;
        }

        // 2. Validar senhas
        if (empty($password) || $password !== $password_confirmation) {
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=password_mismatch');
            exit;
        }

        // 3. Tudo OK - Atualizar o usuário
        $email = $resetRequest['email'];
        $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);

        if (User::updatePassword($email, $newPasswordHash)) {
            // 4. Sucesso! Invalidar o token
            PasswordReset::deleteTokensForEmail($email);

            // 5. Redirecionar para o login com mensagem de sucesso
            header('Location: ' . BASE_URL . '/login?status=password_reset');
            exit;
        } else {
            // Erro ao atualizar
            header('Location: ' . BASE_URL . '/reset-password?token=' . $token . '&error=generic');
            exit;
        }
    }

    /*** Processa o logout do usuário.*/
    public function logout()
    {
        // A sessão já deve ter sido iniciada no index.php
        session_destroy();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /**
     * Verifica se a data de nascimento corresponde a mais de 18 anos.
     * @param string $birthdate
     * @return bool
     */
    private function isOver18($birthdate): bool
    {
        if (empty($birthdate)) {
            return false;
        }
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
