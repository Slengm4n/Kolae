<?php
$lang = require BASE_PATH . '/includes/i18n.php';

$authAlert = null;
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'email_exists':
            $message = $lang['register_email_exists'];
            break;
        case 'password_mismatch':
            $message = $lang['password_mismatch'];
            break;
        case 'registration_failed':
            $message = $lang['register_failed'];
            break;
        case 'weak_password':
            $message = $lang['register_password_character'];
            break;
        case 'underage':
            $message = $lang['auth_birth_underage'];
            break;
        default:
            $message = $lang['unknown_error'];
    }
    $authAlert = ['error', $message];
}

$link = fn($href, $text) => '<a href="' . BASE_URL . $href . '" target="_blank" rel="noopener" class="auth-link">' . htmlspecialchars($text) . '</a>';
$termsText = strtr(htmlspecialchars($lang['auth_terms_text']), [
    '{terms}' => $link('/termos', $lang['auth_terms_link']),
    '{privacy}' => $link('/privacidade', $lang['auth_privacy_link']),
]);

ob_start();
?>
<form id="register-form" action="<?php echo BASE_URL; ?>/register/store" method="POST" class="space-y-5" data-auth-form>
    <?php echo \App\Core\Csrf::field(); ?>

    <?php
    $field = ['id' => 'name', 'icon' => 'fa-user', 'label' => $lang['register_name'],
        'placeholder' => $lang['register_name_ph'], 'autocomplete' => 'name', 'attrs' => 'required maxlength="100" data-remember'];
    include BASE_PATH . '/app/views/partials/auth_field.php';

    $field = ['id' => 'email', 'type' => 'email', 'icon' => 'fa-envelope', 'label' => $lang['global_email'],
        'placeholder' => $lang['register_email_ph'], 'autocomplete' => 'email', 'attrs' => 'required data-remember'];
    include BASE_PATH . '/app/views/partials/auth_field.php';

    include BASE_PATH . '/app/views/partials/auth_birthdate.php';

    $field = ['id' => 'password', 'type' => 'password', 'icon' => 'fa-lock', 'label' => $lang['register_password'],
        'autocomplete' => 'new-password',
        'hint' => $lang['auth_password_min'], 'attrs' => 'required minlength="8" data-min-hint="password-hint"'];
    include BASE_PATH . '/app/views/partials/auth_field.php';

    $field = ['id' => 'password_confirmation', 'type' => 'password', 'icon' => 'fa-lock', 'label' => $lang['register_password_two'],
        'placeholder' => $lang['register_password_ph_two'], 'autocomplete' => 'new-password',
        'attrs' => 'required minlength="8" data-match="password"'];
    include BASE_PATH . '/app/views/partials/auth_field.php';
    ?>
    <p id="password_confirmation-match" class="auth-hint hidden -mt-3" role="status"
        data-ok="<?php echo htmlspecialchars($lang['auth_password_match']); ?>" data-bad="<?php echo htmlspecialchars($lang['auth_password_nomatch']); ?>">
        <i class="fas fa-circle-check" aria-hidden="true"></i><span></span>
    </p>

    <label for="terms" class="flex items-start gap-3 cursor-pointer select-none text-sm text-content-secondary leading-relaxed pt-1">
        <input id="terms" name="terms" type="checkbox" required class="auth-check mt-0.5">
        <span><?php echo $termsText; ?></span>
    </label>

    <button id="register-button" type="submit" class="auth-btn mt-7" data-loading-text="<?php echo htmlspecialchars($lang['auth_creating']); ?>">
        <?php echo htmlspecialchars($lang['register_create_btn']); ?>
        <i class="fas fa-arrow-right text-sm" aria-hidden="true"></i>
    </button>
</form>
<?php
$authContent = ob_get_clean();

$pageTitle = $lang['global_menu_register'] . ' · Kolae';
$authImage = 'register_bg.webp';
$authSideTitle = $lang['register_img_title'];
$authSideText = $lang['register_img_text'];
$authBack = [BASE_URL . '/', $lang['auth_back_home']];
$authTitle = $lang['register_title_forms'];
$authSubtitle = $lang['register_subtitle_forms'];
$authFooter = htmlspecialchars($lang['register_confirm_answer'])
    . ' <a href="' . BASE_URL . '/login" class="auth-link">' . htmlspecialchars($lang['register_answer_btn']) . '</a>';

include BASE_PATH . '/app/views/partials/auth_layout.php';
