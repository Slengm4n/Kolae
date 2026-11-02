<?php
/**
 * Base dos e-mails do Kolae (D55): faixa azul com o nome, cartão branco e rodapé.
 * Feito com tabelas e estilos inline, porque Gmail e Outlook ignoram boa parte do CSS moderno.
 *
 * Variáveis: $emailTitle (título do e-mail), $emailPreheader (resumo que aparece na lista
 *            da caixa de entrada), $emailContent (HTML do miolo).
 */
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$site = preg_replace('#^https?://#', '', BASE_URL);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="light">
<title><?php echo $h($emailTitle); ?></title>
</head>
<body style="margin:0; padding:0; background:#f1f4f8; font-family:Arial, Helvetica, sans-serif; color:#0b1220;">
<!-- Resumo que aparece na lista de e-mails (escondido no corpo) -->
<div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#f1f4f8;"><?php echo $h($emailPreheader); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f4f8;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                <tr>
                    <td align="center" style="background:#0084FF; border-radius:16px 16px 0 0; padding:28px 24px;">
                        <span style="font-family:Arial, Helvetica, sans-serif; font-size:32px; font-weight:bold; color:#ffffff; letter-spacing:-0.5px;">Kolae</span>
                    </td>
                </tr>
                <tr>
                    <td style="background:#ffffff; border-radius:0 0 16px 16px; padding:36px 32px; font-size:16px; line-height:1.6; color:#334155;">
                        <?php echo $emailContent; ?>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:24px 16px 0; font-size:12px; line-height:1.6; color:#64748b;">
                        <a href="<?php echo $h(BASE_URL); ?>/" style="color:#0069CC; text-decoration:none; font-weight:bold;"><?php echo $h($site); ?></a> · Conectando atletas e quadras<br>
                        Este é um e-mail automático; por favor, não responda.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
