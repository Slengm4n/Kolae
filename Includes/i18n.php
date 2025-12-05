<?php

// 1. Lógica de detecção via URL (GET)
if (isset($_GET['lang'])) {
    $allowedLangs = ['en-us', 'hi-in', 'pt-br', 'zh-cn', 'es-es', 'ja-jp', 'it-it'];

    // Verifica se o idioma solicitado está na lista permitida para segurança
    if (in_array($_GET['lang'], $allowedLangs)) {
        $_SESSION['idioma'] = $_GET['lang'];
    } else {
        $_SESSION['idioma'] = 'pt-br';
    }
}

// 2. Fallback se não houver sessão
if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'pt-br';
}

// 3. O PULO DO GATO:
// Usamos 'return require' para passar o array de volta para quem chamou este arquivo.
// Usamos apenas 'require' (sem _once) para garantir que o array venha, e não o booleano '1'.
return require __DIR__ . '/../lang/' . $_SESSION['idioma'] . '.php';
