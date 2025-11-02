<?php
// Define o título da página
$pageTitle = "Crie uma Nova Senha - Kolae";

// Lógica para exibir mensagens de erro
$errorMessage = null;
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_mismatch':
            $errorMessage = 'As senhas não conferem. Tente novamente.';
            break;
        case 'invalid_token':
            $errorMessage = 'O link de redefinição é inválido ou expirou.';
            break;
        case 'generic':
            $errorMessage = 'Houve um erro ao atualizar sua senha. Tente novamente.';
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Kolae' ?></title>

    <!-- Favicon -->
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Classe simples para a animação */
        .animate-fadeInUp {
            animation: fadeInUp 0.5s ease-out both;
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

<body class="bg-[#0D1117] text-white min-h-screen flex items-center justify-center p-4 sm:p-8">

    <!-- === Mensagem de Erro (Se houver) === -->
    <?php if ($errorMessage): ?>
        <div class="bg-red-600 text-white p-4 text-center fixed top-5 left-1/2 -translate-x-1/2 rounded-md shadow-lg z-50 animate-fadeInUp" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    <!-- === Fim Mensagem de Erro === -->


    <!-- Layout de Formulário Centralizado -->
    <div class="w-full max-w-md animate-fadeInUp">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>/index" class="mb-6 inline-block w-full text-center">
            <img src="<?= BASE_URL ?>/assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto">
        </a>

        <!-- Card do Formulário -->
        <div class="bg-[#161B22] p-8 rounded-2xl border border-gray-800">

            <h2 class="text-3xl font-bold text-center mb-8">Crie sua Nova Senha</h2>

            <!-- Action aponta para a rota POST de reset -->
            <form action="<?= BASE_URL ?>/reset-password" method="POST" class="space-y-6">

                <!-- Campo Oculto com o Token -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300">Nova Senha</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300">Confirme a Nova Senha</label>
                    <div class="mt-1">
                        <!-- Correção do bug 'password_confirm' para 'password_confirmation' -->
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-colors">
                        Salvar Nova Senha
                    </button>
                </div>
            </form>

            <p class="mt-8 text-center text-sm text-gray-400">
                Lembrou da senha?
                <a href="<?= BASE_URL ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300">Voltar para o Login</a>
            </p>
        </div>
    </div>
    <!-- Fim do Layout Centralizado -->

</body>

</html>