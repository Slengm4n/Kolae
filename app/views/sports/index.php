<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <?php $pageTitle = 'Esportes · Kolae'; include BASE_PATH . '/app/views/partials/head.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        
        /* Animações */
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        
        /* Stagger effect para o grid */
        .stagger-grid>div:nth-child(1) { animation-delay: 50ms; }
        .stagger-grid>div:nth-child(2) { animation-delay: 100ms; }
        .stagger-grid>div:nth-child(3) { animation-delay: 150ms; }
        .stagger-grid>div:nth-child(4) { animation-delay: 200ms; }
        .stagger-grid>div:nth-child(5) { animation-delay: 250ms; }
        .stagger-grid>div:nth-child(6) { animation-delay: 300ms; }
        .stagger-grid>div:nth-child(n+7) { animation-delay: 350ms; }

        .card-sport { opacity: 0; animation: fadeInUp 0.5s ease-out forwards; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">
    <?php $adminActive = 'esportes'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>





        <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Gerir Esportes</h1>
                </div>
            </div>

            <div class="hidden md:flex justify-between items-center mb-8 animate-up">
                <div>
                    <h1 class="text-3xl font-bold mb-1 text-content-primary">Gerir Esportes</h1>
                    <p class="text-content-secondary text-sm">Adicione ou edite modalidades disponíveis.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/admin/esportes/criar" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-6 rounded-xl inline-flex justify-center items-center transition-all hover:shadow-lg hover:shadow-brand-500/20 hover:-translate-y-0.5">
                    <i class="fas fa-plus mr-2"></i> Novo Esporte
                </a>
            </div>

            <div class="md:hidden mb-6 animate-up delay-100">
                <a href="<?php echo BASE_URL; ?>/admin/esportes/criar" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 px-4 rounded-lg flex justify-center items-center shadow-md text-sm">
                    <i class="fas fa-plus mr-2"></i> Novo Esporte
                </a>
            </div>

            <div class="mb-6 animate-up delay-100">
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-content-secondary text-sm group-focus-within:text-brand-500 transition-colors"></i>
                    </div>
                    <input type="text" placeholder="Pesquisar esporte..." class="block w-full pl-9 pr-4 py-2.5 border border-content-secondary/20 rounded-xl bg-surface-elevated text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 shadow-sm transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6 stagger-grid">
                <?php if (!empty($sports)) : ?>
                    <?php foreach ($sports as $sport) : ?>
                        <div class="card-sport group relative bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 text-center flex flex-col items-center justify-center aspect-square transition-all duration-300 hover:border-brand-500/50 hover:shadow-lg hover:shadow-brand-500/10 hover:-translate-y-1">

                          <i class="fas <?php echo htmlspecialchars($sport['icon_path'] ?? 'fa-question-circle'); ?> text-4xl md:text-5xl text-content-secondary transition-colors duration-300 group-hover:text-brand-500 group-hover:scale-110 transform"></i>
                            <h3 class="mt-4 font-bold text-sm md:text-lg text-content-primary"><?php echo htmlspecialchars($sport['name']); ?></h3>

                            <div class="absolute top-2 right-2 md:top-3 md:right-3 md:opacity-0 md:group-hover:opacity-100 transition-all duration-300 flex space-x-1 md:space-x-2">
                                <a href="<?php echo BASE_URL; ?>/admin/esportes/editar/<?php echo $sport['id']; ?>" class="w-7 h-7 md:w-8 md:h-8 flex items-center justify-center bg-surface-base rounded-full text-content-secondary border border-content-secondary/10 hover:bg-brand-500 hover:text-white hover:border-brand-500 transition-colors shadow-sm" title="Editar">
                                    <i class="fas fa-pencil-alt text-xs"></i>
                                </a>

                                <form action="<?php echo BASE_URL; ?>/admin/esportes/excluir/<?php echo $sport['id']; ?>" method="POST" data-confirm="Tem a certeza?" data-confirm-danger data-confirm-ok="Excluir">
                                    <?php echo \App\Core\Csrf::field(); ?>
                                    <button type="submit" class="w-7 h-7 md:w-8 md:h-8 flex items-center justify-center bg-surface-base rounded-full text-content-secondary border border-content-secondary/10 hover:bg-red-500 hover:text-white hover:border-red-500 transition-colors shadow-sm" title="Excluir">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="col-span-full py-12 text-center text-content-secondary flex flex-col items-center animate-up delay-200">
                        <i class="fas fa-running text-4xl mb-3 opacity-50"></i>
                        <p>Nenhum esporte encontrado.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>


</body>
</html>