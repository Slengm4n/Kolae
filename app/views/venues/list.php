<?php
/**
 * Página pública "Quadras" (D51): busca, filtros, lista e mapa. Cada quadra abre a página
 * pública dela (D49). Status e filtros são os mesmos da API (VenueApiController::listVenues).
 * Mapa com Leaflet e OpenStreetMap: não depende de chave do Google Maps.
 */
use App\Controllers\PublicVenueController;
use App\Core\VenueAccess;

$lang = require BASE_PATH . '/includes/i18n.php';
$e = fn($s) => htmlspecialchars((string)$s);
$pageTitle = $lang['list_title'] . ' · Kolae';
$pageDescription = $lang['list_subtitle'];
$anchorBase = BASE_URL . '/'; // âncoras do cabeçalho e do rodapé levam à home
$action = BASE_URL . '/quadras';
$hasFilters = $form['q'] !== '' || $form['sport_id'] || $form['agora'] || $form['coberta'] || $form['iluminacao'] || $form['preco'] || $form['perto'];
$count = count($venues);

$statusClass = ['available' => 'status-badge--available', 'in_use' => 'status-badge--in-use', 'closed' => 'status-badge--closed'];
$untilText = function (array $v) use ($lang): string {
    $until = $v['status_until'] ? new DateTimeImmutable($v['status_until']) : null;
    return VenueAccess::statusUntilText(['status' => $v['status'], 'status_until' => $until], $lang);
};
$priceText = function (?int $cents) use ($lang): string {
    if ($cents === null) return $lang['overview_price_none'];
    if ($cents === 0) return $lang['data_price_free'];
    return 'R$ ' . number_format($cents / 100, 2, ',', '.');
};

