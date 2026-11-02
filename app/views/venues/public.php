<?php
/**
 * Página pública da quadra (D49): o mesmo que o app mostra, para quem abre pelo site ou
 * por um link compartilhado. Dados vindos do PublicVenueController.
 * Galeria e informações vêm dos partials venue_gallery e venue_details (também na Visão geral).
 */
use App\Core\VenueAccess;

$lang = require BASE_PATH . '/includes/i18n.php';
$venueId = (int)$venue['id'];
$e = fn($s) => htmlspecialchars((string)$s);
$place = implode(', ', array_filter([$venue['neighborhood'] ?? null, $venue['city'] ?? null]));

$pageTitle = $venue['name'] . ' · Kolae';
$description = trim((string)$venue['description']);
$pageDescription = $description !== ''
    ? mb_strimwidth(preg_replace('/\s+/', ' ', $description), 0, 160, '…')
    : strtr($lang['public_description_default'], ['{place}' => $place ?: 'Kolae']);
$ogImage = $coverUrl ?? '';
$anchorBase = BASE_URL . '/'; // âncoras do cabeçalho e do rodapé levam à home

// Selo de status ao vivo, igual ao app (D40)
$statusKey = $status['status'] ?? 'closed';
$statusClass = ['available' => 'status-badge--available', 'in_use' => 'status-badge--in-use', 'closed' => 'status-badge--closed'][$statusKey] ?? 'status-badge--closed';
$untilText = VenueAccess::statusUntilText($status, $lang);

// Preço como no app (D31)
$price = $venue['average_price_per_hour'];
if ($price === null) {
    $priceText = $lang['overview_price_none'];
} elseif ((float)$price == 0) {
    $priceText = $lang['data_price_free'];
} else {
    $priceText = 'R$ ' . number_format((float)$price, 2, ',', '.');
}

$whatsapp = preg_replace('/\D/', '', (string)($venue['owner_whatsapp'] ?? ''));
$whatsappUrl = $whatsapp !== ''
    ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode(strtr($lang['public_whatsapp_message'], ['{venue}' => $venue['name']]))
    : null;
$ownerName = trim((string)$venue['owner_name']);
$ownerSince = !empty($venue['owner_created_at']) ? (new DateTimeImmutable($venue['owner_created_at']))->format('m/Y') : null;
$nowTs = $now->getTimestamp();

$galleryEmpty = '<div class="flex flex-col items-center justify-center gap-2 text-center aspect-[16/7] rounded-2xl bg-gradient-to-br from-brand-500 to-brand-800 text-white/90 px-6">'
    . '<span class="text-4xl"><i class="fas fa-volleyball"></i></span>'
    . '<span class="text-sm">' . $lang['public_no_photos'] . '</span></div>';
