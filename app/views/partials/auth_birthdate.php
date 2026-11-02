<?php
/**
 * Data de nascimento em três listas (dia, mês pelo nome, ano), na ordem de cada idioma.
 * Os anos vão de quem tem 18 até quem tem 100 (User::MIN_AGE). O JS (src/js/pages/auth_form.js)
 * monta o campo escondido "birthdate" (AAAA-MM-DD) e avisa na hora se a data não existe ou é de menor;
 * sem JS, o servidor monta a data a partir das três listas (AuthController::store).
 *
 * Usa: $lang.
 */
$be = fn($s) => htmlspecialchars((string)$s);
$months = explode(',', $lang['auth_months']);
$maxYear = (int)date('Y') - \App\Models\User::MIN_AGE;
$order = [
    'en-us' => ['month', 'day', 'year'],
    'ja-jp' => ['year', 'month', 'day'],
    'zh-cn' => ['year', 'month', 'day'],
][$_SESSION['idioma'] ?? 'pt-br'] ?? ['day', 'month', 'year'];

$parts = [
    'day' => ['label' => $lang['auth_day'], 'width' => 'w-[5.5rem] shrink-0', 'options' => array_combine(range(1, 31), range(1, 31))],
    'month' => ['label' => $lang['auth_month'], 'width' => 'flex-1 min-w-0', 'options' => array_combine(range(1, 12), $months)],
    'year' => ['label' => $lang['auth_year'], 'width' => 'w-[6.5rem] shrink-0', 'options' => array_combine(range($maxYear, $maxYear - 82), range($maxYear, $maxYear - 82))],
];
?>
<fieldset data-birthdate data-msg-underage="<?php echo $be($lang['auth_birth_underage']); ?>" data-msg-invalid="<?php echo $be($lang['auth_birth_invalid']); ?>"
    data-min-age="<?php echo \App\Models\User::MIN_AGE; ?>" aria-describedby="birthdate-hint">
    <legend class="auth-label"><?php echo $be($lang['register_birthday']); ?></legend>
    <input type="hidden" id="birthdate" name="birthdate" value="">
    <div class="flex gap-2">
        <?php foreach ($order as $part): $p = $parts[$part]; ?>
            <div class="relative <?php echo $p['width']; ?>">
                <label for="birth_<?php echo $part; ?>" class="sr-only"><?php echo $be($p['label']); ?></label>
                <select id="birth_<?php echo $part; ?>" name="birth_<?php echo $part; ?>" class="auth-select" required data-remember>
                    <option value="" disabled selected><?php echo $be($p['label']); ?></option>
                    <?php foreach ($p['options'] as $value => $text): ?>
                        <option value="<?php echo (int)$value; ?>"><?php echo $be($text); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down auth-select-icon" aria-hidden="true"></i>
            </div>
        <?php endforeach; ?>
    </div>
    <p id="birthdate-hint" class="auth-hint" role="status"><i class="fas fa-circle-info" aria-hidden="true"></i><span><?php echo $be($lang['auth_birth_hint']); ?></span></p>
</fieldset>
