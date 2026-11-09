<?php
/**
 * Card "Patrocinado" de um anúncio (D63). O mesmo card aparece na prévia da aba Anúncio e,
 * para o jogador, no topo da home, da /quadras e na página pública da quadra.
 *
 * Variáveis: $lang e $adCard = [
 *   'title', 'body', 'image' (URL ou null), 'ends_on' (YYYY-MM-DD),
 *   'venue' (nome da quadra), 'place' (bairro · cidade, opcional), 'url' (link do card, opcional)
 * ]. Os data-ad-* deixam a prévia da aba Anúncio atualizar o card enquanto o dono digita.
 */
$acE = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$acTag = !empty($adCard['url']) ? 'a' : 'div';
$acImage = $adCard['image'] ?? null;
$acEnds = !empty($adCard['ends_on']) ? strtr($lang['ad_until'], ['{date}' => date('d/m', strtotime($adCard['ends_on']))]) : '';
$acVenue = implode(' · ', array_filter([$adCard['venue'] ?? null, $adCard['place'] ?? null]));
?>
<<?php echo $acTag; ?> <?php echo $acTag === 'a' ? 'href="' . $acE($adCard['url']) . '"' : ''; ?> data-ad-card
    class="group flex flex-col h-full overflow-hidden rounded-2xl border border-amber-400/40 bg-surface-elevated shadow-sm <?php echo $acTag === 'a' ? 'hover:shadow-md hover:border-amber-400/70 transition' : ''; ?>">
    <div class="relative aspect-[16/9] bg-surface-secondary">
        <img data-ad-image src="<?php echo $acE($acImage ?? ''); ?>" alt="" loading="lazy" class="w-full h-full object-cover" <?php echo $acImage ? '' : 'hidden'; ?>>
        <div data-ad-noimage class="absolute inset-0 flex items-center justify-center text-3xl text-content-secondary/40" <?php echo $acImage ? 'hidden' : ''; ?>><i class="fas fa-bullhorn" aria-hidden="true"></i></div>
        <span class="absolute left-3 top-3 inline-flex items-center gap-1 rounded-md bg-amber-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-neutral-900"><?php echo $acE($lang['ad_sponsored']); ?></span>
    </div>
    <div class="flex flex-1 flex-col p-4">
        <p class="text-xs font-semibold text-content-secondary truncate" data-ad-venue><?php echo $acE($acVenue); ?></p>
        <h3 class="mt-1 font-bold text-lg leading-snug break-words" data-ad-title><?php echo $acE($adCard['title'] ?? ''); ?></h3>
        <p class="mt-1 text-sm text-content-secondary break-words" data-ad-body><?php echo $acE($adCard['body'] ?? ''); ?></p>
        <p class="mt-auto pt-3 text-xs text-content-secondary flex items-center gap-1.5"><span><i class="far fa-clock" aria-hidden="true"></i></span><span data-ad-ends><?php echo $acE($acEnds); ?></span></p>
    </div>
</<?php echo $acTag; ?>>
