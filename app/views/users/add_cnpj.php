<!DOCTYPE html>
<html lang="pt-br">

<head>
    <link rel="icon" href="https://i.postimg.cc/Ss21pvVJ/Favicon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kolae</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#0D1117] text-gray-200 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    <div class="bg-gray-800 p-6 sm:p-8 rounded-lg shadow-xl w-full max-w-sm sm:max-w-md text-center border border-gray-700">

        <h1 class="text-2xl font-bold text-gray-50 mb-4">
            Valide seu Cadastro
        </h1>

        <p class="text-gray-400 mb-6 text-sm sm:text-base leading-relaxed">
            Para começar a cadastrar suas quadras, precisamos que você informe um CNPJ válido. Este passo é importante para a segurança da plataforma.
        </p>

        <?php if (isset($error)): ?>
            <div class="bg-red-900/40 border border-red-700 text-red-300 px-4 py-3 rounded-lg mb-6 text-left text-sm" role="alert">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/dashboard/cnpj" method="POST">
            <div class="mb-5 text-left">
                <label for="cnpj" class="block text-gray-300 font-medium mb-2 text-sm">
                    CNPJ
                </label>
                <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" required
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-500
                              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-base">
            </div>

            <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition-colors duration-200 shadow-md">
                Validar e Salvar CNPJ
            </button>
        </form>

        <a href="<?php echo BASE_URL; ?>/dashboard"
            class="inline-block mt-6 text-blue-400 hover:text-blue-300 hover:underline text-sm font-medium transition-colors duration-200">
            Voltar ao Painel
        </a>
    </div>

    <script>
        document.getElementById('cnpj').addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>

</html>