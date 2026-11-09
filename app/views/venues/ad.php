<?php
/**
 * Aba Anúncio da quadra (D63). Dados do VenueAdController.
 * Premium: o anúncio em aberto (situação, prévia, números, pausar/retomar/editar/encerrar) ou o
 * formulário com prévia ao vivo. Grátis: convite para o Premium. Embaixo, os anúncios anteriores.
 */
use App\Core\VenueAds;

$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$pageTitle = $lang['ad_tab'] . ' · ' . $venue['name'] . ' · Kolae';
$toastContext = 'ad';
$venueId = (int)$venue['id'];
$base = BASE_URL . $routePrefix . '/quadras/' . $venueId . '/anuncio';
$planUrl = BASE_URL . '/dashboard/perfil/plano';
$dateBr = fn($ymd) => date('d/m/Y', strtotime($ymd));
$place = implode(' · ', array_filter([$venue['neighborhood'] ?? null, $venue['city'] ?? null]));
$showForm = $premium && (!$ad || $editing);

// Situação do anúncio em aberto: [classes do selo, ícone, texto]
$states = [
    'running' => ['bg-green-500/10 text-green-700 dark:text-green-400', 'fa-circle-play', strtr($lang['ad_state_running'], ['{date}' => $ad ? $dateBr($ad['ends_on']) : ''])],
    'scheduled' => ['bg-brand-500/10 text-brand-700 dark:text-brand-300', 'fa-calendar-day', strtr($lang['ad_state_scheduled'], ['{date}' => $ad ? $dateBr($ad['starts_on']) : ''])],
    'paused' => ['bg-amber-500/10 text-amber-700 dark:text-amber-400', 'fa-pause', $lang['ad_state_paused']],
    'premium_expired' => ['bg-amber-500/10 text-amber-700 dark:text-amber-400', 'fa-crown', $lang['ad_state_premium_expired']],
];
$finalStates = ['ended' => $lang['ad_history_ended'], 'removed' => $lang['ad_history_removed'], 'finished' => $lang['ad_history_finished']];

