<?php
// Gera o link de confirmação de e-mail de uma conta do banco local, para o teste de clique (D58).
// Uso: php link_confirmacao.php <email> [vencido]
// Imprime só o link (nunca as chaves do config.php). Recusa banco que não seja local.
define('BASE_PATH', dirname(__DIR__, 2));
require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/config.php';

if (!in_array(DB_HOST, ['localhost', '127.0.0.1'], true)) {
    fwrite(STDERR, "Recusado: DB_HOST não é local.\n");
    exit(1);
}
define('BASE_URL', getenv('KOLAE_URL') ?: 'http://localhost/kolae');

$user = \App\Models\User::findByEmail($argv[1] ?? '');
if (!$user) {
    fwrite(STDERR, "Conta não encontrada.\n");
    exit(1);
}
$link = \App\Core\EmailVerification::link($user);
if (($argv[2] ?? '') === 'vencido') {
    // Mesma assinatura, com a validade no passado: refaz o link "à mão"
    $expires = time() - 60;
    $sig = hash_hmac('sha256', $user['id'] . '|' . strtolower($user['email']) . '|' . $expires, JWT_SECRET);
    $link = BASE_URL . '/verificar-email?' . http_build_query(['u' => $user['id'], 'e' => $expires, 's' => $sig]);
}
echo $link, "\n";
