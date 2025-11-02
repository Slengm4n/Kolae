<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            animation: pageFadeIn 0.4s ease-out both;
        }

        @keyframes pageFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

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

<body class="bg-[#0D1117] text-white">

    <div class="flex min-h-screen">
        <div class="hidden lg:flex w-1/2 bg-cover bg-center relative items-center justify-center" style="background-image: url('https://images.pexels.com/photos/3768916/pexels-photo-3768916.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');">
            <div class="absolute inset-0 bg-black/60"></div>
            <div class="relative z-10 text-center px-12 animate-fadeInUp">
                <img src="./assets/img/kolae_branca.png" alt="Logo Kolae" class="h-16 mx-auto mb-6">
                <h1 class="text-4xl font-bold leading-tight">Vamos te colocar de volta no jogo.</h1>
                <p class="mt-4 text-lg text-gray-300">Recupere seu acesso e não perca nenhuma oportunidade de se conectar.</p>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-md bg-[#161B22] p-8 rounded-2xl border border-gray-800 lg:border-none lg:bg-transparent lg:p-0 animate-fadeInUp" style="animation-delay: 200ms;">
                <a href="index.html" class="lg:hidden mb-6 inline-block w-full text-center">
                    <img src="./assets/img/kolae_branca.png" alt="Logo Kolae" class="h-10 mx-auto">
                </a>

                <h2 class="text-3xl font-bold text-center mb-2">Esqueceu sua senha?</h2>
                <p class="text-gray-400 text-center mb-8">Sem problemas! Digite seu e-mail e enviaremos um link para você criar uma nova.</p>
                <?php
                if (isset($_GET['status']) && $_GET['status'] === 'sent') {
                    echo '<div class="bg-green-500/20 border border-green-500 text-green-300 px-4 py-3 rounded-lg text-center mb-6 text-sm">'
                        . 'Se um e-mail com este endereço existir, um link foi enviado.'
                        . '</div>';
                }
                if (isset($_GET['error']) && $_GET['error'] === 'invalid_email') {
                    echo '<div class="bg-red-500/20 border border-red-500 text-red-300 px-4 py-3 rounded-lg text-center mb-6 text-sm">'
                        . 'Por favor, insira um e-mail válido.'
                        . '</div>';
                }
                ?>
                <form action="<?= BASE_URL ?>/forgot-password" method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" autocomplete="email" required class="w-full bg-gray-800 border border-gray-700 rounded-md px-4 py-3 text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-bold text-black bg-cyan-400 hover:bg-cyan-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-cyan-400 transition-colors">
                            Enviar Link de Recuperação
                        </button>
                    </div>
                </form>

                <p class="mt-8 text-center text-sm text-gray-400">
                    Lembrou da senha?
                    <a href="<?= BASE_URL ?>/login" class="font-medium text-cyan-400 hover:text-cyan-300">Voltar para o Login</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>