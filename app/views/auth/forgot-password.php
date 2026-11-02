<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$authAlert = null;
if (($_GET['status'] ?? '') === 'sent') {
    $authAlert = ['success', $lang['email_exists'],
        strtr($lang['forgot_resend_hint'], ['{n}' => \App\Models\PasswordReset::RESEND_MINUTES])];
} elseif (($_GET['error'] ?? '') === 'invalid_email') {
    $authAlert = ['error', $lang['email_invalid']];
}

ob_start();
?>
<form id="forgot-form" action="<?php echo BASE_URL; ?>/forgot-password" method="POST" class="space-y-5" data-auth-form>
    <?php echo \App\Core\Csrf::field(); ?>

    <?php
    $field = ['id' => 'email', 'type' => 'email', 'icon' => 'fa-envelope', 'label' => $lang['global_email'],
        'placeholder' => $lang['forget_email_registred'], 'autocomplete' => 'email', 'attrs' => 'required data-remember autofocus'];
    include BASE_PATH . '/app/views/partials/auth_field.php';
    ?>

    <button id="submit-btn" type="submit" class="auth-btn mt-7" data-loading-text="<?php echo htmlspecialchars($lang['auth_sending']); ?>">
        <i class="fas fa-paper-plane text-sm" aria-hidden="true"></i>
        <?php echo htmlspecialchars($lang['forget_btn_recover']); ?>
    </button>
</form>
<?php
$authContent = ob_get_clean();

$pageTitle = $lang['forget_title_password'] . ' · Kolae';
$authImage = 'forgot_bg.webp';
$authSideTitle = $lang['forget_title_back'];
$authSideText = $lang['forget_subtitle_back'];
$authBack = [BASE_URL . '/login', $lang['auth_back_login']];
$authTitle = $lang['forget_title_password'];
$authSubtitle = $lang['forget_subtitle_password'];
$authFooter = htmlspecialchars($lang['remember_passoword_text'])
    . ' <a href="' . BASE_URL . '/login" class="auth-link">' . htmlspecialchars($lang['remember_password_btn']) . '</a>';

include BASE_PATH . '/app/views/partials/auth_layout.php';
