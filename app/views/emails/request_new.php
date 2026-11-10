<?php
/**
 * E-mail ao dono a cada pedido de horário novo (D64), sobre a base emails/layout.php.
 * Variáveis: $ownerName, $person (primeiro nome de quem pediu), $venueName, $when ("12/11, das
 * 19:00 às 20:00"), $sport (ou null), $message (ou null), $link (tela de solicitações).
 */
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$firstName = explode(' ', trim((string)$ownerName))[0] ?: 'olá';
$emailTitle = 'Novo pedido de horário';
$emailPreheader = $person . ' pediu ' . $when . ' em ' . $venueName . '.';

ob_start();
?>
<h1 style="margin:0 0 16px; font-size:24px; line-height:1.3; color:#0b1220;">Novo pedido de horário</h1>
<p style="margin:0 0 16px;">Olá, <?php echo $h($firstName); ?>!</p>
<p style="margin:0 0 20px;"><strong><?php echo $h($person); ?></strong> pediu um horário pelo aplicativo do Kolae:</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px; background:#f1f5f9; border-radius:12px;">
    <tr><td style="padding:16px 20px; font-size:15px; line-height:1.6; color:#334155;">
        <strong style="color:#0b1220;"><?php echo $h($venueName); ?></strong><br>
        <?php echo $h($when); ?>
        <?php if ($sport): ?><br>Esporte: <?php echo $h($sport); ?><?php endif; ?>
        <?php if ($message): ?><br><span style="color:#64748b;">"<?php echo $h($message); ?>"</span><?php endif; ?>
    </td></tr>
</table>
<p style="margin:0 0 28px;">O horário ainda não está reservado: ele só entra na sua agenda quando você aprovar.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 28px;">
    <tr>
        <td align="center" style="border-radius:12px; background:#0069CC;">
            <a href="<?php echo $h($link); ?>" style="display:inline-block; padding:14px 32px; font-size:16px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:12px;">Ver o pedido</a>
        </td>
    </tr>
</table>
<p style="margin:0; font-size:14px; color:#64748b;">Se ninguém responder, o pedido expira sozinho no início do horário.</p>
<?php
$emailContent = ob_get_clean();
include BASE_PATH . '/app/views/emails/layout.php';
