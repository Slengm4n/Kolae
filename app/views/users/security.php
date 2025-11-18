<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolae</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo APP_VERSION; ?>" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        .animate-up {
            animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .delay-100 {
            animation-delay: 100ms;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animação da Barra de Força */
        #password-strength-bar {
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.4s ease;
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <header class="bg-[#161B22]/80 backdrop-blur-md border-b border-gray-800 sticky top-0 z-30 py-4 transition-all">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <a href="<?php echo BASE_URL; ?>/" class="flex items-center gap-2 group">
                <img src="<?php echo BASE_URL; ?>/assets/img/kolae_branca.png" alt="Logo" class="h-8 w-auto group-hover:opacity-80 transition-opacity">
            </a>

            <nav class="hidden md:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>/dashboard" class="font-medium text-gray-400 hover:text-white transition-colors">Meu Painel</a>
            </nav>

            <div class="relative">
                <button id="user-menu-button" class="flex items-center gap-3 p-2 px-3 border border-gray-700 rounded-full cursor-pointer transition-all hover:bg-gray-700/50 hover:border-gray-600">
                    <?php if (!empty($_SESSION['user_avatar'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $_SESSION['user_id'] . '/' . $_SESSION['user_avatar']; ?>"
                            class="w-8 h-8 rounded-full object-cover border border-gray-600"
                            alt="Avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-[#161B22] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                    <div class="p-4 border-b border-gray-800">
                        <p class="font-semibold text-white truncate"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></p>
                    </div>
                    <ul class="py-2 text-sm">
                        <li><a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-800 transition-colors"><i class="fas fa-home w-4 text-center text-gray-400"></i> Voltar para Home</a></li>
                        <li class="border-t border-gray-800 my-2"></li>
                        <li><a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-3 px-5 py-3 text-red-400 hover:bg-red-500/10 transition-colors"><i class="fas fa-sign-out-alt w-4 text-center"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-10 max-w-6xl">

        <div class="flex flex-col md:flex-row items-start gap-8">

            <aside class="w-full md:w-64 flex-shrink-0 animate-up">
                <h1 class="text-2xl font-bold text-white mb-6 px-2">Configurações</h1>
                <nav class="bg-[#161B22] p-2 rounded-2xl border border-gray-800 shadow-lg">
                    <ul class="space-y-1">
                        <li>
                            <a href="<?php echo BASE_URL; ?>/dashboard/perfil" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition-all group">
                                <i class="fas fa-user-edit w-5 text-center group-hover:text-cyan-400 transition-colors"></i>
                                <span>Editar Perfil</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-xl border-l-4 border-cyan-400 transition-all">
                                <i class="fas fa-shield-alt w-5 text-center"></i>
                                <span>Segurança</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <section class="flex-1 w-full animate-up delay-100">
                <div class="bg-[#161B22] p-6 md:p-10 rounded-2xl border border-gray-800 shadow-xl relative overflow-hidden">

                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 to-blue-600"></div>

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-800 pb-4 mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Alterar Senha</h2>
                            <p class="text-sm text-gray-400">Mantenha sua conta segura atualizando sua senha periodicamente.</p>
                        </div>
                    </div>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
                        <div class="p-4 mb-6 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 flex items-center animate-up">
                            <i class="fas fa-check-circle mr-3 text-xl"></i>
                            <span>Senha alterada com sucesso!</span>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="p-4 mb-6 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 flex items-center animate-up">
                            <i class="fas fa-times-circle mr-3 text-xl"></i>
                            <span>
                                <?php
                                switch ($_GET['error']) {
                                    case 'current_mismatch':
                                        echo 'A senha atual informada está incorreta.';
                                        break;
                                    case 'new_mismatch':
                                        echo 'A nova senha e a confirmação não coincidem.';
                                        break;
                                    case 'weak_password':
                                        echo 'A nova senha é muito fraca.';
                                        break;
                                    case 'update_failed':
                                        echo 'Erro ao salvar. Tente novamente.';
                                        break;
                                    default:
                                        echo 'Ocorreu um erro desconhecido.';
                                }
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/dashboard/perfil/seguranca/atualizar" method="POST" class="max-w-lg space-y-6">

                        <div>
                            <label for="current_password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Senha Atual</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-lock"></i></div>
                                <input type="password" name="current_password" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>
                        </div>

                        <hr class="border-gray-800">

                        <div>
                            <label for="new_password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Nova Senha</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-key"></i></div>
                                <input type="password" name="new_password" id="new_password" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>

                            <div class="mt-3">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs text-gray-500">Força da senha</span>
                                    <span id="password-strength-text" class="text-xs font-bold text-gray-500">Muito Fraca</span>
                                </div>
                                <div class="w-full bg-gray-800 rounded-full h-1.5 overflow-hidden">
                                    <div id="password-strength-bar" class="h-full w-0 bg-red-500"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5 ml-1">Confirmar Nova Senha</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-check-double"></i></div>
                                <input type="password" name="confirm_password" required class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                            </div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-4">
                            <a href="<?php echo BASE_URL; ?>/dashboard/" class="text-gray-400 hover:text-white text-sm font-semibold px-4 py-2 transition-colors">Cancelar</a>
                            <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-cyan-500/20 transition-all transform hover:-translate-y-0.5">
                                Atualizar Senha
                            </button>
                        </div>

                    </form>

                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu Dropdown (Padrão)
            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('profile-dropdown');
            if (userBtn && userDropdown) {
                userBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userDropdown.classList.toggle('opacity-0');
                    userDropdown.classList.toggle('invisible');
                    userDropdown.classList.toggle('-translate-y-2');
                });
                window.addEventListener('click', (e) => {
                    if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.add('opacity-0', 'invisible', '-translate-y-2');
                    }
                });
            }

            // Medidor de Força de Senha
            const newPasswordInput = document.getElementById('new_password');
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');

            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', () => {
                    const password = newPasswordInput.value;
                    let score = 0;
                    if (password.length > 0) score++;
                    if (password.length >= 8) score++;
                    if (/[A-Z]/.test(password)) score++;
                    if (/[0-9]/.test(password)) score++;
                    if (/[^A-Za-z0-9]/.test(password)) score++;

                    let barColor = 'bg-red-500';
                    let text = 'Muito Fraca';
                    let width = '20%';

                    if (password.length === 0) {
                        width = '0%';
                        text = '';
                    } else if (score < 3) {
                        barColor = 'bg-red-500';
                        text = 'Fraca';
                        width = '30%';
                    } else if (score < 4) {
                        barColor = 'bg-yellow-500';
                        text = 'Razoável';
                        width = '60%';
                    } else if (score < 5) {
                        barColor = 'bg-blue-500';
                        text = 'Forte';
                        width = '80%';
                    } else {
                        barColor = 'bg-green-500';
                        text = 'Muito Forte';
                        width = '100%';
                    }

                    strengthBar.style.width = width;
                    strengthBar.className = `h-full rounded-full ${barColor}`;
                    strengthText.textContent = text;

                    // Cor do texto muda conforme a força
                    strengthText.className = `text-xs font-bold ${barColor.replace('bg-', 'text-')}`;
                });
            }
        });
    </script>

</body>

</html>