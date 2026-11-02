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
        // Limite (D52): um e-mail a cada PasswordReset::RESEND_MINUTES por conta. Quem chama
        // responde igual de qualquer jeito, para não revelar quem tem cadastro.
        if (PasswordReset::sentRecently($email)) {
            error_log('Esqueci a senha: pedido repetido em menos de ' . PasswordReset::RESEND_MINUTES . ' min; e-mail não reenviado.');
            return true;
        }

        try {
            $token = bin2hex(random_bytes(32));
            PasswordReset::deleteTokensForEmail($email);
            PasswordReset::createToken($email, $token);
            if (self::isTestAddress($email)) {
                error_log('Esqueci a senha: endereço de teste, e-mail não enviado (o token foi criado).');
                return true;
            }

            $mail = self::create();
            $mail->addAddress($email, $name);
            [$html, $text] = self::passwordResetMessage($name, BASE_URL . '/reset-password?token=' . $token);
            $mail->isHTML(true);
            $mail->Subject = 'Redefinir sua senha no Kolae';
            $mail->Body = $html;
            $mail->AltBody = $text;

            return $mail->send();
        } catch (MailException $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Erro ao preparar o e-mail de redefinição de senha: " . $e->getMessage());
        }
        return false;
    }

    /**
     * E-mail de redefinição de senha (D55): HTML no visual do Kolae e a versão em texto puro
     * (ajuda a não cair no spam e serve para quem lê e-mail sem HTML).
     * @return array [html, texto]
     */
    public static function passwordResetMessage(string $name, string $link): array
    {
        $minutes = 60; // validade do token (PasswordReset::findValidToken)
        ob_start();
        include BASE_PATH . '/app/views/emails/password_reset.php';
        $html = ob_get_clean();

        $firstName = explode(' ', trim($name))[0] ?: 'olá';
        $text = "Olá, $firstName!\n\n"
            . "Recebemos um pedido para redefinir a senha da sua conta no Kolae.\n"
            . "Para criar uma nova, abra este link:\n\n$link\n\n"
            . "O link vale por $minutes minutos e só pode ser usado uma vez.\n"
            . "Não foi você? Pode ignorar este e-mail: sua senha continua a mesma.\n\n"
            . "Kolae · " . preg_replace('#^https?://#', '', BASE_URL);
        return [$html, $text];
    }

    /**
     * E-mail de confirmação do endereço (D58). O link vem pronto de EmailVerification::link.
     * @return bool true se foi enviado; falhas vão para o log
     */
    public static function sendEmailVerification(string $email, string $name, string $link): bool
    {
        if (self::isTestAddress($email)) {
            error_log('Confirmação de e-mail: endereço de teste, não enviado.');
            return true;
        }
        try {
            $mail = self::create();
            $mail->addAddress($email, $name);
            [$html, $text] = self::verifyEmailMessage($name, $link);
            $mail->isHTML(true);
            $mail->Subject = 'Confirme seu e-mail no Kolae';
            $mail->Body = $html;
            $mail->AltBody = $text;
            return $mail->send();
        } catch (MailException $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
        } catch (\Throwable $e) {
            error_log("Erro ao preparar o e-mail de confirmação: " . $e->getMessage());
        }
        return false;
    }

    /**
     * E-mail de confirmação (D58): HTML no visual do Kolae e a versão em texto puro.
     * @return array [html, texto]
     */
    public static function verifyEmailMessage(string $name, string $link): array
    {
        $hours = EmailVerification::HOURS;
        ob_start();
        include BASE_PATH . '/app/views/emails/verify_email.php';
        $html = ob_get_clean();

        $firstName = explode(' ', trim($name))[0] ?: 'olá';
        $text = "Olá, $firstName!\n\n"
            . "Boas-vindas ao Kolae. Para confirmar que este e-mail é seu, abra este link:\n\n$link\n\n"
            . "O link vale por $hours horas. Se vencer, peça outro no seu painel.\n"
            . "Não criou uma conta no Kolae? Pode ignorar este e-mail.\n\n"
            . "Kolae · " . preg_replace('#^https?://#', '', BASE_URL);
        return [$html, $text];
    }

    /**
     * Domínios reservados para testes (RFC 2606/6761): nunca recebem e-mail de verdade.
     * Os testes criam contas como dono@teste.local; mandar para elas pelo Titan só geraria
     * devoluções e prejudicaria a reputação do remetente.
     */
    public static function isTestAddress(string $email): bool
    {
        return (bool)preg_match('/\.(local|test|example|invalid|localhost)$/i', trim($email));
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
