<?php
/**
 * Aba Fotos da quadra (D46): galeria em ordem (a primeira é a capa), enviar mais fotos,
 * escolher a capa, mudar a ordem e apagar. Dados vindos do VenuePhotosController.
 */
$lang = require BASE_PATH . '/includes/i18n.php';
$pageTitle = $lang['venue_tab_photos'] . ' · ' . $venue['name'] . ' · Kolae';
$venueId = (int)$venue['id'];
$action = BASE_URL . $routePrefix . '/quadras/' . $venueId . '/fotos';
$imgUrl = fn(array $img) => BASE_URL . '/uploads/venues/' . $venueId . '/' . rawurlencode(basename($img['file_path']));
$total = count($images);
$room = max(0, $maxImages - $total);
$flash = $_GET['status'] ?? null;

// Mensagem do envio: "3 fotos enviadas. 1 recusada (formato ou tamanho)."
$uploadMessage = '';
if ($flash === 'uploaded') {
    $parts = [];
    if (($saved = (int)($_GET['saved'] ?? 0)) > 0) $parts[] = strtr($lang[$saved === 1 ? 'photos_saved_one' : 'photos_saved_many'], ['{n}' => $saved]);
    if (($rejected = (int)($_GET['rejected'] ?? 0)) > 0) $parts[] = strtr($lang['photos_rejected'], ['{n}' => $rejected]);
    if (($over = (int)($_GET['over_limit'] ?? 0)) > 0) $parts[] = strtr($lang['photos_over_limit'], ['{n}' => $over, '{max}' => $maxImages]);
    $uploadMessage = implode(' ', $parts);
}
$flashOk = ['cover' => $lang['photos_cover_saved'], 'deleted' => $lang['photos_deleted']];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($_SESSION['idioma'] ?? 'pt-br'); ?>">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-poppins">
    <?php include BASE_PATH . '/app/views/partials/owner_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-4xl pb-28 md:pb-10">
        <?php include BASE_PATH . '/app/views/partials/venue_top.php'; ?>

        <?php if ($uploadMessage !== ''):
            $allOk = empty($_GET['rejected']) && empty($_GET['over_limit']); ?>
            <div class="mb-6 p-4 rounded-xl border flex items-start gap-3 <?php echo $allOk ? 'border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-400' : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300'; ?>" role="status">
                <span class="mt-0.5"><i class="fas <?php echo $allOk ? 'fa-check-circle' : 'fa-triangle-exclamation'; ?>"></i></span><p class="text-sm font-medium"><?php echo htmlspecialchars($uploadMessage); ?></p>
            </div>
        <?php elseif (isset($flashOk[$flash])): ?>
            <div class="mb-6 p-4 rounded-xl border border-green-500/30 bg-green-500/10 text-green-700 dark:text-green-400 flex items-start gap-3" role="status">
                <span class="mt-0.5"><i class="fas fa-check-circle"></i></span><p class="text-sm font-medium"><?php echo $flashOk[$flash]; ?></p>
            </div>
        <?php elseif ($flash === 'none'): ?>
            <div class="mb-6 p-4 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300 flex items-start gap-3" role="alert">
                <span class="mt-0.5"><i class="fas fa-triangle-exclamation"></i></span><p class="text-sm font-medium"><?php echo $lang['photos_none_selected']; ?></p>
            </div>
        <?php elseif ($flash === 'error'): ?>
            <div class="mb-6 p-4 rounded-xl border border-red-500/30 bg-red-500/10 text-red-700 dark:text-red-400 flex items-start gap-3" role="alert">
                <span class="mt-0.5"><i class="fas fa-times-circle"></i></span><p class="text-sm font-medium"><?php echo $lang['photos_error']; ?></p>
            </div>
        <?php endif; ?>

        <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 md:p-7 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold"><?php echo $lang['photos_title']; ?></h2>
                    <p class="text-sm text-content-secondary mt-1"><?php echo $lang['photos_hint']; ?></p>
                </div>
                <span class="shrink-0 text-sm font-semibold tabular-nums <?php echo $room === 0 ? 'text-amber-700 dark:text-amber-300' : 'text-content-secondary'; ?>"><?php echo strtr($lang['photos_count'], ['{n}' => $total, '{max}' => $maxImages]); ?></span>
            </div>

            <?php if ($room > 0): ?>
                <form id="upload-form" method="POST" action="<?php echo $action; ?>" enctype="multipart/form-data" class="mt-5" data-room="<?php echo $room; ?>" data-max-bytes="<?php echo 10 * 1024 * 1024; ?>">
                    <?php echo \App\Core\Csrf::field(); ?>
                    <label id="drop-zone" for="images" class="flex flex-col items-center justify-center gap-2 text-center border-2 border-dashed border-content-secondary/25 rounded-2xl px-6 py-8 cursor-pointer hover:border-brand-500 hover:bg-brand-500/5 transition-colors">
                        <span class="w-12 h-12 rounded-full bg-brand-500/10 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl"><i class="fas fa-cloud-arrow-up"></i></span>
                        <span class="font-semibold hidden md:inline"><?php echo $lang['photos_drop']; ?></span>
                        <span class="font-semibold md:hidden"><?php echo $lang['photos_tap']; ?></span>
                        <span class="text-xs text-content-secondary"><?php echo strtr($lang['photos_rules'], ['{n}' => $room]); ?></span>
                        <input type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                    </label>
                    <div id="chosen" class="hidden mt-4">
                        <div id="chosen-list" class="grid grid-cols-4 sm:grid-cols-6 gap-2"></div>
                        <p id="chosen-warning" class="hidden mt-3 text-sm text-amber-700 dark:text-amber-300" role="alert"></p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button type="submit" id="upload-btn" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors"><span><i class="fas fa-upload"></i></span><span id="upload-label"><?php echo $lang['photos_send']; ?></span></button>
                            <button type="button" id="clear-btn" class="text-sm font-semibold text-content-secondary hover:text-content-primary px-3 py-2"><?php echo $lang['global_cancel']; ?></button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <p class="mt-5 p-4 rounded-xl bg-surface-secondary text-sm text-content-secondary"><?php echo strtr($lang['photos_full'], ['{max}' => $maxImages]); ?></p>
            <?php endif; ?>
        </section>

        <?php if (!$images): ?>
            <div class="mt-6 p-8 rounded-2xl border border-dashed border-content-secondary/20 text-center">
                <span class="text-3xl text-content-secondary/50"><i class="fas fa-image"></i></span>
                <p class="mt-3 font-semibold"><?php echo $lang['photos_empty_title']; ?></p>
                <p class="text-sm text-content-secondary mt-1"><?php echo $lang['photos_empty_text']; ?></p>
            </div>
        <?php else: ?>
            <ol class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($images as $i => $img):
                    $imageId = (int)$img['id'];
                    $isCover = $i === 0;
                    $base = $action . '/' . $imageId; ?>
                    <li id="foto-<?php echo $imageId; ?>" class="scroll-mt-28 bg-surface-elevated border <?php echo $isCover ? 'border-brand-500/60 ring-1 ring-brand-500/30' : 'border-content-secondary/10'; ?> rounded-2xl overflow-hidden shadow-sm flex flex-col">
                        <div class="relative aspect-[4/3] bg-surface-secondary">
                            <img src="<?php echo $imgUrl($img); ?>" alt="<?php echo htmlspecialchars(strtr($lang['photos_alt'], ['{n}' => $i + 1])); ?>" class="w-full h-full object-cover" loading="lazy">
                            <span class="absolute left-2 top-2 text-xs font-bold rounded-lg px-2 py-1 <?php echo $isCover ? 'bg-brand-500 text-white' : 'bg-black/55 text-white'; ?>">
                                <?php echo $isCover ? '<i class="fas fa-star mr-1"></i>' . $lang['photos_cover'] : $i + 1; ?>
                            </span>
                        </div>
                        <div class="p-3 flex items-center gap-2">
                            <form method="POST" action="<?php echo $base; ?>/mover">
                                <?php echo \App\Core\Csrf::field(); ?>
                                <input type="hidden" name="direction" value="left">
                                <button type="submit" class="w-10 h-10 rounded-lg border border-content-secondary/15 text-content-secondary hover:text-content-primary hover:border-content-secondary/40 disabled:opacity-30 disabled:cursor-not-allowed" aria-label="<?php echo htmlspecialchars($lang['photos_move_left']); ?>" title="<?php echo htmlspecialchars($lang['photos_move_left']); ?>" <?php echo $i === 0 ? 'disabled' : ''; ?>><i class="fas fa-arrow-left"></i></button>
                            </form>
                            <form method="POST" action="<?php echo $base; ?>/mover">
                                <?php echo \App\Core\Csrf::field(); ?>
                                <input type="hidden" name="direction" value="right">
                                <button type="submit" class="w-10 h-10 rounded-lg border border-content-secondary/15 text-content-secondary hover:text-content-primary hover:border-content-secondary/40 disabled:opacity-30 disabled:cursor-not-allowed" aria-label="<?php echo htmlspecialchars($lang['photos_move_right']); ?>" title="<?php echo htmlspecialchars($lang['photos_move_right']); ?>" <?php echo $i === $total - 1 ? 'disabled' : ''; ?>><i class="fas fa-arrow-right"></i></button>
                            </form>
                            <?php if (!$isCover): ?>
                                <form method="POST" action="<?php echo $base; ?>/capa" class="flex-1">
                                    <?php echo \App\Core\Csrf::field(); ?>
                                    <button type="submit" class="w-full h-10 rounded-lg text-sm font-semibold text-brand-600 dark:text-brand-400 hover:bg-brand-500/10 transition-colors"><?php echo $lang['photos_make_cover']; ?></button>
                                </form>
                            <?php else: ?>
                                <span class="flex-1 text-center text-xs text-content-secondary"><?php echo $lang['photos_cover_hint']; ?></span>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo $base; ?>/excluir" class="delete-form">
                                <?php echo \App\Core\Csrf::field(); ?>
                                <button type="submit" class="w-10 h-10 rounded-lg text-content-secondary hover:text-red-600 hover:bg-red-500/10 transition-colors" aria-label="<?php echo htmlspecialchars($lang['photos_delete']); ?>" title="<?php echo htmlspecialchars($lang['photos_delete']); ?>"><i class="fas fa-trash-can"></i></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </main>

    <script>
        (function () {
            document.querySelectorAll('.delete-form').forEach(f => f.addEventListener('submit', e => {
                if (!confirm(<?php echo json_encode($lang['photos_delete_confirm'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)) e.preventDefault();
            }));

            const form = document.getElementById('upload-form');
            if (!form) return;
            const input = document.getElementById('images');
            const zone = document.getElementById('drop-zone');
            const box = document.getElementById('chosen');
            const list = document.getElementById('chosen-list');
            const warning = document.getElementById('chosen-warning');
            const label = document.getElementById('upload-label');
            const room = Number(form.dataset.room);
            const maxBytes = Number(form.dataset.maxBytes);
            const msg = <?php echo json_encode([
                'send' => $lang['photos_send_n'],
                'tooMany' => $lang['photos_too_many'],
                'tooBig' => $lang['photos_too_big'],
                'type' => $lang['photos_bad_type'],
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
            const okTypes = ['image/jpeg', 'image/png', 'image/webp'];

            // Mostra as escolhidas e já tira o que o servidor recusaria (formato, tamanho, limite)
            const show = files => {
                const dt = new DataTransfer();
                const notes = [];
                let big = 0, bad = 0, extra = 0;
                for (const file of files) {
                    if (!okTypes.includes(file.type)) { bad++; continue; }
                    if (file.size > maxBytes) { big++; continue; }
                    if (dt.files.length >= room) { extra++; continue; }
                    dt.items.add(file);
                }
                input.files = dt.files;
                if (bad) notes.push(msg.type.replace('{n}', bad));
                if (big) notes.push(msg.tooBig.replace('{n}', big));
                if (extra) notes.push(msg.tooMany.replace('{n}', extra).replace('{room}', room));
                warning.textContent = notes.join(' ');
                warning.classList.toggle('hidden', !notes.length);

                list.innerHTML = '';
                for (const file of dt.files) {
                    const img = document.createElement('img');
                    img.className = 'aspect-square w-full object-cover rounded-lg border border-content-secondary/10';
                    img.alt = '';
                    img.src = URL.createObjectURL(file);
                    list.appendChild(img);
                }
                label.textContent = msg.send.replace('{n}', dt.files.length);
                document.getElementById('upload-btn').disabled = dt.files.length === 0;
                box.classList.toggle('hidden', dt.files.length === 0 && !notes.length);
            };

            input.addEventListener('change', () => show(input.files));
            ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('border-brand-500', 'bg-brand-500/5'); }));
            ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('border-brand-500', 'bg-brand-500/5'); }));
            zone.addEventListener('drop', e => show(e.dataTransfer.files));
            document.getElementById('clear-btn').addEventListener('click', () => { input.value = ''; show([]); box.classList.add('hidden'); });
            form.addEventListener('submit', () => {
                const btn = document.getElementById('upload-btn');
                btn.disabled = true;
                btn.classList.add('opacity-70');
            });
        })();
    </script>
</body>
</html>
