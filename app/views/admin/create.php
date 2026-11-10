<!DOCTYPE html>
<html lang="pt-BR" class="transition-colors duration-500">

<head>
    <?php $pageTitle = 'Criar usuário · Kolae'; include BASE_PATH . '/app/views/partials/head.php'; ?>

    <style>
        body { font-family: 'Poppins', sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        .animate-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both; }
        .delay-100 { animation-delay: 100ms; }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-surface-base text-content-primary transition-colors duration-500">
    <?php $adminActive = 'usuarios'; include BASE_PATH . '/app/views/partials/admin_header.php'; ?>





        <main class="container mx-auto px-4 py-6 md:py-10 max-w-6xl">

            <div class="flex items-center gap-3 md:hidden mb-6 animate-up">
                <div>
                    <h1 class="text-xl font-bold text-content-primary leading-tight">Criar Usuário</h1>
                </div>
            </div>

            <h1 class="hidden md:block text-3xl font-bold mb-8 animate-up text-content-primary">Criar Novo Usuário</h1>

            <div class="bg-surface-elevated p-6 md:p-8 rounded-2xl border border-content-secondary/10 max-w-3xl mx-auto shadow-xl animate-up delay-100">

                <form action="<?php echo BASE_URL; ?>/admin/usuarios/salvar" method="POST" class="space-y-5 md:space-y-6">
                    <?php echo \App\Core\Csrf::field(); ?>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">

                        <div class="col-span-1 md:col-span-2">
                            <label for="name" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">Nome Completo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-content-secondary text-sm"></i>
                                </div>
                                <input id="name" name="name" type="text" placeholder="Ex: João Silva" required
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label for="email" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">E-mail</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-content-secondary text-sm"></i>
                                </div>
                                <input id="email" name="email" type="email" placeholder="email@exemplo.com" required
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-content-primary placeholder-gray-500 focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="birthdate" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">Data de Nascimento</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar text-content-secondary text-sm"></i>
                                </div>
                                <input id="birthdate" name="birthdate" type="date" required
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-4 py-2.5 md:py-3 text-sm text-content-primary focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label for="role" class="block text-xs font-bold text-content-secondary uppercase tracking-wider mb-1.5 ml-1">Cargo</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-shield-alt text-content-secondary text-sm"></i>
                                </div>
                                <select id="role" name="role" required
                                    class="w-full bg-input-base border border-content-secondary/20 rounded-xl pl-9 pr-8 py-2.5 md:py-3 text-sm text-content-primary focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 appearance-none transition-all cursor-pointer">
                                    <option value="player" class="bg-surface-elevated text-content-primary py-2" selected>Usuário Padrão</option>
                                    <option value="owner" class="bg-surface-elevated text-content-primary py-2">Responsável por Quadra</option>
                                    <option value="admin" class="bg-surface-elevated text-content-primary py-2">Administrador</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-content-secondary text-xs"></i>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="pt-6 flex gap-3 md:justify-end">
                        <a href="<?php echo BASE_URL; ?>/admin/usuarios"
                            class="flex-1 md:flex-none text-center bg-surface-base hover:bg-content-secondary/10 text-content-secondary font-semibold py-3 px-6 rounded-xl transition-colors border border-content-secondary/20 text-sm">
                            Cancelar
                        </a>
                        <button type="submit"
                            class="flex-1 md:flex-none text-center bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-xl transition-all hover:shadow-lg hover:shadow-brand-500/20 hover:-translate-y-0.5 text-sm">
                            Criar Usuário
                        </button>
                    </div>

                </form>
            </div>
        </main>

</body>
</html>