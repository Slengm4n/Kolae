<?php
/**
 * E-mail de redefinição de senha (D55), sobre a base emails/layout.php.
 * Variáveis: $name, $link, $minutes (validade do link).
 */
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$firstName = explode(' ', trim((string)$name))[0] ?: 'olá';
$emailTitle = 'Redefinir sua senha';
$emailPreheader = 'Use o botão para criar uma nova senha. O link vale por ' . $minutes . ' minutos.';

ob_start();
?>
<h1 style="margin:0 0 16px; font-size:24px; line-height:1.3; color:#0b1220;">Redefinir sua senha</h1>
<p style="margin:0 0 16px;">Olá, <?php echo $h($firstName); ?>!</p>
<p style="margin:0 0 28px;">Recebemos um pedido para redefinir a senha da sua conta no Kolae. Toque no botão abaixo para criar uma nova:</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
    <tr>
        <td align="center" style="border-radius:12px; background:#0084FF;">
            <a href="<?php echo $h($link); ?>" style="display:inline-block; padding:14px 32px; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:12px;">Criar nova senha</a>
        </td>
    </tr>
</table>
<p style="margin:0 0 16px; font-size:14px; color:#64748b;">O link vale por <strong style="color:#334155;"><?php echo (int)$minutes; ?> minutos</strong> e só pode ser usado uma vez.</p>
<p style="margin:0 0 24px; font-size:14px; color:#64748b;">Não foi você? Pode ignorar este e-mail: sua senha continua a mesma.</p>
<p style="margin:0; padding-top:20px; border-top:1px solid #e2e8f0; font-size:12px; line-height:1.5; color:#94a3b8;">
    Se o botão não funcionar, copie e cole este endereço no navegador:<br>
    <a href="<?php echo $h($link); ?>" style="color:#0069CC; word-break:break-all;"><?php echo $h($link); ?></a>
</p>
<?php
$emailContent = ob_get_clean();
include BASE_PATH . '/app/views/emails/layout.php';
