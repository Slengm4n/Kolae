<?php
/**
 * Informações da quadra como no app (D47): esportes, estrutura, "Sobre a quadra", horário
 * da semana e endereço. Usada na Visão geral (painel) e na página pública da quadra.
 *
 * Variáveis: $lang, $venue, $sports, $summary (VenueHoursController::weekSummary),
 *            $editLinks (['editar' => url, 'horario' => url] no painel; null na página pública).
 */
use App\Models\Venue;

$de = fn($s) => htmlspecialchars((string)$s);
$editLinks = $editLinks ?? null;
$addLink = fn() => $editLinks
    ? ' <a href="' . $editLinks['editar'] . '" class="font-semibold text-brand-600 dark:text-brand-400 hover:underline">' . $lang['overview_add'] . '</a>'
    : '';

// Estrutura: o que a quadra tem (o que não tem fica apagado)
$floorKey = Venue::FLOOR_LANG_KEYS[$venue['floor_type'] ?? ''] ?? null;
$facts = [];
if ($floorKey && isset($lang[$floorKey])) {
    $facts[] = ['fa-layer-group', $lang[$floorKey]];
}
$facts[] = [$venue['is_covered'] ? 'fa-house' : 'fa-sun', $venue['is_covered'] ? $lang['overview_covered'] : $lang['overview_uncovered']];
$facts[] = ['fa-lightbulb', $venue['has_lighting'] ? $lang['overview_lighting'] : $lang['overview_no_lighting'], !$venue['has_lighting']];
if ((int)$venue['court_capacity'] > 0) {
    $facts[] = ['fa-users', strtr($lang['overview_capacity'], ['{n}' => (int)$venue['court_capacity']])];
}
if ($venue['has_leisure_area']) {
    $facts[] = ['fa-umbrella-beach', (int)$venue['leisure_area_capacity'] > 0
        ? strtr($lang['overview_leisure_capacity'], ['{n}' => (int)$venue['leisure_area_capacity']])
        : $lang['overview_leisure']];
}

// "Seg a Sex", "Sáb" (igual à aba Horário)
$dayLabel = function (array $days) use ($lang): string {
    $first = $lang['weekday_short_' . $days[0]];
    if (count($days) === 1) return $first;
    $last = $lang['weekday_short_' . end($days)];
    return count($days) === 2 ? "$first, $last" : "$first {$lang['weekday_range_sep']} $last";
};
$hasHours = (bool)array_filter($summary, fn($g) => $g['opens_at'] !== null);

$addressLine = implode(', ', array_filter([trim(($venue['street'] ?? '') . ' ' . ($venue['number'] ?? '')), $venue['complement'] ?? null]));
$cityLine = implode(' · ', array_filter([$venue['neighborhood'] ?? null, trim(($venue['city'] ?? '') . (!empty($venue['state']) ? ' - ' . $venue['state'] : ''))]));
$mapsUrl = (!empty($venue['latitude']) && !empty($venue['longitude']))
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($venue['latitude'] . ',' . $venue['longitude'])
    : null;
?>
<div class="space-y-8">
    <section>
        <h2 class="text-lg font-bold"><?php echo $lang['overview_sports']; ?></h2>
        <?php if ($sports): ?>
            <ul class="mt-3 flex flex-wrap gap-2">
                <?php foreach ($sports as $sport): ?>
                    <li class="inline-flex items-center gap-2 rounded-full bg-brand-500/10 text-brand-700 dark:text-brand-300 px-3 py-1.5 text-sm font-medium">
                        <?php if (!empty($sport['icon_path'])): ?><span><i class="fas <?php echo $de($sport['icon_path']); ?>"></i></span><?php endif; ?><?php echo $de($sport['name']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['overview_no_sports'] . $addLink(); ?></p>
        <?php endif; ?>
    </section>

    <section class="pt-8 border-t border-content-secondary/10">
        <h2 class="text-lg font-bold"><?php echo $lang['overview_facts_title']; ?></h2>
        <ul class="mt-4 grid sm:grid-cols-2 gap-x-6 gap-y-3">
            <?php foreach ($facts as $fact): ?>
                <li class="flex items-center gap-3 <?php echo !empty($fact[2]) ? 'text-content-secondary' : ''; ?>">
                    <span class="w-9 h-9 shrink-0 rounded-xl bg-surface-secondary flex items-center justify-center text-content-secondary"><i class="fas <?php echo $fact[0]; ?>"></i></span>
                    <span class="text-sm"><?php echo $de($fact[1]); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (trim((string)$venue['description']) !== '' || $editLinks): ?>
        <section class="pt-8 border-t border-content-secondary/10">
            <h2 class="text-lg font-bold"><?php echo $lang['data_description']; ?></h2>
            <?php if (trim((string)$venue['description']) !== ''): ?>
                <p class="mt-3 text-content-secondary leading-relaxed whitespace-pre-line"><?php echo $de($venue['description']); ?></p>
            <?php else: ?>
                <p class="mt-2 text-sm text-content-secondary"><?php echo $lang['overview_no_description'] . $addLink(); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="pt-8 border-t border-content-secondary/10">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-bold"><?php echo $lang['overview_hours_title']; ?></h2>
            <?php if ($editLinks): ?>
                <a href="<?php echo $editLinks['horario']; ?>" class="text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline"><?php echo $lang['overview_edit']; ?></a>
            <?php endif; ?>
        </div>
        <?php if (!$hasHours): ?>
            <p class="mt-2 text-sm text-content-secondary"><?php echo $editLinks ? $lang['hours_summary_empty'] : $lang['public_hours_none']; ?></p>
        <?php else: ?>
            <dl class="mt-3 grid grid-cols-[auto_1fr] gap-x-8 gap-y-2 text-sm">
                <?php foreach ($summary as $group): ?>
                    <dt class="font-semibold"><?php echo $de($dayLabel($group['days'])); ?></dt>
                    <dd class="<?php echo $group['opens_at'] ? '' : 'text-content-secondary'; ?>"><?php echo $group['opens_at'] ? $group['opens_at'] . '–' . $group['closes_at'] : $lang['hours_closed']; ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </section>

    <section class="pt-8 border-t border-content-secondary/10">
        <h2 class="text-lg font-bold"><?php echo $lang['overview_address_title']; ?></h2>
        <p class="mt-3"><?php echo $de($addressLine); ?></p>
        <p class="text-sm text-content-secondary"><?php echo $de($cityLine); ?><?php echo !empty($venue['cep']) ? ' · ' . $de($venue['cep']) : ''; ?></p>
        <?php if ($mapsUrl): ?>
            <a href="<?php echo $de($mapsUrl); ?>" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 dark:text-brand-400 hover:underline">
                <span><i class="fas fa-map-location-dot"></i></span><?php echo $lang['overview_open_maps']; ?>
            </a>
        <?php endif; ?>
    </section>
</div>
