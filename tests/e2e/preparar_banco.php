<?php
// Recria os dados de teste no banco LOCAL (D56): apaga os do seed anterior, roda
// database/seeds/teste_status.sql e deixa o dono de teste pronto para login.
// Imprime os ids das quadras em JSON: {"A":..,"B":..,"C":..,"D":..,"E":..}
//   A = Arena (em uso)  B = Beach (disponível)  C = Clay (manutenção)
//   D = Duda (sem horário)  E = Excluida (desativada)
// Uso (da raiz do projeto): php tests/e2e/preparar_banco.php
// NUNCA na produção: recusa se o config.php não apontar para um banco local.
define('BASE_PATH', dirname(__DIR__, 2));
require BASE_PATH . '/config.php';

$host = strtolower((string)DB_HOST);
if (!in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
    fwrite(STDERR, "Recusado: DB_HOST é '" . DB_HOST . "'. Os dados de teste só podem ir para um banco local.\n");
    exit(1);
}
$db = getenv('KOLAE_DB') ?: DB_NAME;

$pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? DB_PORT : 3306) . ';dbname=' . $db . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => true, // permite rodar o arquivo .sql inteiro de uma vez
]);

// Roda um .sql com vários comandos, consumindo os resultados (o seed termina com um SELECT)
$run = function (string $file) use ($pdo) {
    $stmt = $pdo->query(file_get_contents($file));
    do {
        if ($stmt->columnCount() > 0) {
            $stmt->fetchAll();
        }
    } while ($stmt->nextRowset());
    $stmt->closeCursor();
};

$run(BASE_PATH . '/database/seeds/teste_status_limpar.sql');
$run(BASE_PATH . '/database/seeds/teste_status.sql');

// Senha teste123 e um CNPJ válido criptografado com as chaves do config.php local
$cnpj = openssl_encrypt('11222333000181', 'aes-256-cbc', ENCRYPTION_KEY, 0, hex2bin(ENCRYPTION_IV));
$pdo->prepare("UPDATE users SET password = ?, cnpj = ? WHERE email = 'dono-s3@kolae.test'")
    ->execute([password_hash('teste123', PASSWORD_DEFAULT), $cnpj]);

// Contas usadas pelos testes além do dono do seed: um admin e um segundo dono (sem as
// quadras de teste). Cria se não existirem e garante a senha teste123 e a conta ativa.
$hash = password_hash('teste123', PASSWORD_DEFAULT);
$upsert = $pdo->prepare("INSERT INTO users (name, email, password, role, status, birthdate)
                         VALUES (?, ?, ?, ?, 'active', '1990-01-01')
                         ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), status = 'active', deleted_at = NULL");
$upsert->execute(['Admin Teste', 'admin@teste.local', $hash, 'admin']);
$upsert->execute(['Outro Dono Teste', 'dono@teste.local', $hash, 'owner']);
// O segundo dono também tem CNPJ (outro, válido): assim os testes de "quadra de outro dono" não caem na regra do CNPJ
$cnpj2 = openssl_encrypt('11444777000161', 'aes-256-cbc', ENCRYPTION_KEY, 0, hex2bin(ENCRYPTION_IV));
$pdo->prepare("UPDATE users SET cnpj = ? WHERE email = 'dono@teste.local'")->execute([$cnpj2]);

// Limites de login e de "esqueci a senha" zerados, para um teste não atrapalhar o outro
$pdo->exec("DELETE FROM login_attempts");

$ids = $pdo->query("SELECT id FROM venues WHERE name LIKE '%Teste S3' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
if (count($ids) !== 5) {
    fwrite(STDERR, "Esperava 5 quadras de teste, achei " . count($ids) . ".\n");
    exit(1);
}
echo json_encode(array_combine(['A', 'B', 'C', 'D', 'E'], array_map('intval', $ids))), "\n";
