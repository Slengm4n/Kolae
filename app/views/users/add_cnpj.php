<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" media="print" onload="this.media='all'" />
    <noscript>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    </noscript>

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css?v=" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            background-image: radial-gradient(circle at top center, #1f2937 0%, #0D1117 40%);
            min-height: 100vh;
        }

        /* Animação de Entrada */
        .animate-up {
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
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

<body class="text-gray-200 flex items-center justify-center p-4">

    <div class="w-full max-w-md animate-up">

        <div class="text-center mb-6">
            <div class="w-20 h-20 bg-[#161B22] border border-gray-700 rounded-full flex items-center justify-center mx-auto shadow-lg">
                <i class="fas fa-shield-alt text-4xl text-cyan-400"></i>
            </div>
        </div>

        <div class="bg-[#161B22] p-8 rounded-2xl shadow-2xl border border-gray-800 relative overflow-hidden">

            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-cyan-500 to-blue-600"></div>

            <h1 class="text-2xl font-bold text-white mb-2 text-center">
                Valide seu Cadastro
            </h1>

            <p class="text-gray-400 mb-8 text-sm text-center leading-relaxed">
                Para garantir a segurança da plataforma e cadastrar suas quadras, precisamos validar seu CNPJ.
            </p>

            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-400 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3" role="alert">
                    <i class="fas fa-exclamation-circle text-lg"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/dashboard/cnpj" method="POST" class="space-y-6">
                <div>
                    <label for="cnpj" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">
                        CNPJ da Empresa
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-500 group-focus-within:text-cyan-400 transition-colors"></i>
                        </div>
                        <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" required maxlength="18"
                            class="w-full pl-11 pr-4 py-3.5 bg-gray-900/50 border border-gray-700 rounded-xl text-white placeholder-gray-600 
                                   focus:outline-none focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 transition-all font-mono tracking-wide">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-cyan-500 hover:bg-cyan-400 text-black font-bold py-3.5 rounded-xl transition-all duration-200 shadow-lg hover:shadow-cyan-500/20 hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i class="fas fa-check"></i> Validar e Salvar
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="<?php echo BASE_URL; ?>/dashboard"
                    class="text-sm text-gray-500 hover:text-white transition-colors inline-flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-600 mt-6">
            &copy; <?php echo date('Y'); ?> Kolae. Ambiente Seguro.
        </p>
    </div>

    <script>
        // Máscara de CNPJ Robusta
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,3})(\d{0,3})(\d{0,4})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : x[1] + '.' + x[2] + (x[3] ? '.' : '') + x[3] + (x[4] ? '/' : '') + x[4] + (x[5] ? '-' + x[5] : '');
        });
    </script>
</body>

</html>