<?php
/**
 * Fotos das quadras no painel do admin (D67, parte C): antes era um card do Início. Só em português (D19).
 */
$pageTitle = 'Fotos das quadras · Admin · Kolae';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <?php include BASE_PATH . '/app/views/partials/head.php'; ?>
</head>

<body class="bg-surface-base text-content-primary font-sans antialiased">
    <?php $adminActive = 'fotos'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>

    <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Fotos das quadras</h1>
        </div>

            <?php
            $photoReport = $_SESSION['photo_report'] ?? null;
            unset($_SESSION['photo_report']);
            $mb = fn($bytes) => number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
            ?>
            <section id="fotos" class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-sm mb-10 scroll-mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-sm text-content-secondary mt-1">
                            <?php echo (int)$photoStats['count']; ?> fotos · <?php echo $mb($photoStats['bytes']); ?> no total
                            <?php if ($photoStats['heavy'] > 0): ?>· <span class="text-amber-700 dark:text-amber-300 font-semibold"><?php echo (int)$photoStats['heavy']; ?> pesada(s), acima de 600 KB</span><?php endif; ?>
                            <?php if ($photoStats['no_thumb'] > 0): ?>· <span class="text-amber-700 dark:text-amber-300 font-semibold"><?php echo (int)$photoStats['no_thumb']; ?> sem miniatura</span><?php endif; ?>
                        </p>
                        <p class="text-xs text-content-secondary mt-1">Fotos novas já são otimizadas no envio. Este botão reduz as antigas para no máximo 1600 px em JPEG, o que deixa o app mais rápido no 4G.</p>
                    </div>
                    <form method="POST" action="<?php echo BASE_URL; ?>/admin/fotos/otimizar" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button span:last-child').textContent = 'Otimizando...';">
                        <?php echo \App\Core\Csrf::field(); ?>
                        <button type="submit" class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 disabled:opacity-60 text-white font-semibold px-5 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                            <span><i class="fas fa-wand-magic-sparkles"></i></span><span>Otimizar fotos</span>
                        </button>
                    </form>
                </div>
                <?php if ($photoReport): ?>
                    <p class="mt-4 p-3 rounded-xl text-sm <?php echo $photoReport['failed'] ? 'bg-amber-500/10 text-amber-800 dark:text-amber-300' : 'bg-green-500/10 text-green-700 dark:text-green-400'; ?>" role="status">
                        <?php echo (int)$photoReport['optimized']; ?> otimizada(s), <?php echo (int)$photoReport['skipped']; ?> já estavam boas<?php echo $photoReport['failed'] ? ', ' . (int)$photoReport['failed'] . ' não deu para processar' : ''; ?>.
                        <?php if ($photoReport['saved_bytes'] > 0): ?>Economia de <?php echo $mb($photoReport['saved_bytes']); ?>.<?php endif; ?>
                        <?php if (!empty($photoReport['thumbs'])): ?><?php echo (int)$photoReport['thumbs']; ?> miniatura(s) criada(s) para os cards.<?php endif; ?>
                    </p>
                <?php endif; ?>
            </section>
    </main>
</body>

</html>
