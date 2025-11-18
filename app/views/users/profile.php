<?php
// Recupera dados passados pelo Controller
$user = $data['user'] ?? [];
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userAvatar = $_SESSION['user_avatar'] ?? null; // Pega da sessão atualizada
$userId = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kolael</title>

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

                    <?php if (!empty($userAvatar)): ?>
                        <img src="<?php echo BASE_URL . '/uploads/avatars/' . $userId . '/' . $userAvatar; ?>"
                            class="w-8 h-8 rounded-full object-cover border border-gray-600"
                            alt="Avatar"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm" style="display:none;">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>

                <div id="profile-dropdown" class="absolute top-full right-0 mt-3 w-64 bg-[#161B22] border border-gray-700 rounded-xl shadow-2xl opacity-0 invisible transform -translate-y-2 transition-all duration-200 z-50">
                    <div class="p-4 border-b border-gray-800">
                        <p class="font-semibold text-white truncate"><?php echo htmlspecialchars($userName); ?></p>
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
                            <a href="#" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-xl border-l-4 border-cyan-400 transition-all">
                                <i class="fas fa-user-edit w-5 text-center"></i>
                                <span>Editar Perfil</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/dashboard/perfil/seguranca" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white rounded-xl transition-all group">
                                <i class="fas fa-shield-alt w-5 text-center group-hover:text-cyan-400 transition-colors"></i>
                                <span>Segurança</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <section class="flex-1 w-full animate-up delay-100">

                <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
                    <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/20 text-green-300 flex items-center animate-up">
                        <i class="fas fa-check-circle mr-3 text-xl"></i>
                        <span>Perfil atualizado com sucesso!</span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 flex items-center animate-up">
                        <i class="fas fa-times-circle mr-3 text-xl"></i>
                        <span>
                            <?php
                            if ($_GET['error'] === 'avatar_upload') echo 'Erro ao fazer upload da imagem. Tente um arquivo menor (JPG/PNG).';
                            elseif ($_GET['error'] === 'update_failed') echo 'Erro ao atualizar o perfil.';
                            else echo 'Ocorreu um erro desconhecido.';
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="bg-[#161B22] p-6 md:p-10 rounded-2xl border border-gray-800 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 to-blue-600"></div>

                    <?php if (!empty($user)) : ?>

                        <form action="<?php echo BASE_URL; ?>/dashboard/perfil/atualizar" method="POST" enctype="multipart/form-data">

                            <div class="flex flex-col lg:flex-row gap-10 mb-10">
                                <div class="flex flex-col items-center lg:items-start">
                                    <h2 class="text-lg font-bold text-white mb-4 lg:hidden">Foto de Perfil</h2>

                                    <div class="relative group w-40 h-40 rounded-full overflow-hidden border-4 border-gray-800 shadow-2xl cursor-pointer">
                                        <?php
                                        $placeholder = 'https://placehold.co/256x256/1f2937/9ca3af?text=Foto';
                                        // Usa a variável do banco ($user['avatar_path']) para mostrar no form
                                        $avatarFormPath = !empty($user['avatar_path'])
                                            ? BASE_URL . '/uploads/avatars/' . $user['id'] . '/' . $user['avatar_path']
                                            : $placeholder;
                                        ?>
                                        <img id="avatar-preview"
                                            src="<?php echo $avatarFormPath; ?>"
                                            alt="Avatar"
                                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                            onerror="this.onerror=null; this.src='<?php echo $placeholder; ?>';">

                                        <label for="avatar-upload" class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300 cursor-pointer">
                                            <i class="fas fa-camera text-2xl mb-1"></i>
                                            <span class="text-xs font-medium">Alterar</span>
                                        </label>
                                        <input type="file" name="avatar" id="avatar-upload" class="hidden" accept="image/png, image/jpeg, image/gif">
                                    </div>
                                    <p class="text-xs text-gray-500 mt-3 text-center lg:text-left w-40">Clique na foto para alterar.<br>Máx 5MB.</p>
                                </div>

                                <div class="flex-1 w-full space-y-6">
                                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-gray-800 pb-4 mb-4">
                                        <div>
                                            <h2 class="text-2xl font-bold text-white">Informações Pessoais</h2>
                                            <p class="text-sm text-gray-400">Atualize seus dados de identificação.</p>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="name" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Nome Completo</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-user"></i></div>
                                            <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required
                                                class="w-full bg-gray-900/50 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="email" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                                            <div class="relative opacity-60 cursor-not-allowed" title="Não editável">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-envelope"></i></div>
                                                <input id="email" type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled
                                                    class="w-full bg-gray-800 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-gray-400 cursor-not-allowed">
                                            </div>
                                        </div>

                                        <div>
                                            <label for="birthdate" class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1.5">Nascimento</label>
                                            <div class="relative opacity-60 cursor-not-allowed" title="Não editável">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500"><i class="fas fa-calendar"></i></div>
                                                <input id="birthdate" type="date" value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>" disabled
                                                    class="w-full bg-gray-800 border border-gray-700 rounded-xl pl-10 pr-4 py-3 text-sm text-gray-400 cursor-not-allowed">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-800 flex justify-end items-center gap-4">
                                <a href="<?php echo BASE_URL; ?>/dashboard" class="text-gray-400 hover:text-white text-sm font-semibold px-4 py-2 transition-colors">
                                    Cancelar
                                </a>
                                <button type="submit" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-cyan-500/20 transition-all transform hover:-translate-y-0.5">
                                    Salvar Alterações
                                </button>
                            </div>

                        </form>
                    <?php else : ?>
                        <div class="text-center py-12">
                            <i class="fas fa-user-slash text-4xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400">Não foi possível carregar os dados do usuário.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown Menu Logic
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

            // Image Preview Logic
            const avatarUpload = document.getElementById('avatar-upload');
            const avatarPreview = document.getElementById('avatar-preview');
            if (avatarUpload && avatarPreview) {
                avatarUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => avatarPreview.src = e.target.result;
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>

</body>

</html>