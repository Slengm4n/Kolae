<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$authAlert = null;
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'credentials':
            $message = $lang['error_incorrect_access'];
            break;
        case 'invalid_token':
            $message = $lang['error_invalid_token'];
            break;
        case 'too_many':
            $message = strtr($lang['login_too_many'], ['{minutes}' => (int)($_GET['minutes'] ?? 15)]);
            break;
        case 'inactive_account':
            $message = $lang['error_account_inactive'];
            break;
        case 'verify_expired':
            $message = $lang['verify_expired'];
            break;
        case 'verify_invalid':
            $message = $lang['verify_invalid'];
            break;
        default:
            $message = $lang['unknown_error'];
    }
    $authAlert = ['error', $message];
} elseif (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'password_reset':
            $authAlert = ['success', $lang['message_password_redefinition']];
            break;
        case 'registered':
            $authAlert = ['success', $lang['message_registration_success'], $lang['verify_after_register']];
            break;
        case 'email_verified':
            $authAlert = ['success', $lang['verify_done']];
            break;
    }
}

ob_start();
?>
<form id="login-form" action="<?php echo BASE_URL; ?>/login/authenticate" method="POST" class="space-y-5" data-auth-form>
    <?php echo \App\Core\Csrf::field(); ?>

    <?php
    $field = ['id' => 'email', 'type' => 'email', 'icon' => 'fa-envelope', 'label' => $lang['global_email'],
        'placeholder' => $lang['register_email_ph'], 'autocomplete' => 'email', 'attrs' => 'required data-remember autofocus'];
    include BASE_PATH . '/app/views/partials/auth_field.php';

    $field = ['id' => 'password', 'type' => 'password', 'icon' => 'fa-lock', 'label' => $lang['global_senha'],
        'placeholder' => $lang['login_senha_ph'], 'autocomplete' => 'current-password', 'attrs' => 'required'];
    include BASE_PATH . '/app/views/partials/auth_field.php';
    ?>

    <div class="flex items-center justify-between gap-4 text-sm">
        <label for="remember-me" class="flex items-center gap-2 cursor-pointer select-none text-content-secondary hover:text-content-primary transition-colors">
            <input id="remember-me" name="remember-me" type="checkbox" class="auth-check">
            <?php echo htmlspecialchars($lang['login_remember']); ?>
        </label>
        <a href="<?php echo BASE_URL; ?>/forgot-password" class="auth-link"><?php echo htmlspecialchars($lang['login_forget']); ?></a>
    </div>

    <button id="login-button" type="submit" class="auth-btn mt-7" data-loading-text="<?php echo htmlspecialchars($lang['auth_wait']); ?>">
        <?php echo htmlspecialchars($lang['login_btn_entry']); ?>
        <i class="fas fa-arrow-right text-sm" aria-hidden="true"></i>
    </button>
</form>
<?php
$authContent = ob_get_clean();

$pageTitle = $lang['global_menu_login'] . ' · Kolae';
$authImage = 'login_bg.webp';
$authSideTitle = $lang['login_title_jorney'];
$authSideText = $lang['login_subtitle_jorney'];
$authBack = [BASE_URL . '/', $lang['auth_back_home']];
$authTitle = $lang['login_title_welcome'];
$authSubtitle = $lang['login_subtitle_welcome'];
$authFooter = htmlspecialchars($lang['login_text_register'])
    . ' <a href="' . BASE_URL . '/register" class="auth-link">' . htmlspecialchars($lang['global_menu_register']) . '</a>';

include BASE_PATH . '/app/views/partials/auth_layout.php';
