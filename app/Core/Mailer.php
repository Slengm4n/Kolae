<?php

namespace App\Core;

use App\Models\PasswordReset;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/**
 * Class Mailer
 * Envio de e-mails do Kolae (PHPMailer + constantes SMTP_* do config.php).
 * Usado pelo site e pela API, para o texto e as regras ficarem num lugar só.
 */
class Mailer
{
    /**
     * Gera um token de redefinição de senha (válido por 1 hora, ver PasswordReset)
     * e envia o link para o e-mail. Apaga tokens anteriores desse e-mail.
     *
     * @return bool true se o e-mail foi enviado. Falhas vão para o log; quem chama
     *              deve responder igual em qualquer caso, para não revelar quem tem conta.
     */
    public static function sendPasswordReset(string $email, string $name): bool
    {
        try {
            $token = bin2hex(random_bytes(32));
            PasswordReset::deleteTokensForEmail($email);
            PasswordReset::createToken($email, $token);

            $mail = self::create();
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha - Kolae';

            $resetLink = BASE_URL . '/reset-password?token=' . $token;
            $mail->Body = "Olá " . htmlspecialchars($name) . ",<br><br>"
                . "Recebemos uma solicitação para redefinir sua senha no Kolae.<br>"
                . "Clique no link abaixo para criar uma nova senha:<br><br>"
                . "<a href='$resetLink' style='color: #06b6d4; text-decoration: none; font-weight: bold;'>Redefinir Minha Senha</a><br><br>"
                . "Este link expira em 1 hora.";

            return $mail->send();
        } catch (MailException $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Erro ao preparar o e-mail de redefinição de senha: " . $e->getMessage());
        }
        return false;
    }

    /**
     * PHPMailer configurado com o SMTP do config.php.
     */
    private static function create(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = 0; // Nunca expor o diálogo SMTP (credenciais) na resposta
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        return $mail;
    }
}