$editLinks = null;
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>" class="scroll-smooth">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased overflow-x-hidden">
    <?php include BASE_PATH . '/app/views/partials/public_header.php'; ?>

    <section class="bg-neutral-950 text-white pt-28 pb-8">
        <div class="container mx-auto px-4 sm:px-6 max-w-5xl">
            <a href="<?php echo BASE_URL; ?>/#quadras" class="inline-flex items-center gap-2 text-sm font-semibold text-white/70 hover:text-white transition-colors">
                <span><i class="fas fa-arrow-left"></i></span><?php echo $lang['public_back']; ?>
            </a>
            <div class="mt-4 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-3xl sm:text-4xl font-semibold"><?php echo $e($venue['name']); ?></h1>
                    <?php if ($place !== ''): ?>
                        <p class="mt-2 text-white/70 flex items-center gap-2"><span class="text-brand-300"><i class="fas fa-location-dot"></i></span><?php echo $e($place); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $e($lang['home_status_' . $statusKey] ?? $statusKey); ?><?php if ($untilText !== ''): ?> <span class="font-normal opacity-80">· <?php echo $e($untilText); ?></span><?php endif; ?></span>
                    <button type="button" id="share-btn" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10 transition-colors" data-copied="<?php echo $e($lang['public_link_copied']); ?>">
                        <span><i class="fas fa-share-nodes"></i></span><span class="label"><?php echo $lang['public_share']; ?></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-4 sm:px-6 max-w-5xl py-8 md:py-10">
        <?php include BASE_PATH . '/app/views/partials/venue_gallery.php'; ?>

        <div class="mt-10 lg:grid lg:grid-cols-[1fr_21rem] lg:gap-10">
            <?php include BASE_PATH . '/app/views/partials/venue_details.php'; ?>

            <aside class="mt-10 lg:mt-0">
                <div class="lg:sticky lg:top-24 space-y-5">
                    <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 shadow-sm">
                        <p class="text-2xl font-bold">
                            <?php echo $e($priceText); ?>
                            <?php if ($price !== null && (float)$price > 0): ?><span class="text-sm font-normal text-content-secondary"><?php echo $lang['overview_per_hour']; ?></span><?php endif; ?>
                        </p>

                        <div class="mt-5 pt-5 border-t border-content-secondary/10">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="font-semibold"><?php echo $lang['overview_today']; ?></h2>
                                <span class="text-sm text-content-secondary">
                                    <?php echo $todayHours
                                        ? $e(strtr($lang['overview_today_hours'], ['{opens}' => substr($todayHours['opens_at'], 0, 5), '{closes}' => substr($todayHours['closes_at'], 0, 5)]))
                                        : $lang['overview_closed_today']; ?>
                                </span>
                            </div>
                            <?php if ($todayHours): ?>
                                <?php if ($busy): ?>
                                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-content-secondary"><?php echo $lang['public_today_busy']; ?></p>
                                    <ul class="mt-2 flex flex-wrap gap-2">
                                        <?php foreach ($busy as $m):
                                            $start = new DateTimeImmutable($m['start_time']);
                                            $end = new DateTimeImmutable($m['end_time']);
                                            $ongoing = $start->getTimestamp() <= $nowTs; ?>
                                            <li class="text-sm tabular-nums rounded-lg px-2.5 py-1 <?php echo $ongoing ? 'status-badge status-badge--in-use' : 'bg-surface-secondary'; ?>"><?php echo $start->format('H:i') . '–' . $end->format('H:i'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['public_today_free']; ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($whatsappUrl): ?>
                            <a href="<?php echo $e($whatsappUrl); ?>" target="_blank" rel="noopener" class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#1ebe5b] text-neutral-950 font-semibold py-3 rounded-xl transition-colors">
                                <span class="text-lg"><i class="fab fa-whatsapp"></i></span><?php echo $lang['public_whatsapp_btn']; ?>
                            </a>
                            <p class="mt-2 text-xs text-center text-content-secondary"><?php echo $lang['public_whatsapp_hint']; ?></p>
                        <?php else: ?>
                            <p class="mt-5 p-3 rounded-xl bg-surface-secondary text-sm text-content-secondary"><?php echo $lang['public_no_contact']; ?></p>
                        <?php endif; ?>
                    </section>

                    <section class="bg-surface-elevated border border-content-secondary/10 rounded-2xl p-5 shadow-sm flex items-center gap-4">
                        <?php if ($ownerAvatarUrl): ?>
                            <img src="<?php echo $e($ownerAvatarUrl); ?>" alt="" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <span class="w-12 h-12 rounded-full bg-brand-500 text-white flex items-center justify-center font-semibold text-lg"><?php echo $e(mb_strtoupper(mb_substr($ownerName ?: 'K', 0, 1))); ?></span>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="text-xs text-content-secondary"><?php echo $lang['public_owner_title']; ?></p>
                            <p class="font-semibold truncate"><?php echo $e($ownerName); ?></p>
                            <?php if ($ownerSince): ?><p class="text-xs text-content-secondary"><?php echo $e(strtr($lang['public_owner_since'], ['{date}' => $ownerSince])); ?></p><?php endif; ?>
                        </div>
                    </section>
                </div>
            </aside>
        </div>
    </main>

    <?php include BASE_PATH . '/app/views/partials/public_footer.php'; ?>

    <script>
        // Compartilhar: menu do celular quando existe; senão, copia o link
        (function () {
            const btn = document.getElementById('share-btn');
            const label = btn.querySelector('.label');
            const original = label.textContent;
            const copy = async () => {
                try {
                    await navigator.clipboard.writeText(location.href);
                    label.textContent = btn.dataset.copied;
                    setTimeout(() => { label.textContent = original; }, 2000);
                } catch (e) {}
            };
            btn.addEventListener('click', async () => {
                if (!navigator.share) return copy();
                try {
                    await navigator.share({ title: document.title, url: location.href });
                } catch (e) {
                    // Cancelar o menu não copia; outro erro (sem permissão etc.) cai na cópia
                    if (e.name !== 'AbortError') copy();
                }
            });
        })();
    </script>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
