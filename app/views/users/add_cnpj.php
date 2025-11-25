<?php
// Mantive sua lógica de erro
?>
<!DOCTYPE html>
<html lang="pt-br" class="transition-colors duration-500">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae - Validar CNPJ</title>

    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">

    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
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

<body class="bg-surface-base text-content-primary font-poppins transition-colors duration-500 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md animate-up">

        <div class="text-center mb-8">
            <div class="w-24 h-24 bg-cyan-500/10 dark:bg-cyan-500/5 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-cyan-500/10 backdrop-blur-sm border border-cyan-500/20">
                <i class="fas fa-shield-alt text-5xl text-cyan-600 dark:text-cyan-400"></i>
            </div>

            <h1 class="text-3xl font-bold text-content-primary mb-3 tracking-tight">
                Valide seu Cadastro
            </h1>

            <p class="text-content-secondary text-lg leading-relaxed max-w-xs mx-auto">
                Para garantir a segurança da plataforma e cadastrar suas quadras, precisamos validar seu CNPJ.
            </p>
        </div>

        <div class="relative z-10">

            <?php if (isset($error)): ?>
                <div class="bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 px-5 py-4 rounded-2xl mb-8 text-sm flex items-center gap-3 font-medium" role="alert">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form action="<?php echo BASE_URL; ?>/dashboard/cnpj" method="POST" class="space-y-6">
                <div class="group">
                    <label for="cnpj" class="block text-sm font-bold text-content-primary mb-2 ml-1">
                        CNPJ da Empresa
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-building text-content-secondary/70 transition-colors group-focus-within:text-cyan-500"></i>
                        </div>

                        <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" required maxlength="18"
                            class="w-full bg-white dark:bg-surface-secondary/50 border border-gray-200 dark:border-gray-700/50 rounded-xl pl-12 pr-4 py-4 text-content-primary placeholder-content-secondary/50 font-mono tracking-wide focus:outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 transition-all duration-300 shadow-sm">
                    </div>
                </div>

                <button type="submit"
                    class="w-full flex justify-center items-center py-4 px-6 rounded-xl shadow-lg shadow-cyan-500/20 text-base font-bold text-white bg-gradient-to-r from-cyan-500 to-cyan-400 hover:from-cyan-400 hover:to-cyan-300 hover:shadow-cyan-500/40 hover:-translate-y-0.5 focus:outline-none focus:ring-4 focus:ring-cyan-500/30 transition-all duration-300 active:scale-[0.98]">
                    <i class="fas fa-check mr-2"></i> Validar e Salvar
                </button>
            </form>

            <div class="mt-10 text-center">
                <a href="<?php echo BASE_URL; ?>/dashboard"
                    class="group inline-flex items-center text-content-secondary hover:text-content-primary transition-colors font-medium">
                    <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform text-cyan-500"></i>
                    Voltar ao Painel
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-content-secondary/60 mt-12">
            &copy; <?php echo date('Y'); ?> Kolae. Ambiente Seguro.
        </p>
    </div>

    <script type="module" src="<?php echo BASE_URL; ?>/assets/js/bundle.js"></script>

</body>

</html>