// Pontos do mapa (só quadras com coordenadas)
$mapPoints = [];
foreach ($venues as $v) {
    if ($v['latitude'] === null || $v['longitude'] === null) continue;
    $mapPoints[] = [
        'id' => $v['id'],
        'name' => $v['name'],
        'lat' => $v['latitude'],
        'lng' => $v['longitude'],
        'status' => $v['status'],
        'label' => $lang['home_status_' . $v['status']] ?? $v['status'],
        'url' => BASE_URL . '/quadra/' . $v['id'],
    ];
}
$pill = 'inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold cursor-pointer select-none transition-colors border-white/20 text-white/85 hover:bg-white/10 has-[:checked]:bg-white has-[:checked]:text-neutral-900 has-[:checked]:border-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-400';
$select = 'rounded-full border border-white/20 bg-neutral-900 text-white/90 px-3.5 py-2 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400';
?>
<!DOCTYPE html>
<html lang="<?php echo $e($_SESSION['idioma'] ?? 'pt-br'); ?>" class="scroll-smooth">
<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
</head>
<body class="bg-surface-base text-content-primary font-sans antialiased overflow-x-hidden">
    <?php include BASE_PATH . '/app/views/partials/public_header.php'; ?>

    <section class="bg-neutral-950 text-white pt-28 pb-8">
        <div class="container mx-auto px-4 sm:px-6 max-w-7xl">
            <h1 class="text-3xl sm:text-4xl font-semibold"><?php echo $lang['list_heading']; ?></h1>
            <p class="mt-2 text-white/70"><?php echo $lang['list_subtitle']; ?></p>

            <form id="filters" method="GET" action="<?php echo $action; ?>" class="mt-6" role="search">
                <div class="flex gap-2">
                    <label for="q" class="sr-only"><?php echo $lang['list_search_ph']; ?></label>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-white/50"><i class="fas fa-magnifying-glass"></i></span>
                        <input id="q" name="q" type="search" maxlength="100" value="<?php echo $e($form['q']); ?>" placeholder="<?php echo $e($lang['list_search_ph']); ?>"
                            class="w-full rounded-xl bg-white/10 border border-white/15 pl-11 pr-4 py-3 text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-brand-400">
                    </div>
                    <button type="submit" class="rounded-xl bg-brand-600 hover:bg-brand-700 px-5 font-semibold"><?php echo $lang['list_search_btn']; ?></button>
                </div>

                <fieldset class="mt-4 flex flex-wrap items-center gap-2">
                    <legend class="sr-only"><?php echo $lang['list_filters_label']; ?></legend>
                    <label class="sr-only" for="sport_id"><?php echo $lang['overview_sports']; ?></label>
                    <select id="sport_id" name="sport_id" class="<?php echo $select; ?> auto-submit">
                        <option value=""><?php echo $lang['list_sport_all']; ?></option>
                        <?php foreach ($sports as $s): ?>
                            <option value="<?php echo (int)$s['id']; ?>" <?php echo (int)$s['id'] === $form['sport_id'] ? 'selected' : ''; ?>><?php echo $e($s['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="<?php echo $pill; ?>"><input type="checkbox" name="agora" value="1" class="sr-only auto-submit" <?php echo $form['agora'] ? 'checked' : ''; ?>><span class="w-2 h-2 rounded-full bg-brand-400"></span><?php echo $lang['list_now']; ?></label>
                    <label class="<?php echo $pill; ?>"><input type="checkbox" name="coberta" value="1" class="sr-only auto-submit" <?php echo $form['coberta'] ? 'checked' : ''; ?>><i class="fas fa-house"></i><?php echo $lang['overview_covered']; ?></label>
                    <label class="<?php echo $pill; ?>"><input type="checkbox" name="iluminacao" value="1" class="sr-only auto-submit" <?php echo $form['iluminacao'] ? 'checked' : ''; ?>><i class="fas fa-lightbulb"></i><?php echo $lang['overview_lighting']; ?></label>
                    <label class="sr-only" for="preco"><?php echo $lang['list_price_label']; ?></label>
                    <select id="preco" name="preco" class="<?php echo $select; ?> auto-submit">
                        <option value=""><?php echo $lang['list_price_any']; ?></option>
                        <?php foreach (PublicVenueController::PRICE_OPTIONS as $p): ?>
                            <option value="<?php echo $p; ?>" <?php echo $p === $form['preco'] ? 'selected' : ''; ?>><?php echo $e(strtr($lang['list_price_max'], ['{n}' => $p])); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($coords): ?>
                        <input type="hidden" name="lat" value="<?php echo $e($coords['lat']); ?>">
                        <input type="hidden" name="lng" value="<?php echo $e($coords['lng']); ?>">
                    <?php endif; ?>
                    <button type="button" id="near-btn" class="inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold transition-colors <?php echo $form['perto'] ? 'bg-white text-neutral-900 border-white' : 'border-white/20 text-white/85 hover:bg-white/10'; ?>" aria-pressed="<?php echo $form['perto'] ? 'true' : 'false'; ?>">
                        <i class="fas fa-location-crosshairs"></i><span class="label"><?php echo $form['perto'] ? $lang['list_near_on'] : $lang['list_near']; ?></span>
                    </button>
                    <?php if ($hasFilters): ?>
                        <a href="<?php echo $action; ?>" class="ml-1 text-sm font-semibold text-white/70 hover:text-white underline underline-offset-4"><?php echo $lang['list_clear']; ?></a>
                    <?php endif; ?>
                </fieldset>
                <p id="near-error" class="hidden mt-3 text-sm text-amber-300" role="alert"><?php echo $lang['list_near_error']; ?></p>
            </form>
        </div>
    </section>

    <main class="container mx-auto px-4 sm:px-6 max-w-7xl py-6 md:py-8">
        <?php $adHighlightsClass = 'mb-8'; include BASE_PATH . '/app/views/partials/ad_highlights.php'; ?>
        <div class="flex items-center justify-between gap-4">
            <p class="font-semibold" aria-live="polite"><?php echo $e(strtr($lang[$count === 1 ? 'list_count_one' : 'list_count_many'], ['{n}' => $count])); ?></p>
            <?php if ($mapPoints): ?>
                <div class="lg:hidden inline-flex p-1 rounded-xl bg-surface-secondary border border-content-secondary/10" role="group" aria-label="<?php echo $e($lang['list_map_label']); ?>">
                    <button type="button" class="view-btn px-4 py-1.5 rounded-lg text-sm font-semibold bg-surface-elevated shadow-sm" data-view="list" aria-pressed="true"><i class="fas fa-list mr-1.5"></i><?php echo $lang['list_view_list']; ?></button>
                    <button type="button" class="view-btn px-4 py-1.5 rounded-lg text-sm font-semibold text-content-secondary" data-view="map" aria-pressed="false"><i class="fas fa-map mr-1.5"></i><?php echo $lang['list_view_map']; ?></button>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-5 lg:grid <?php echo $mapPoints ? 'lg:grid-cols-[1fr_26rem] xl:grid-cols-[1fr_32rem]' : ''; ?> lg:gap-8">
            <div id="list-view">
                <?php if (!$venues): ?>
                    <div class="py-16 text-center rounded-2xl border-2 border-dashed border-content-secondary/20">
                        <span class="text-4xl text-content-secondary/50"><i class="fas fa-magnifying-glass-location"></i></span>
                        <p class="mt-4 font-semibold"><?php echo $lang['list_empty_title']; ?></p>
                        <p class="mt-1 text-sm text-content-secondary"><?php echo $lang['list_empty_text']; ?></p>
                        <?php if ($hasFilters): ?><a href="<?php echo $action; ?>" class="mt-4 inline-block font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['list_clear']; ?></a><?php endif; ?>
                    </div>
                <?php else: ?>
                    <ul class="grid sm:grid-cols-2 gap-5">
                        <?php foreach ($venues as $v):
                            $place = implode(', ', array_filter([$v['neighborhood'], $v['city']]));
                            $until = $untilText($v); ?>
                            <li class="venue-card group relative overflow-hidden rounded-2xl border border-content-secondary/15 bg-surface-elevated shadow-sm hover:shadow-lg hover:border-brand-500/40 transition focus-within:ring-2 focus-within:ring-brand-500" data-venue-id="<?php echo (int)$v['id']; ?>">
                                <div class="relative aspect-[16/10] bg-gradient-to-br from-brand-500 to-brand-800 overflow-hidden">
                                    <?php if ($v['cover_image_url']): ?>
                                        <img src="<?php echo $e(\App\Core\ImageHelper::thumbUrl($v['cover_image_url'])); ?>" alt="" loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center text-white/80 text-4xl"><i class="fas fa-volleyball"></i></div>
                                    <?php endif; ?>
                                    <span class="absolute left-3 top-3 status-badge <?php echo $statusClass[$v['status']] ?? 'status-badge--closed'; ?> shadow"><?php echo $e($lang['home_status_' . $v['status']] ?? $v['status']); ?><?php if ($until !== ''): ?> <span class="font-normal opacity-80">· <?php echo $e($until); ?></span><?php endif; ?></span>
                                    <?php if ($v['distance_km'] !== null): ?>
                                        <span class="absolute right-3 top-3 text-xs font-semibold bg-black/60 text-white rounded-full px-2.5 py-1"><?php echo $e(strtr($lang['list_distance'], ['{km}' => number_format($v['distance_km'], 1, ',', '.')])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="p-4">
                                    <h2 class="font-semibold text-lg leading-snug"><a href="<?php echo BASE_URL . '/quadra/' . (int)$v['id']; ?>" class="after:absolute after:inset-0 focus:outline-none"><?php echo $e($v['name']); ?></a></h2>
                                    <?php if ($place !== ''): ?><p class="mt-0.5 text-sm text-content-secondary truncate"><i class="fas fa-location-dot text-brand-500 mr-1"></i><?php echo $e($place); ?></p><?php endif; ?>
                                    <?php if ($v['sports']): ?>
                                        <ul class="mt-3 flex flex-wrap gap-1.5 text-xs font-medium">
                                            <?php foreach (array_slice($v['sports'], 0, 3) as $s): ?>
                                                <li class="rounded-full bg-brand-500/10 text-brand-700 dark:text-brand-300 px-2.5 py-1"><?php echo $e($s['name']); ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($v['sports']) > 3): ?><li class="rounded-full bg-surface-secondary text-content-secondary px-2.5 py-1">+<?php echo count($v['sports']) - 3; ?></li><?php endif; ?>
                                        </ul>
                                    <?php endif; ?>
                                    <p class="mt-3 pt-3 border-t border-content-secondary/10 text-sm text-content-secondary">
                                        <span class="font-semibold text-content-primary text-base"><?php echo $e($priceText($v['price_per_hour_cents'])); ?></span><?php if ($v['price_per_hour_cents']): ?> <?php echo $lang['overview_per_hour']; ?><?php endif; ?>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if ($mapPoints): ?>
                <div id="map-view" class="hidden lg:block mt-0">
                    <div id="venues-map" class="lg:sticky lg:top-24 h-[calc(100vh-12rem)] lg:h-[calc(100vh-8rem)] min-h-[24rem] rounded-2xl overflow-hidden border border-content-secondary/15 z-0" role="region" aria-label="<?php echo $e($lang['list_map_label']); ?>"></div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include BASE_PATH . '/app/views/partials/public_footer.php'; ?>

    <style>
        .venue-pin { display: block; width: 18px; height: 18px; border-radius: 9999px; border: 3px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.4); }
        .venue-pin--available { background: #0084FF; }
        .venue-pin--in_use { background: #D32F2F; }
        .venue-pin--closed { background: #8A8A8A; }
        .venue-pin.is-active { transform: scale(1.4); }
        .me-pin { display: block; width: 16px; height: 16px; border-radius: 9999px; background: #111; border: 3px solid #fff; box-shadow: 0 0 0 6px rgba(0,132,255,.25); }
        .leaflet-popup-content a { font-weight: 600; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
        (function () {
            const form = document.getElementById('filters');
            // Filtros aplicam na hora (o botão Buscar fica para quem navega sem JavaScript)
            form.querySelectorAll('.auto-submit').forEach(el => el.addEventListener('change', () => form.submit()));

            // Perto de mim: pega a posição do navegador e ordena por distância
            const near = document.getElementById('near-btn');
            near.addEventListener('click', () => {
                if (near.getAttribute('aria-pressed') === 'true') {
                    form.querySelectorAll('input[name=lat], input[name=lng]').forEach(i => i.remove());
                    form.submit();
                    return;
                }
                if (!navigator.geolocation) {
                    document.getElementById('near-error').classList.remove('hidden');
                    return;
                }
                near.disabled = true;
                navigator.geolocation.getCurrentPosition(pos => {
                    for (const [name, value] of [['lat', pos.coords.latitude.toFixed(5)], ['lng', pos.coords.longitude.toFixed(5)]]) {
                        const input = document.createElement('input');
                        input.type = 'hidden'; input.name = name; input.value = value;
                        form.appendChild(input);
                    }
                    form.submit();
                }, () => {
                    near.disabled = false;
                    document.getElementById('near-error').classList.remove('hidden');
                }, { timeout: 10000 });
            });

            // Mapa
            const points = <?php echo json_encode($mapPoints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
            const mapEl = document.getElementById('venues-map');
            if (!mapEl || !window.L) return;
            const map = L.map(mapEl, { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            const markers = {};
            const bounds = [];
            points.forEach(p => {
                const icon = L.divIcon({ className: '', html: '<span class="venue-pin venue-pin--' + p.status + '"></span>', iconSize: [18, 18], iconAnchor: [9, 9] });
                const marker = L.marker([p.lat, p.lng], { icon, title: p.name, keyboard: true }).addTo(map);
                // Nome e status entram como texto, nunca como HTML
                const box = document.createElement('div');
                const name = document.createElement('strong'); name.textContent = p.name;
                const status = document.createElement('div'); status.textContent = p.label;
                const link = document.createElement('a'); link.href = p.url; link.textContent = <?php echo json_encode($lang['list_see_venue'], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE); ?> + ' →';
                box.append(name, status, link);
                marker.bindPopup(box);
                markers[p.id] = marker;
                bounds.push([p.lat, p.lng]);
            });
            <?php if ($coords): ?>
            const me = [<?php echo (float)$coords['lat']; ?>, <?php echo (float)$coords['lng']; ?>];
            L.marker(me, { icon: L.divIcon({ className: '', html: '<span class="me-pin"></span>', iconSize: [16, 16], iconAnchor: [8, 8] }), title: <?php echo json_encode($lang['list_you_are_here'], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE); ?> }).addTo(map);
            bounds.push(me);
            <?php endif; ?>
            if (bounds.length === 1) map.setView(bounds[0], 15); else map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });

            // Passar o mouse num card destaca o ponto no mapa
            document.querySelectorAll('.venue-card').forEach(card => {
                const pin = () => markers[card.dataset.venueId]?.getElement()?.querySelector('.venue-pin');
                card.addEventListener('mouseenter', () => pin()?.classList.add('is-active'));
                card.addEventListener('mouseleave', () => pin()?.classList.remove('is-active'));
            });

            // Celular: alternar entre lista e mapa
            const listView = document.getElementById('list-view');
            const mapView = document.getElementById('map-view');
            document.querySelectorAll('.view-btn').forEach(btn => btn.addEventListener('click', () => {
                const showMap = btn.dataset.view === 'map';
                listView.classList.toggle('hidden', showMap);
                mapView.classList.toggle('hidden', !showMap);
                document.querySelectorAll('.view-btn').forEach(b => {
                    const active = b === btn;
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                    b.classList.toggle('bg-surface-elevated', active);
                    b.classList.toggle('shadow-sm', active);
                    b.classList.toggle('text-content-secondary', !active);
                });
                if (showMap) {
                    map.invalidateSize();
                    if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                }
            }));
        })();
    </script>
    <script type="module" src="<?php echo \App\Core\ViewHelper::asset('assets/js/bundle.js'); ?>"></script>
</body>
</html>