// Formulário: valores (os digitados, depois de um erro) e limites das datas
$val = fn($k) => (string)($input[$k] ?? '');
$startMin = ($editing && $ad && $ad['starts_on'] < $today) ? $ad['starts_on'] : $today;
$startValue = $val('starts_on') !== '' ? $val('starts_on') : $today;
$endValue = $val('ends_on') !== '' ? $val('ends_on') : VenueAds::lastDay($startValue);
$fieldError = fn($field) => isset($errors[$field]) ? ($lang['ad_error_' . $field . '_' . $errors[$field]] ?? $lang['unknown_error']) : null;
$inputClass = fn($field) => 'w-full bg-input-base border ' . (isset($errors[$field]) ? 'border-red-500' : 'border-content-secondary/20') . ' rounded-xl px-4 py-3 text-content-primary focus:border-brand-500 outline-none transition-all';
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <div class="mb-5">
            <h2 class="text-xl font-bold flex items-center gap-2"><?php echo $e($lang['ad_title']); ?>
                <?php if ($premium): ?><span class="inline-flex items-center gap-1 rounded-md bg-amber-400/20 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:text-amber-400"><i class="fas fa-crown" aria-hidden="true"></i>Premium</span><?php endif; ?>
            </h2>
            <p class="text-sm text-content-secondary mt-0.5"><?php echo $e($lang['ad_intro']); ?></p>
        </div>

        <?php if ($lastRemoved): ?>
            <div class="mb-5 p-4 rounded-2xl border border-red-500/30 bg-red-500/5 text-sm flex items-start gap-3" id="ad-removed" role="status">
                <span class="mt-0.5 text-red-600 dark:text-red-400"><i class="fas fa-ban" aria-hidden="true"></i></span>
                <div>
                    <p class="font-semibold"><?php echo $e(strtr($lang['ad_removed_title'], ['{title}' => $lastRemoved['title']])); ?></p>
                    <?php if (!empty($lastRemoved['removed_reason'])): ?>
                        <p class="mt-1 text-content-secondary"><?php echo $e($lang['ad_removed_reason']); ?> <?php echo $e($lastRemoved['removed_reason']); ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-content-secondary"><?php echo $e($lang['ad_removed_text']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$premium && !$ad): ?>
            <!-- Grátis: convite para o Premium, com um anúncio de exemplo apagado ao fundo -->
            <section class="relative overflow-hidden rounded-2xl border border-content-secondary/10 bg-surface-elevated" id="ad-locked">
                <div class="p-5 md:p-6 blur-[3px] opacity-50 select-none pointer-events-none max-w-sm" aria-hidden="true">
                    <?php $adCard = ['title' => $lang['ad_example_title'], 'body' => $lang['ad_example_body'], 'image' => $coverUrl, 'ends_on' => VenueAds::lastDay($today), 'venue' => $venue['name'], 'place' => $place];
                    include BASE_PATH . '/app/views/partials/ad_card.php'; ?>
                </div>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-surface-elevated/40">
                    <span class="w-12 h-12 rounded-full bg-amber-400/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl" aria-hidden="true"><i class="fas fa-crown"></i></span>
                    <h3 class="mt-3 font-bold text-lg"><?php echo $e($lang['ad_locked_title']); ?></h3>
                    <p class="mt-1 text-sm text-content-secondary max-w-md"><?php echo $e($lang['ad_locked_text']); ?></p>
                    <a href="<?php echo $planUrl; ?>" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold transition-colors"><?php echo $e($lang['ad_locked_btn']); ?></a>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($ad && !$editing): [$badgeClass, $badgeIcon, $badgeText] = $states[$adState]; ?>
            <!-- Anúncio em aberto -->
            <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-6 shadow-sm" id="ad-current">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold <?php echo $badgeClass; ?>" data-ad-state="<?php echo $e($adState); ?>"><i class="fas <?php echo $badgeIcon; ?>" aria-hidden="true"></i><?php echo $e($badgeText); ?></span>
                    <span class="text-sm text-content-secondary"><?php echo $e(strtr($lang['ad_period'], ['{from}' => $dateBr($ad['starts_on']), '{to}' => $dateBr($ad['ends_on'])])); ?></span>
                </div>
                <?php if ($adState === 'premium_expired'): ?>
                    <p class="mt-3 text-sm text-amber-800 dark:text-amber-300"><?php echo $e($lang['ad_premium_expired_text']); ?> <a href="<?php echo $planUrl; ?>" class="font-semibold underline"><?php echo $e($lang['ad_renew']); ?></a></p>
                <?php endif; ?>

                <div class="mt-5 grid md:grid-cols-[minmax(0,20rem)_1fr] gap-6 items-start">
                    <div>
                        <?php $adCard = ['title' => $ad['title'], 'body' => $ad['body'], 'image' => $adImage, 'ends_on' => $ad['ends_on'], 'venue' => $venue['name'], 'place' => $place];
                        include BASE_PATH . '/app/views/partials/ad_card.php'; ?>
                    </div>
                    <div>
                        <dl class="grid grid-cols-3 gap-3" id="ad-numbers">
                            <?php $rate = $adCounts['views'] > 0 ? round($adCounts['clicks'] / $adCounts['views'] * 100, 1) : 0;
                            foreach ([['views', $lang['ad_views'], number_format($adCounts['views'], 0, ',', '.')], ['clicks', $lang['ad_clicks'], number_format($adCounts['clicks'], 0, ',', '.')], ['rate', $lang['ad_rate'], number_format($rate, $rate == floor($rate) ? 0 : 1, ',', '.') . '%']] as [$k, $label, $value]): ?>
                                <div class="rounded-xl bg-surface-base border border-content-secondary/10 p-3" data-number="<?php echo $k; ?>">
                                    <dt class="text-xs text-content-secondary"><?php echo $e($label); ?></dt>
                                    <dd class="mt-1 text-xl font-bold tabular-nums"><?php echo $e($value); ?></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                        <p class="mt-2 text-xs text-content-secondary"><?php echo $e($lang['ad_numbers_hint']); ?></p>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <?php if ($premium): ?>
                                <a href="<?php echo $base; ?>?editar=1#ad-form" id="ad-edit" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-content-secondary/20 text-sm font-semibold hover:border-brand-500 hover:text-brand-600 dark:hover:text-brand-400 transition-colors"><span><i class="fas fa-pen" aria-hidden="true"></i></span><?php echo $e($lang['ad_edit']); ?></a>
                            <?php endif; ?>
                            <?php if ($ad['status'] === 'active'): ?>
                                <form method="POST" action="<?php echo $base . '/' . (int)$ad['id']; ?>/pausar"><?php echo \App\Core\Csrf::field(); ?>
                                    <button type="submit" id="ad-pause" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-content-secondary/20 text-sm font-semibold hover:border-amber-500 transition-colors"><span><i class="fas fa-pause" aria-hidden="true"></i></span><?php echo $e($lang['ad_pause']); ?></button>
                                </form>
                            <?php elseif ($premium): ?>
                                <form method="POST" action="<?php echo $base . '/' . (int)$ad['id']; ?>/retomar"><?php echo \App\Core\Csrf::field(); ?>
                                    <button type="submit" id="ad-resume" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold transition-colors"><span><i class="fas fa-play" aria-hidden="true"></i></span><?php echo $e($lang['ad_resume']); ?></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo $base . '/' . (int)$ad['id']; ?>/encerrar" data-confirm="<?php echo $e($lang['ad_end_confirm']); ?>" data-confirm-danger data-confirm-ok="<?php echo $e($lang['ad_end']); ?>"><?php echo \App\Core\Csrf::field(); ?>
                                <button type="submit" id="ad-end" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-red-500/40 text-red-600 dark:text-red-400 text-sm font-semibold hover:bg-red-500/10 transition-colors"><span><i class="fas fa-stop" aria-hidden="true"></i></span><?php echo $e($lang['ad_end']); ?></button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($showForm): ?>
            <!-- Criar ou editar, com prévia ao vivo -->
            <form id="ad-form" method="POST" action="<?php echo $base; ?>" enctype="multipart/form-data" novalidate
                class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-6 shadow-sm scroll-mt-24 grid md:grid-cols-[1fr_minmax(0,20rem)] gap-6 items-start">
                <?php echo \App\Core\Csrf::field(); ?>
                <div class="space-y-5">
                    <h3 class="font-bold text-lg"><?php echo $e($ad ? $lang['ad_form_edit'] : $lang['ad_form_new']); ?></h3>
                    <?php if ($errors): ?>
                        <p class="p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-sm text-red-700 dark:text-red-400" role="alert" id="ad-errors"><?php echo $e($lang['ad_form_errors']); ?></p>
                    <?php endif; ?>

                    <div>
                        <div class="flex items-end justify-between mb-1.5 ml-1">
                            <label for="ad-title" class="text-xs font-bold text-content-secondary uppercase tracking-wider"><?php echo $e($lang['ad_field_title']); ?></label>
                            <span class="text-xs text-content-secondary tabular-nums"><span id="ad-title-count"><?php echo mb_strlen($val('title')); ?></span>/<?php echo VenueAds::TITLE_MAX; ?></span>
                        </div>
                        <input type="text" id="ad-title" name="title" required maxlength="<?php echo VenueAds::TITLE_MAX; ?>" value="<?php echo $e($val('title')); ?>" placeholder="<?php echo $e($lang['ad_example_title']); ?>" class="<?php echo $inputClass('title'); ?>" aria-describedby="ad-title-error">
                        <p id="ad-title-error" class="<?php echo $fieldError('title') ? '' : 'hidden'; ?> mt-1.5 text-sm text-red-600 dark:text-red-400"><?php echo $e($fieldError('title') ?? $lang['ad_error_title_required']); ?></p>
                    </div>

                    <div>
                        <div class="flex items-end justify-between mb-1.5 ml-1">
                            <label for="ad-body" class="text-xs font-bold text-content-secondary uppercase tracking-wider"><?php echo $e($lang['ad_field_body']); ?></label>
                            <span class="text-xs text-content-secondary tabular-nums"><span id="ad-body-count"><?php echo mb_strlen($val('body')); ?></span>/<?php echo VenueAds::BODY_MAX; ?></span>
                        </div>
                        <textarea id="ad-body" name="body" rows="3" required maxlength="<?php echo VenueAds::BODY_MAX; ?>" placeholder="<?php echo $e($lang['ad_example_body']); ?>" class="<?php echo $inputClass('body'); ?> resize-none" aria-describedby="ad-body-error"><?php echo $e($val('body')); ?></textarea>
                        <p id="ad-body-error" class="<?php echo $fieldError('body') ? '' : 'hidden'; ?> mt-1.5 text-sm text-red-600 dark:text-red-400"><?php echo $e($fieldError('body') ?? $lang['ad_error_body_required']); ?></p>
                    </div>

                    <fieldset>
                        <legend class="text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $e($lang['ad_field_period']); ?></legend>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="ad-starts" class="block text-xs text-content-secondary mb-1 ml-1"><?php echo $e($lang['ad_field_starts']); ?></label>
                                <input type="date" id="ad-starts" name="starts_on" required min="<?php echo $e($startMin); ?>" value="<?php echo $e($startValue); ?>" class="<?php echo $inputClass('starts_on'); ?>">
                            </div>
                            <div>
                                <label for="ad-ends" class="block text-xs text-content-secondary mb-1 ml-1"><?php echo $e($lang['ad_field_ends']); ?></label>
                                <input type="date" id="ad-ends" name="ends_on" required min="<?php echo $e(max($startValue, $today)); ?>" max="<?php echo $e(VenueAds::lastDay($startValue)); ?>" value="<?php echo $e($endValue); ?>" class="<?php echo $inputClass('ends_on'); ?>">
                            </div>
                        </div>
                        <?php foreach (['starts_on', 'ends_on'] as $f): if ($fieldError($f)): ?>
                            <p class="mt-1.5 text-sm text-red-600 dark:text-red-400" data-error="<?php echo $f; ?>"><?php echo $e($fieldError($f)); ?></p>
                        <?php endif; endforeach; ?>
                        <p class="mt-1.5 text-xs text-content-secondary ml-1"><?php echo $e(strtr($lang['ad_field_period_hint'], ['{n}' => VenueAds::MAX_DAYS])); ?></p>
                    </fieldset>

                    <div>
                        <label for="ad-image" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1"><?php echo $e($lang['ad_field_image']); ?> <span class="font-normal normal-case">(<?php echo $e($lang['agenda_optional']); ?>)</span></label>
                        <input type="file" id="ad-image" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                            class="block w-full text-sm text-content-secondary file:mr-3 file:rounded-lg file:border-0 file:bg-brand-500/10 file:px-4 file:py-2 file:font-semibold file:text-brand-700 dark:file:text-brand-300 hover:file:bg-brand-500/20">
                        <?php if ($fieldError('image')): ?><p class="mt-1.5 text-sm text-red-600 dark:text-red-400" data-error="image"><?php echo $e($fieldError('image')); ?></p><?php endif; ?>
                        <p class="mt-1.5 text-xs text-content-secondary ml-1"><?php echo $e($lang['ad_field_image_hint']); ?></p>
                        <?php if (!empty($ad['image_path'])): ?>
                            <label class="mt-2 inline-flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" name="remove_image" value="1" id="ad-remove-image" class="rounded"><?php echo $e($lang['ad_field_use_cover']); ?></label>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-1">
                        <button type="submit" id="ad-submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-transform hover:-translate-y-0.5"><?php echo $e($ad ? $lang['ad_save'] : $lang['ad_publish']); ?></button>
                        <?php if ($ad): ?><a href="<?php echo $base; ?>" class="text-sm font-semibold text-content-secondary hover:text-content-primary px-3 py-2"><?php echo $e($lang['global_cancel']); ?></a><?php endif; ?>
                    </div>
                </div>

                <div class="md:sticky md:top-24">
                    <p class="text-xs font-bold text-content-secondary uppercase tracking-wider mb-2 ml-1"><?php echo $e($lang['ad_preview']); ?></p>
                    <div id="ad-preview">
                        <?php $adCard = ['title' => $val('title') ?: $lang['ad_example_title'], 'body' => $val('body') ?: $lang['ad_example_body'], 'image' => $ad ? $adImage : $coverUrl, 'ends_on' => $endValue, 'venue' => $venue['name'], 'place' => $place];
                        include BASE_PATH . '/app/views/partials/ad_card.php'; ?>
                    </div>
                    <p class="mt-2 text-xs text-content-secondary"><?php echo $e($lang['ad_preview_hint']); ?></p>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($history): ?>
            <section class="mt-8" id="ad-history">
                <h3 class="font-semibold mb-3"><?php echo $e($lang['ad_history']); ?></h3>
                <ul class="divide-y divide-content-secondary/10 rounded-2xl border border-content-secondary/10 bg-surface-elevated">
                    <?php foreach ($history as $old):
                        $oldState = VenueAds::ownerState($old, $premium, new DateTimeImmutable($today)); ?>
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                            <span class="font-medium truncate max-w-full"><?php echo $e($old['title']); ?></span>
                            <span class="text-content-secondary"><?php echo $e(strtr($lang['ad_period'], ['{from}' => $dateBr($old['starts_on']), '{to}' => $dateBr($old['ends_on'])])); ?> · <?php echo $e($finalStates[$oldState] ?? $lang['ad_history_finished']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </main>

    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
    <?php if ($showForm): ?>
        <script>
            (function () {
                const form = document.getElementById('ad-form');
                const card = document.querySelector('#ad-preview [data-ad-card]');
                const title = document.getElementById('ad-title');
                const body = document.getElementById('ad-body');
                const starts = document.getElementById('ad-starts');
                const ends = document.getElementById('ad-ends');
                const image = document.getElementById('ad-image');
                const removeImage = document.getElementById('ad-remove-image');
                const t = <?php echo json_encode(['example_title' => $lang['ad_example_title'], 'example_body' => $lang['ad_example_body'], 'until' => $lang['ad_until']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
                const coverUrl = <?php echo json_encode($coverUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
                const savedUrl = <?php echo json_encode($ad ? $adImage : $coverUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES); ?>;
                const MAX_DAYS = <?php echo VenueAds::MAX_DAYS; ?>;
                const today = <?php echo json_encode($today); ?>;
                const img = card.querySelector('[data-ad-image]');
                const noImg = card.querySelector('[data-ad-noimage]');
                let objectUrl = null;

                const addDays = (ymd, n) => { const d = new Date(ymd + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
                const br = ymd => ymd ? ymd.slice(8, 10) + '/' + ymd.slice(5, 7) : '';
                const setImage = url => { img.hidden = !url; noImg.hidden = !!url; if (url) img.src = url; };

                const update = () => {
                    card.querySelector('[data-ad-title]').textContent = title.value.trim() || t.example_title;
                    card.querySelector('[data-ad-body]').textContent = body.value.trim() || t.example_body;
                    card.querySelector('[data-ad-ends]').textContent = ends.value ? t.until.replace('{date}', br(ends.value)) : '';
                    document.getElementById('ad-title-count').textContent = title.value.length;
                    document.getElementById('ad-body-count').textContent = body.value.length;
                };
                // O fim acompanha o início: no máximo 30 dias contando o primeiro
                const syncEnds = () => {
                    if (!starts.value) return;
                    const last = addDays(starts.value, MAX_DAYS - 1);
                    ends.min = starts.value > today ? starts.value : today;
                    ends.max = last;
                    if (!ends.value || ends.value > last || ends.value < ends.min) ends.value = last;
                    update();
                };

                [title, body, ends].forEach(el => el.addEventListener('input', update));
                starts.addEventListener('change', syncEnds);
                image.addEventListener('change', () => {
                    if (objectUrl) URL.revokeObjectURL(objectUrl);
                    objectUrl = image.files[0] ? URL.createObjectURL(image.files[0]) : null;
                    if (removeImage && objectUrl) removeImage.checked = false;
                    setImage(objectUrl || (removeImage && removeImage.checked ? coverUrl : savedUrl));
                });
                if (removeImage) removeImage.addEventListener('change', () => { if (!objectUrl) setImage(removeImage.checked ? coverUrl : savedUrl); });

                // Título e texto obrigatórios: avisa sem recarregar (o servidor confere tudo de novo)
                form.addEventListener('submit', ev => {
                    let first = null;
                    [[title, 'ad-title-error'], [body, 'ad-body-error']].forEach(([el, errId]) => {
                        const bad = el.value.trim() === '';
                        document.getElementById(errId).classList.toggle('hidden', !bad);
                        el.classList.toggle('border-red-500', bad);
                        if (bad && !first) first = el;
                    });
                    if (first) { ev.preventDefault(); first.focus(); }
                });
                update();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
