 <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
<div class="flex flex-col items-center justify-center min-h-screen bg-[#0D1117] text-gray-200 p-8">
    <div class="text-center">
        <h1 class="text-9xl font-extrabold text-cyan-500 mb-4 tracking-widest">403</h1>
        
        <p class="text-4xl font-semibold mb-6">Oops! <?php echo htmlspecialchars($message ?? 'Acesso Negado.'); ?></p>
        
        <p class="text-gray-400 mb-8 max-w-lg mx-auto">
            Você não tem permissão para acessar essa área.
        </p>
    </div>
</div>