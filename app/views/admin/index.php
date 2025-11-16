<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Estilo para que o botão de excluir pareça um link */
        .link-button {
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>

<body class="bg-[#0D1117] text-gray-200">

    <?php
    if (isset($_SESSION['flash_message']) && $_SESSION['flash_message']['type'] === 'success_with_password') {
        $password = json_encode($_SESSION['flash_message']['password']);
        echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                showPasswordModal($password);
            });
        </script>";
        unset($_SESSION['flash_message']);
    }
    ?>

    <div>
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed top-0 left-0 z-40 w-64 h-screen bg-[#161B22] border-r border-gray-800 flex flex-col transition-transform -translate-x-full md:translate-x-0">
            <button id="sidebar-close-btn" class="md:hidden absolute top-4 right-4 text-gray-500 hover:text-white">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="p-6 text-center border-b border-gray-800">
                <div class="w-24 h-24 rounded-full bg-gray-700 mx-auto flex items-center justify-center mb-4">
                    <i class="fas fa-user-shield text-4xl text-cyan-400"></i>
                </div>
                <h2 class="text-xl font-bold"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h2>
                <p class="text-sm text-gray-400">Admin Kolae</p>
            </div>
            <nav class="flex-1 px-4 py-6 space-y-2">
                <a href="<?php echo BASE_URL; ?>/admin" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-home w-5 text-center"></i><span>Início</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold bg-cyan-500/10 text-cyan-400 rounded-lg">
                    <i class="fas fa-users w-5 text-center"></i><span>Usuários</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/esportes" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-running w-5 text-center"></i><span>Esportes</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/mapa" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fas fa-map-marker-alt w-5 text-center"></i><span>Mapa</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/quadras" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-gray-400 hover:bg-gray-700/50 hover:text-white rounded-lg transition-colors">
                    <i class="fa-solid fa-flag w-5 text-center"></i>
                    <span>Quadras</span>
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <a href="<?php echo BASE_URL; ?>/logout" class="flex items-center gap-4 px-4 py-3 text-sm font-semibold text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i><span>Sair</span>
                </a>
            </div>
        </aside>

        <!-- Overlay -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden"></div>

        <!-- Main Content -->
        <main class="md:ml-64 flex-1 p-6 sm:p-10">
            <button id="sidebar-toggle" class="md:hidden mb-6 text-gray-400 hover:text-white">
                <i class="fas fa-bars text-2xl"></i>
            </button>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <h1 class="text-3xl font-bold mb-4 sm:mb-0">Gerir Usuários</h1>
                <a href="<?php echo BASE_URL; ?>/admin/usuarios/criar" class="bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-2 px-4 rounded-lg inline-flex items-center transition-colors">
                    <i class="fas fa-plus mr-2"></i>Novo Usuário
                </a>
            </div>
            <div class="mb-6 space-y-4">
                <?php if (isset($_GET['status'])): ?>
                    <?php
                    $message = '';
                    $bgColor = 'bg-green-800'; // Cor padrão para sucesso
                    $textColor = 'text-green-200';

                    switch ($_GET['status']) {
                        case 'updated':
                            $message = 'Usuário atualizado com sucesso!';
                            break;
                        case 'deleted':
                            $message = 'Usuário desativado com sucesso!';
                            break;
                        case 'not_found': // Caso tente editar/excluir ID inválido
                            $message = 'Usuário não encontrado.';
                            $bgColor = 'bg-yellow-800'; // Cor de aviso
                            $textColor = 'text-yellow-200';
                            break;
                        case 'error': // Erro genérico do update/delete
                        case 'error_create': // Erro específico do create (se User::create falhar)
                            $message = 'Ocorreu um erro inesperado ao processar a sua solicitação.';
                            $bgColor = 'bg-red-800'; // Cor de erro
                            $textColor = 'text-red-200';
                            break;
                            // Adicione outros casos de 'status' que você possa usar
                    }
                    ?>
                    <?php if (!empty($message)): ?>
                        <div class="p-4 text-sm rounded-lg <?php echo $bgColor; ?> <?php echo $textColor; ?>" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php /* Bloco separado para ?error=..., se você usar */ ?>
                <?php if (isset($_GET['error'])): ?>
                    <div class="p-4 text-sm rounded-lg bg-red-800 text-red-200" role="alert">
                        <?php
                        // Exemplo: se você redirecionar com ?error=algum_erro_especifico
                        // if ($_GET['error'] === 'algum_erro_especifico') { echo 'Mensagem específica.'; }
                        // else { echo 'Ocorreu um erro desconhecido.'; }
                        echo 'Ocorreu um erro desconhecido.'; // Mensagem padrão
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-6">
                <label for="searchInput" class="sr-only">Pesquisar Usuários</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchInput"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-700 rounded-lg bg-gray-800 text-white placeholder-gray-500 focus:outline-none focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm"
                        placeholder="Pesquisar por usuário...">
                </div>
            </div>
            <div class="bg-[#161B22] rounded-2xl border border-gray-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table id="userTable" class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Nome</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Data Criada</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Cargo</th>
                                <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php if (isset($users) && count($users) > 0) : ?>
                                <?php foreach ($users as $user) : ?>
                                    <tr class="hover:bg-gray-800/50 transition-colors">
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-400"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white"><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-400"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-400 capitalize"><?php echo htmlspecialchars($user['role']); ?></td>
                                        <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex items-center text-xs leading-5 font-semibold rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?>">
                                                <span class="w-2 h-2 mr-2 rounded-full <?php echo $user['status'] === 'active' ? 'bg-green-400' : 'bg-red-400'; ?>"></span>
                                                <?php echo $user['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="<?php echo BASE_URL; ?>/admin/usuarios/editar/<?php echo $user['id']; ?>" class="text-cyan-400 hover:text-cyan-300 transition-colors" title="Editar">
                                                <i class="fas fa-pencil-alt"></i> <span class="hidden sm:inline">Editar</span>
                                            </a>
                                            <form action="<?php echo BASE_URL; ?>/admin/usuarios/excluir/<?php echo $user['id']; ?>" method="POST" class="inline ml-4" onsubmit="return confirm('Tem a certeza que deseja excluir este utilizador?');">
                                                <button type="submit" class="link-button text-red-400 hover:text-red-300 transition-colors" title="Excluir">
                                                    <i class="fas fa-trash-alt"></i> <span class="hidden sm:inline">Excluir</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-user-slash text-3xl mb-2"></i>
                                        <p>Nenhum utilizador encontrado.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Senha -->
    <div id="password-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 hidden">
        <div class="bg-[#161B22] border border-gray-800 rounded-2xl shadow-xl p-8 w-full max-w-md m-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-white">Utilizador Criado!</h3>
                <button id="close-modal-btn" class="text-2xl text-gray-500 hover:text-white">&times;</button>
            </div>
            <div class="bg-yellow-900/50 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold"><i class="fas fa-exclamation-triangle"></i> Atenção!</strong>
                <span class="block sm:inline">Anote ou copie esta senha. Após fechar este aviso, ela não poderá ser visualizada novamente.</span>
            </div>
            <label for="modal-password-display" class="block text-sm font-medium text-gray-400 mb-2">Senha Temporária Gerada:</label>
            <div class="relative mb-2">
                <input type="password" id="modal-password-display" readonly class="w-full bg-gray-900 border border-gray-700 rounded-lg pl-4 pr-20 py-3 text-lg text-cyan-300 font-mono tracking-widest">
                <div class="absolute top-1/2 right-3 -translate-y-1/2 flex items-center space-x-3">
                    <button id="toggle-visibility-btn" class="text-gray-400 hover:text-white" title="Mostrar/Ocultar senha">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button id="copy-password-btn" class="text-gray-400 hover:text-white" title="Copiar senha">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </div>
            <p class="text-xs text-center text-gray-500 mb-4">
                Este aviso fechará em <span id="modal-timer-countdown">30</span> segundos.
            </p>
            <div class="w-full bg-gray-700 rounded-full h-1.5">
                <div id="modal-timer-bar" class="bg-cyan-500 h-1.5 rounded-full" style="width: 100%;"></div>
            </div>
        </div>
    </div>

    <script>
        // Modal Script
        const modal = document.getElementById('password-modal');
        const passwordDisplay = document.getElementById('modal-password-display');
        const copyBtn = document.getElementById('copy-password-btn');
        const closeBtn = document.getElementById('close-modal-btn');
        const timerBar = document.getElementById('modal-timer-bar');
        const timerCountdown = document.getElementById('modal-timer-countdown');
        const toggleVisibilityBtn = document.getElementById('toggle-visibility-btn');

        let countdownTimer;
        let progressBarInterval;

        function showPasswordModal(password) {
            passwordDisplay.value = password;
            modal.classList.remove('hidden');
            passwordDisplay.type = 'password';
            toggleVisibilityBtn.innerHTML = '<i class="fas fa-eye"></i>';
            timerBar.style.width = '100%';
            timerCountdown.textContent = '30';
            let timeLeft = 30;
            countdownTimer = setTimeout(closeModal, 30000);
            progressBarInterval = setInterval(() => {
                timeLeft--;
                timerBar.style.width = `${(timeLeft / 30) * 100}%`;
                timerCountdown.textContent = timeLeft;
                if (timeLeft <= 0) clearInterval(progressBarInterval);
            }, 1000);
        }

        function closeModal() {
            modal.classList.add('hidden');
            clearTimeout(countdownTimer);
            clearInterval(progressBarInterval);
        }

        copyBtn.addEventListener('click', () => {
            const textArea = document.createElement('textarea');
            textArea.value = passwordDisplay.value;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                copyBtn.innerHTML = '<i class="fas fa-check text-green-400"></i>';
            } catch (err) {
                console.error('Falha ao copiar', err);
            }
            document.body.removeChild(textArea);
            setTimeout(() => {
                copyBtn.innerHTML = '<i class="far fa-copy"></i>';
            }, 2000);
        });

        toggleVisibilityBtn.addEventListener('click', () => {
            const isPassword = passwordDisplay.type === 'password';
            passwordDisplay.type = isPassword ? 'text' : 'password';
            toggleVisibilityBtn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });

        closeBtn.addEventListener('click', closeModal);

        // Sidebar Script
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const sidebarCloseBtn = document.getElementById('sidebar-close-btn');
        const overlay = document.getElementById('sidebar-overlay');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        if (toggleBtn && sidebarCloseBtn && overlay) {
            toggleBtn.addEventListener('click', openSidebar);
            sidebarCloseBtn.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);
        }


        // --- LÓGICA DA BARRA DE PESQUISA ---
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('userTable');

        if (table && searchInput) {
            const tableBody = table.querySelector('tbody');

            if (tableBody) {
                const tableRows = tableBody.querySelectorAll(':scope > tr');
                const noVenuesRow = tableBody.querySelector('tr > td[colspan="4"]'); // Pega a linha "Nenhuma quadra"

                console.log("Número de linhas (TRs) encontradas no tbody:", tableRows.length);

                searchInput.addEventListener('keyup', function() {
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    let visibleRowCount = 0;

                    tableRows.forEach((row) => {
                        const cells = row.cells;

                        // Pula a linha "Nenhuma quadra encontrada"
                        if (cells.length === 1 && cells[0].getAttribute('colspan')) {
                            return; // Não filtra esta linha
                        }

                        if (cells.length < 2) return; // Pula linhas mal formatadas

                        const nameCellText = (cells[0].textContent || cells[0].innerText).trim();
                        const addressCellText = (cells[1].textContent || cells[1].innerText).trim(); // Coluna 1 é o endereço
                        const rowText = (nameCellText + ' ' + addressCellText).toLowerCase();

                        if (rowText.includes(searchTerm)) {
                            row.style.display = '';
                            visibleRowCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    // Mostra/Esconde a linha "Nenhuma quadra encontrada"
                    if (noVenuesRow) {
                        // Mostra se NENHUMA linha de dados está visível E há mais de uma linha na tabela (a própria linha "nenhuma")
                        noVenuesRow.parentElement.style.display = (visibleRowCount === 0 && tableRows.length > 1) ? '' : 'none';
                    }

                });
            } else {
                console.error("Erro: Elemento <tbody> não encontrado dentro da tabela #venuesTable.");
            }
        } else {
            console.error("Erro: Tabela #venuesTable ou Input #searchInput não encontrado.");
        }
        // --- FIM PESQUISA ---
    </script>
</body>

</html>