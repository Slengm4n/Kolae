<?php
$lang = require BASE_PATH . '/includes/i18n.php';
$token = $token ?? '';

$authAlert = null;
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_mismatch':
            $authAlert = ['error', $lang['reset_password_mismatch']];
            break;
        case 'weak_password':
            $authAlert = ['error', $lang['register_password_character']];
            break;
        case 'invalid_token':
            $authAlert = ['error', $lang['reset_invalid']];
            break;
        case 'generic':
            $authAlert = ['error', $lang['reset_unknown_error']];
            break;
    }
}

ob_start();
?>
<form id="reset-form" action="<?php echo BASE_URL; ?>/reset-password" method="POST" class="space-y-5" data-auth-form>
    <?php echo \App\Core\Csrf::field(); ?>
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

    <?php
    $field = ['id' => 'password', 'type' => 'password', 'icon' => 'fa-lock', 'label' => $lang['reset_passoword_one'],
        'autocomplete' => 'new-password', 'hint' => $lang['auth_password_min'],
        'attrs' => 'required minlength="8" autofocus data-min-hint="password-hint"'];
    include BASE_PATH . '/app/views/partials/auth_field.php';

    $field = ['id' => 'password_confirmation', 'type' => 'password', 'icon' => 'fa-lock', 'label' => $lang['reset_passoword_two'],
        'autocomplete' => 'new-password', 'attrs' => 'required minlength="8" data-match="password"'];
    include BASE_PATH . '/app/views/partials/auth_field.php';
    ?>
    <p id="password_confirmation-match" class="auth-hint hidden -mt-3" role="status"
        data-ok="<?php echo htmlspecialchars($lang['auth_password_match']); ?>" data-bad="<?php echo htmlspecialchars($lang['auth_password_nomatch']); ?>">
        <i class="fas fa-circle-check" aria-hidden="true"></i><span></span>
    </p>

    <button id="reset-button" type="submit" class="auth-btn mt-7" data-loading-text="<?php echo htmlspecialchars($lang['auth_wait']); ?>">
        <i class="fas fa-check text-sm" aria-hidden="true"></i>
        <?php echo htmlspecialchars($lang['reset_password_btn']); ?>
    </button>
</form>
<?php
$authContent = ob_get_clean();

$pageTitle = $lang['reset_title'] . ' · Kolae';
$authImage = 'forgot_bg.webp';
$authSideTitle = $lang['auth_reset_side_title'];
$authSideText = $lang['auth_reset_side_text'];
$authBack = [BASE_URL . '/login', $lang['auth_back_login']];
$authTitle = $lang['reset_title'];
$authSubtitle = $lang['auth_reset_subtitle'];
$authFooter = htmlspecialchars($lang['remember_passoword_text'])
    . ' <a href="' . BASE_URL . '/login" class="auth-link">' . htmlspecialchars($lang['remember_password_btn']) . '</a>';

include BASE_PATH . '/app/views/partials/auth_layout.php';
