<?php
/**
 * Fileira "Destaques" com os cards "Patrocinado" do dia (D63), no topo da home e da /quadras.
 * Variáveis: $lang e $adCards (AdDisplay::cards). Sem anúncio no ar, não mostra nada.
 */
if (empty($adCards)) {
    return;
}
?>
<section id="ad-highlights" class="<?php echo $adHighlightsClass ?? ''; ?>" aria-labelledby="ad-highlights-title">
    <h2 id="ad-highlights-title" class="text-sm font-semibold uppercase tracking-widest text-content-secondary flex items-center gap-2">
        <span class="text-amber-500"><i class="fas fa-bullhorn" aria-hidden="true"></i></span><?php echo htmlspecialchars($lang['ad_highlights']); ?>
    </h2>
    <ul class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($adCards as $adCard): ?>
            <li class="min-w-0" data-ad-id="<?php echo (int)$adCard['id']; ?>"><?php include BASE_PATH . '/app/views/partials/ad_card.php'; ?></li>
        <?php endforeach; ?>
    </ul>
</section>
