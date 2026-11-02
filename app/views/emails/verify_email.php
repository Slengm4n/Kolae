<?php
/**
 * E-mail de confirmação do endereço (D58), sobre a base emails/layout.php.
 * Variáveis: $name, $link, $hours (validade do link).
 */
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$firstName = explode(' ', trim((string)$name))[0] ?: 'olá';
$emailTitle = 'Confirme seu e-mail';
$emailPreheader = 'Falta só um toque para confirmar seu e-mail no Kolae.';

ob_start();
?>
<h1 style="margin:0 0 16px; font-size:24px; line-height:1.3; color:#0b1220;">Confirme seu e-mail</h1>
<p style="margin:0 0 16px;">Olá, <?php echo $h($firstName); ?>!</p>
<p style="margin:0 0 28px;">Boas-vindas ao Kolae. Para confirmar que este e-mail é seu, toque no botão abaixo:</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
    <tr>
        <td align="center" style="border-radius:12px; background:#0069CC;">
            <a href="<?php echo $h($link); ?>" style="display:inline-block; padding:14px 32px; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:12px;">Confirmar e-mail</a>
        </td>
    </tr>
</table>
<p style="margin:0 0 16px; font-size:14px; color:#64748b;">O link vale por <strong style="color:#334155;"><?php echo (int)$hours; ?> horas</strong>. Se vencer, peça outro no seu painel.</p>
<p style="margin:0 0 24px; font-size:14px; color:#64748b;">Não criou uma conta no Kolae? Pode ignorar este e-mail.</p>
<p style="margin:0; padding-top:20px; border-top:1px solid #e2e8f0; font-size:12px; line-height:1.5; color:#94a3b8;">
    Se o botão não funcionar, copie e cole este endereço no navegador:<br>
    <a href="<?php echo $h($link); ?>" style="color:#0069CC; word-break:break-all;"><?php echo $h($link); ?></a>
</p>
<?php
$emailContent = ob_get_clean();
include BASE_PATH . '/app/views/emails/layout.php';
