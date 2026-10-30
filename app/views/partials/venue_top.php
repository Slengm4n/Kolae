<?php
/**
 * Topo das páginas de gerenciar uma quadra (D35): voltar ao painel, nome, selo de status
 * ao vivo (mesma regra da API, D40) e as abas Dados, Horário e Agenda.
 *
 * Variáveis: $lang, $venue, $status (VenueAccess::currentStatus), $activeTab ('data' | 'hours' | 'agenda'),
 *            $routePrefix ('/dashboard' | '/admin'), $backUrl.
 */
use App\Core\VenueSchedule;

$statusKey = $status['status'] ?? 'closed';
$statusClass = ['available' => 'status-badge--available', 'in_use' => 'status-badge--in-use', 'closed' => 'status-badge--closed'][$statusKey] ?? 'status-badge--closed';
$statusLabel = $lang['home_status_' . $statusKey] ?? $statusKey;

// "até 19:00", "abre 08:00" ou "abre Seg 08:00"
$untilText = '';
if (!empty($status['status_until'])) {
    $until = $status['status_until'];
    $time = $until->format('H:i');
    $sameDay = $until->format('Y-m-d') === VenueSchedule::now()->format('Y-m-d');
    $when = $sameDay ? $time : ($lang['weekday_short_' . $until->format('w')] . ' ' . $time);
    $untilText = ($statusKey === 'closed' ? $lang['venue_status_opens'] : $lang['venue_status_until']) . ' ' . $when;
}

$venueId = (int)$venue['id'];
$base = BASE_URL . $routePrefix . '/quadras';
$tabs = [
    'data' => ['label' => $lang['venue_tab_data'], 'icon' => 'fa-pen', 'url' => "$base/editar/$venueId"],
    'hours' => ['label' => $lang['venue_tab_hours'], 'icon' => 'fa-clock', 'url' => "$base/$venueId/horario"],
    'agenda' => ['label' => $lang['venue_tab_agenda'], 'icon' => 'fa-calendar-days', 'url' => $agendaUrl ?? null],
];
$place = implode(' · ', array_filter([$venue['neighborhood'] ?? null, $venue['city'] ?? null]));
?>
<div class="mb-6">
    <a href="<?php echo BASE_URL . $backUrl; ?>" class="inline-flex items-center gap-2 text-sm font-semibold text-content-secondary hover:text-brand-600 dark:hover:text-brand-400 transition-colors">
        <span><i class="fas fa-arrow-left"></i></span> <?php echo $lang['venue_back_panel']; ?>
    </a>

    <div class="mt-3 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-2xl md:text-3xl font-bold text-content-primary truncate"><?php echo htmlspecialchars($venue['name']); ?></h1>
            <?php if ($place !== ''): ?>
                <p class="text-sm text-content-secondary mt-1 truncate"><?php echo htmlspecialchars($place); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 shrink-0" title="<?php echo htmlspecialchars($lang['venue_status_now']); ?>">
            <span class="text-xs text-content-secondary hidden sm:inline"><?php echo $lang['venue_status_now']; ?>:</span>
            <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusLabel); ?><?php if ($untilText !== ''): ?> <span class="font-normal opacity-80">· <?php echo htmlspecialchars($untilText); ?></span><?php endif; ?></span>
        </div>
    </div>

    <nav class="mt-5 flex gap-1 p-1 bg-surface-secondary border border-content-secondary/10 rounded-xl overflow-x-auto" aria-label="<?php echo htmlspecialchars($lang['venue_tabs_label']); ?>">
        <?php foreach ($tabs as $key => $tab):
            $active = $key === $activeTab; ?>
            <?php if ($tab['url'] === null): ?>
                <span class="flex-1 min-w-max flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-content-secondary/60 cursor-not-allowed" aria-disabled="true">
                    <span><i class="fas <?php echo $tab['icon']; ?>"></i></span><?php echo $tab['label']; ?>
                    <span class="text-[10px] uppercase tracking-wide bg-content-secondary/10 rounded px-1.5 py-0.5"><?php echo $lang['venue_tab_soon']; ?></span>
                </span>
            <?php else: ?>
                <a href="<?php echo $tab['url']; ?>"
                    class="flex-1 min-w-max flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-colors <?php echo $active ? 'bg-surface-elevated text-brand-600 dark:text-brand-400 shadow-sm' : 'text-content-secondary hover:text-content-primary hover:bg-surface-elevated/60'; ?>"
                    <?php echo $active ? 'aria-current="page"' : ''; ?>>
                    <span><i class="fas <?php echo $tab['icon']; ?>"></i></span><?php echo $tab['label']; ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</div>
