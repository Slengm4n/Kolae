<?php
/**
 * Galeria da quadra (D47): grade com uma foto grande e até quatro menores no computador,
 * carrossel de arrastar no celular e tela cheia com setas, teclado e Esc.
 * Usada na Visão geral (painel) e na página pública da quadra.
 *
 * Variáveis: $lang, $images (VenueImage, na ordem), $venueId, $galleryEmpty (HTML quando não há foto).
 */
$galleryUrl = fn(array $img) => BASE_URL . '/uploads/venues/' . (int)$venueId . '/' . rawurlencode(basename($img['file_path']));
$galleryCount = count($images);
$ge = fn($s) => htmlspecialchars((string)$s);
?>
<?php if (!$images): ?>
    <?php echo $galleryEmpty; ?>
<?php else: ?>
    <section aria-label="<?php echo $ge($lang['overview_gallery_label']); ?>" class="relative">
        <!-- Celular: carrossel de arrastar -->
        <div class="md:hidden -mx-4">
            <div id="carousel" class="flex overflow-x-auto snap-x snap-mandatory scroll-smooth no-scrollbar">
                <?php foreach ($images as $i => $img): ?>
                    <button type="button" class="open-photo shrink-0 w-full snap-center aspect-[4/3] bg-surface-secondary" data-index="<?php echo $i; ?>" aria-label="<?php echo $ge(strtr($lang['photos_alt'], ['{n}' => $i + 1])); ?>">
                        <img src="<?php echo $galleryUrl($img); ?>" alt="" class="w-full h-full object-cover" <?php echo $i ? 'loading="lazy"' : ''; ?>>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php if ($galleryCount > 1): ?>
                <span id="carousel-counter" class="absolute right-3 bottom-3 text-xs font-semibold bg-black/60 text-white rounded-full px-2.5 py-1 tabular-nums" aria-live="polite">1 / <?php echo $galleryCount; ?></span>
            <?php endif; ?>
        </div>

        <!-- Computador: foto grande + até 4 menores -->
        <?php
        $small = array_slice($images, 1, 4);
        $smallSpan = [1 => ['col-span-2 row-span-2'], 2 => ['col-span-2', 'col-span-2'], 3 => ['col-span-2', '', ''], 4 => ['', '', '', '']][count($small)] ?? [];
        ?>
        <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[26rem] rounded-2xl overflow-hidden">
            <button type="button" class="open-photo group relative <?php echo $small ? 'col-span-2 row-span-2' : 'col-span-4 row-span-2'; ?> bg-surface-secondary overflow-hidden" data-index="0" aria-label="<?php echo $ge(strtr($lang['photos_alt'], ['{n}' => 1])); ?>">
                <img src="<?php echo $galleryUrl($images[0]); ?>" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
            </button>
            <?php foreach ($small as $j => $img): ?>
                <button type="button" class="open-photo group relative <?php echo $smallSpan[$j]; ?> bg-surface-secondary overflow-hidden" data-index="<?php echo $j + 1; ?>" aria-label="<?php echo $ge(strtr($lang['photos_alt'], ['{n}' => $j + 2])); ?>">
                    <img src="<?php echo \App\Core\ImageHelper::thumbUrl($galleryUrl($img)); ?>" alt="" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.03]" loading="lazy">
                </button>
            <?php endforeach; ?>
        </div>
        <?php if ($galleryCount > 1): ?>
            <button type="button" class="open-photo hidden md:inline-flex absolute right-4 bottom-4 items-center gap-2 bg-white text-neutral-900 text-sm font-semibold rounded-lg px-4 py-2 shadow-lg hover:bg-neutral-100" data-index="0">
                <span><i class="fas fa-table-cells"></i></span><?php echo strtr($lang['overview_see_all'], ['{n}' => $galleryCount]); ?>
            </button>
        <?php endif; ?>
    </section>

    <!-- Fotos em tela cheia -->
    <div id="lightbox" class="fixed inset-0 z-[60] bg-neutral-950 hidden flex-col" role="dialog" aria-modal="true" aria-label="<?php echo $ge($lang['overview_gallery_label']); ?>">
        <div class="flex items-center justify-between p-4 text-white">
            <span id="lightbox-counter" class="text-sm tabular-nums" aria-live="polite"></span>
            <button type="button" id="lightbox-close" class="w-11 h-11 rounded-full hover:bg-white/10 flex items-center justify-center text-xl" aria-label="<?php echo $ge($lang['overview_close']); ?>"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="relative flex-1 min-h-0">
            <div id="lightbox-track" class="h-full flex overflow-x-auto snap-x snap-mandatory no-scrollbar">
                <?php foreach ($images as $i => $img): ?>
                    <div class="shrink-0 w-full h-full snap-center flex items-center justify-center p-2 md:p-10">
                        <img src="<?php echo $galleryUrl($img); ?>" alt="<?php echo $ge(strtr($lang['photos_alt'], ['{n}' => $i + 1])); ?>" class="max-w-full max-h-full object-contain" loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($galleryCount > 1): ?>
                <button type="button" id="lightbox-prev" class="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/90 text-neutral-900 items-center justify-center shadow-lg hover:bg-white" aria-label="<?php echo $ge($lang['overview_prev']); ?>"><i class="fas fa-chevron-left"></i></button>
                <button type="button" id="lightbox-next" class="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-white/90 text-neutral-900 items-center justify-center shadow-lg hover:bg-white" aria-label="<?php echo $ge($lang['overview_next']); ?>"><i class="fas fa-chevron-right"></i></button>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .no-scrollbar { scrollbar-width: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
    <script>
        (function () {
            const total = <?php echo $galleryCount; ?>;
            const counterText = (i) => (i + 1) + ' / ' + total;
            const indexOf = el => Math.round(el.scrollLeft / el.clientWidth);

            // Contador do carrossel do celular
            const carousel = document.getElementById('carousel');
            const carouselCounter = document.getElementById('carousel-counter');
            if (carousel && carouselCounter) {
                carousel.addEventListener('scroll', () => { carouselCounter.textContent = counterText(indexOf(carousel)); }, { passive: true });
            }

            // Tela cheia
            const box = document.getElementById('lightbox');
            const track = document.getElementById('lightbox-track');
            const counter = document.getElementById('lightbox-counter');
            const closeBtn = document.getElementById('lightbox-close');
            let opener = null;

            const goTo = (i, smooth) => {
                i = Math.max(0, Math.min(total - 1, i));
                track.scrollTo({ left: i * track.clientWidth, behavior: smooth ? 'smooth' : 'auto' });
                counter.textContent = counterText(i);
            };
            const open = (i, from) => {
                opener = from;
                box.classList.remove('hidden');
                box.classList.add('flex');
                document.body.style.overflow = 'hidden';
                goTo(i, false);
                closeBtn.focus();
            };
            const close = () => {
                box.classList.add('hidden');
                box.classList.remove('flex');
                document.body.style.overflow = '';
                if (opener) opener.focus();
            };

            document.querySelectorAll('.open-photo').forEach(btn => btn.addEventListener('click', () => {
                // No celular abre na foto que está aparecendo no carrossel
                const i = btn.closest('#carousel') ? indexOf(carousel) : Number(btn.dataset.index);
                open(i, btn);
            }));
            closeBtn.addEventListener('click', close);
            track.addEventListener('scroll', () => { counter.textContent = counterText(indexOf(track)); }, { passive: true });
            document.getElementById('lightbox-prev')?.addEventListener('click', () => goTo(indexOf(track) - 1, true));
            document.getElementById('lightbox-next')?.addEventListener('click', () => goTo(indexOf(track) + 1, true));
            document.addEventListener('keydown', ev => {
                if (box.classList.contains('hidden')) return;
                if (ev.key === 'Escape') close();
                if (ev.key === 'ArrowLeft') goTo(indexOf(track) - 1, true);
                if (ev.key === 'ArrowRight') goTo(indexOf(track) + 1, true);
            });
            // Clicar fora da foto fecha
            track.addEventListener('click', ev => { if (ev.target === ev.currentTarget || ev.target.tagName === 'DIV') close(); });
        })();
    </script>
<?php endif; ?>
