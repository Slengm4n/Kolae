<?php
// Define variáveis padrão se não vierem do controller
$userName = $data['userName'] ?? 'Admin';
$totalUsers = $data['totalUsers'] ?? '0';
$totalSports = $data['totalSports'] ?? '0';
$totalLocations = $data['totalLocations'] ?? '0';
$recentUsers = $data['recentUsers'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do admin · Kolae</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="<?php echo \App\Core\ViewHelper::asset('assets/css/style.css'); ?>" rel="stylesheet">

    <?php include 'app/views/partials/theme_script.php'; ?>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .animate-up { animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        .delay-200 { animation-delay: 200ms; }
        .delay-300 { animation-delay: 300ms; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">

    <div class="flex h-screen overflow-hidden">
        
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-surface-elevated border-r border-content-secondary/10 flex flex-col transition-transform duration-300 ease-in-out -translate-x-full md:translate-x-0 shadow-2xl">
            <button name="close-btn" id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-content-secondary hover:text-content-primary transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>

            <div class="p-8 text-center border-b border-content-secondary/10">
                <div class="w-20 h-20 rounded-full bg-surface-base border border-content-secondary/20 mx-auto flex items-center justify-center mb-4 shadow-inner">
                    <i class="fas fa-user-shield text-3xl text-brand-500"></i>
                </div>
                <h2 class="text-lg font-bold text-content-primary tracking-wide">
                    <?php echo htmlspecialchars($userName); ?>
                </h2>
                <p class="text-xs text-content-secondary uppercase tracking-wider mt-1">Admin Kolae</p>
            </div>

            <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 border-l-4 border-brand-500 rounded-r-lg transition-all shadow-sm">
                    <i class="fas fa-home w-5 text-center"></i>
                    <span>Início</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-users w-5 text-center"></i>
                    <span>Usuários</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-running w-5 text-center group-hover:text-purple-500 transition-colors"></i>
                    <span>Esportes</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fas fa-map-marker-alt w-5 text-center group-hover:text-green-500 transition-colors"></i>
                    <span>Mapa</span>
                </a>

                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary hover:translate-x-1 rounded-lg transition-all duration-200 group">
                    <i class="fa-solid fa-flag w-5 text-center group-hover:text-yellow-500 transition-colors"></i>
                    <span>Quadras</span>
                </a>
            </nav>

            <div class="p-4 border-t border-content-secondary/10 space-y-2">
                <button id="theme-toggle" class="w-full flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:bg-surface-base hover:text-content-primary rounded-lg transition-colors group cursor-pointer text-left">
                    <i class="fas fa-moon w-5 text-center group-hover:text-yellow-400 transition-colors"></i>
                    <span class="dark:hidden">Modo Escuro</span>
                    <span class="hidden dark:inline">Modo Claro</span>
                </button>

                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-content-secondary hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-colors group">
                    <i class="fas fa-sign-out-alt w-5 text-center group-hover:rotate-180 transition-transform duration-300"></i>
                    <span>Sair</span>
                </a>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

        <main class="md:ml-64 flex-1 p-6 sm:p-10 overflow-y-auto h-full">
            <button id="sidebar-toggle" class="md:hidden mb-6 text-content-secondary hover:text-content-primary">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <h1 class="text-3xl font-bold mb-8 animate-up text-content-primary">Dashboard</h1>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 rounded-2xl shadow-lg transform transition-all duration-300 hover:-translate-y-1 hover:shadow-green-500/20 animate-up delay-100 text-white">
                    <p class="text-sm text-green-100 font-medium">Total de Usuários</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $totalUsers; ?></p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-6 rounded-2xl shadow-lg transform transition-all duration-300 hover:-translate-y-1 hover:shadow-purple-500/20 animate-up delay-200 text-white">
                    <p class="text-sm text-purple-100 font-medium">Total de Esportes</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $totalSports; ?></p>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 p-6 rounded-2xl shadow-lg transform transition-all duration-300 hover:-translate-y-1 hover:shadow-yellow-500/20 animate-up delay-300 text-white">
                    <p class="text-sm text-yellow-100 font-medium">Total de Locais</p>
                    <p class="text-4xl font-bold mt-2"><?php echo $totalLocations; ?></p>
                </div>
            </div>

            <!-- Fotos das quadras (D48): otimizar as que já estavam no servidor -->
            <?php
            $photoReport = $_SESSION['photo_report'] ?? null;
            unset($_SESSION['photo_report']);
            $mb = fn($bytes) => number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
            ?>
            <section id="fotos" class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-sm mb-10 scroll-mt-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-content-primary flex items-center gap-2"><span class="text-brand-500"><i class="fas fa-images"></i></span>Fotos das quadras</h2>
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
            <div class="bg-surface-elevated p-6 rounded-2xl border border-content-secondary/10 shadow-sm animate-up delay-300">
                <h2 class="text-xl font-semibold mb-6 text-content-primary">Usuários Recentes</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-content-secondary/10">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-content-secondary uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-content-secondary uppercase tracking-wider">Função</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-content-secondary uppercase tracking-wider">Email</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-content-secondary uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-content-secondary/10">
                            <?php if (!empty($recentUsers)) : ?>
                                <?php foreach ($recentUsers as $user) : ?>
                                    <tr class="hover:bg-surface-base transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-content-primary"><?php echo htmlspecialchars($user['name'] ?? 'N/D'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-content-secondary"><?php echo htmlspecialchars($user['role'] ?? 'N/D'); ?></td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-content-secondary"><?php echo htmlspecialchars($user['email'] ?? 'N/D'); ?></td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = $user['status'] ?? 'inactive';
                                            $statusClass = $status === 'active' ? 'bg-green-500/10 text-green-600 dark:text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20';
                                            ?>
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-content-secondary">Nenhum usuário recente encontrado.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar Logic
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const closeBtn = document.getElementById('sidebar-close-btn');
            const overlay = document.getElementById('sidebar-overlay');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // Theme Toggle Logic
            const themeToggleBtn = document.getElementById('theme-toggle');
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (document.documentElement.classList.contains('dark')) {
                        document.documentElement.classList.remove('dark');
                        localStorage.theme = 'light';
                    } else {
                        document.documentElement.classList.add('dark');
                        localStorage.theme = 'dark';
                    }
                });
            }
        });
    </script>

</body>
</html>