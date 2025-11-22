<?php
require_once __DIR__ . '/../../../Includes/i18n.php';
$token = $data['token'] ?? '';

// Lógica para exibir mensagens de erro
$errorMessage = null;
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'password_mismatch':
            $errorMessage = $lang['reset_password_mismatch'];
            break;
        case 'invalid_token':
            $errorMessage = $lang['reset_invalid'];
            break;
        case 'generic':
            $errorMessage = $lang['reset_unknown_error'];
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['idioma']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

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

            <h2 class="text-3xl font-bold text-center mb-8"><?php echo $lang['reset_title']; ?> </h2>

            <!-- Action aponta para a rota POST de reset -->
            <form id="reset-form" action="<?= BASE_URL ?>/reset-password" method="POST" class="space-y-6">

                <!-- Campo Oculto com o Token -->
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300"><?php echo $lang['reset_passoword_one']; ?> </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-300"><?php echo $lang['reset_passoword_two']; ?></label>
                    <div class="mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                    </div>
                </div>

                <div>
                    <button id='reset-button' type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-colors">
                        <?php echo $lang['reset_password_btn']; ?> 
                    </button>
                </div>
            </form>

            <p class="mt-8 text-center text-sm text-gray-400">
                <?php echo $lang['remember_passoword_text']; ?> 
                <a href="<?= BASE_URL ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300"><?php echo $lang['remember_password_btn']; ?> </a>
            </p>
        </div>
    </div>
    <script>
        const resetForm = document.getElementById('reset-form');
        const resetButton = document.getElementById('reset-button');

        if (resetForm && resetButton) {
            resetForm.addEventListener('submit', function() {
                resetButton.disabled = true;
                resetButton.innerHTML = `
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    Aguarde...
                `;
            });
        }
    </script>

</body>

</html>