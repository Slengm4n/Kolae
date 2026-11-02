<?php
/**
 * Campo das telas de entrada: rótulo, ícone e, em senha, o botão de mostrar/esconder
 * e o aviso de Caps Lock (src/js/pages/auth_form.js).
 *
 * Usa: $lang e $field = [
 *   'id', 'label', 'icon' (Font Awesome), 'type' (padrão text),
 *   'placeholder', 'autocomplete', 'attrs' (atributos extras, ex: 'required minlength="8"'),
 *   'hint' (texto de ajuda abaixo do campo, opcional), 'value' (opcional)
 * ]
 */
$fe = fn($s) => htmlspecialchars((string)$s);
$fType = $field['type'] ?? 'text';
$fIsPassword = $fType === 'password';
$fName = $field['name'] ?? $field['id'];
?>
<div>
    <label for="<?php echo $fe($field['id']); ?>" class="auth-label"><?php echo $fe($field['label']); ?></label>
    <div class="auth-field">
        <i class="fas <?php echo $fe($field['icon']); ?> auth-icon" aria-hidden="true"></i>
        <input id="<?php echo $fe($field['id']); ?>" name="<?php echo $fe($fName); ?>" type="<?php echo $fe($fType); ?>"
            class="auth-input<?php echo $fIsPassword ? ' has-toggle' : ''; ?>"
            <?php if (!empty($field['placeholder'])): ?>placeholder="<?php echo $fe($field['placeholder']); ?>"<?php endif; ?>
            <?php if (!empty($field['autocomplete'])): ?>autocomplete="<?php echo $fe($field['autocomplete']); ?>"<?php endif; ?>
            <?php if (isset($field['value'])): ?>value="<?php echo $fe($field['value']); ?>"<?php endif; ?>
            <?php if (!empty($field['hint'])): ?>aria-describedby="<?php echo $fe($field['id']); ?>-hint"<?php endif; ?>
            <?php echo $field['attrs'] ?? ''; ?>>
        <?php if ($fIsPassword): ?>
            <button type="button" class="auth-toggle" data-password-toggle="<?php echo $fe($field['id']); ?>"
                data-label-show="<?php echo $fe($lang['auth_show_password']); ?>" data-label-hide="<?php echo $fe($lang['auth_hide_password']); ?>"
                aria-label="<?php echo $fe($lang['auth_show_password']); ?>" aria-pressed="false">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
    </div>
    <?php if (!empty($field['hint'])): ?>
        <p id="<?php echo $fe($field['id']); ?>-hint" class="auth-hint"><?php echo $fe($field['hint']); ?></p>
    <?php endif; ?>
    <?php if ($fIsPassword): ?>
        <p class="auth-hint is-bad hidden" data-caps-warning="<?php echo $fe($field['id']); ?>" role="status">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i><?php echo $fe($lang['auth_caps_lock']); ?>
        </p>
    <?php endif; ?>
</div>
