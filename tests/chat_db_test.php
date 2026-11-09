<?php
// Testa o acesso ao banco do chat (Conversation e Message, D66) nas quadras e contas do seed
// database/seeds/teste_status.sql: abrir conversa, lados, mensagens, não lidas, "lida", listas do
// app e do painel, conversa vazia, bloqueio, quadra desativada, limite de envio e conta inativa.
// Deixa tudo como encontrou. Rodar da raiz do projeto (banco local ou o do GitHub Actions):
//   php tests/chat_db_test.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

use App\Core\ChatRules;
use App\Core\Database;
use App\Models\Conversation;
use App\Models\Message;

$ok = 0;
$falhas = 0;
function check(string $nome, bool $cond, string $detalhe = ''): void
{
    global $ok, $falhas;
    if ($cond) {
        $ok++;
        echo "  ok    $nome\n";
    } else {
        $falhas++;
        echo "  FALHA $nome  $detalhe\n";
    }
}

$pdo = Database::getConnection();
$quadras = [];
foreach ($pdo->query("SELECT v.name, v.id, v.user_id FROM venues v JOIN users u ON u.id = v.user_id
                      WHERE u.email = 'dono-s3@kolae.test' AND v.name IN ('Arena Teste S3', 'Beach Teste S3', 'Excluida Teste S3')")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $quadras[$row['name']] = $row;
}
$pessoas = [];
foreach ($pdo->query("SELECT email, id, status FROM users WHERE email IN ('dono@teste.local', 'admin@teste.local')")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $pessoas[$row['email']] = $row;
}
if (count($quadras) !== 3 || count($pessoas) !== 2) {
    exit("Quadras ou contas de teste não encontradas: rode database/seeds/teste_status.sql antes.\n");
}
$arena = (int)$quadras['Arena Teste S3']['id'];
$beach = (int)$quadras['Beach Teste S3']['id'];
$excluida = (int)$quadras['Excluida Teste S3']['id'];
$dono = (int)$quadras['Arena Teste S3']['user_id'];
$pessoa = (int)$pessoas['dono@teste.local']['id']; // outro dono, aqui no papel de quem pergunta
$admin = (int)$pessoas['admin@teste.local']['id'];
$statusOriginal = $pessoas['dono@teste.local']['status'];
$limpar = fn() => $pdo->exec("DELETE FROM conversations WHERE venue_id IN ($arena, $beach, $excluida)"); // apaga as mensagens junto (cascata)
$msgIds = fn(array $list) => array_map(fn($m) => (int)$m['id'], $list);

try {
    $limpar();

    echo "abrir conversa\n";
    [$c, $criou] = Conversation::findOrCreate($arena, $pessoa);
    check('cria na primeira vez', $criou && (int)$c['venue_id'] === $arena && (int)$c['user_id'] === $pessoa);
    [$c2, $criou2] = Conversation::findOrCreate($arena, $pessoa);
    check('na segunda, devolve a mesma', !$criou2 && (int)$c2['id'] === (int)$c['id']);
    check('vem com o dono e a situação da quadra', (int)$c['venue_owner_id'] === $dono && $c['venue_name'] === 'Arena Teste S3' && Conversation::venueAvailable($c));

    echo "lados\n";
    check('quem pergunta = user', Conversation::side($c, $pessoa) === 'user');
    check('dono da quadra = owner', Conversation::side($c, $dono) === 'owner');
    check('admin não faz parte', Conversation::side($c, $admin) === null);

    echo "conversa vazia\n";
    check('fora da lista do app', Conversation::forUser($pessoa) === []);
    check('fora do painel', Conversation::forOwner($dono) === []);
    check('não conta como não lida', Conversation::unreadForOwner($dono) === 0);
    check('ler: sem mensagens', Message::list((int)$c['id'], null, null, 30) === []);
    check('marcar lida numa vazia não muda nada', Conversation::markRead($c, 'user', 10) === null);

    echo "mensagens e não lidas\n";
    $m1 = Message::create($c, $pessoa, 'user', 'Tem horário sábado?');
    $c = Conversation::findById((int)$c['id']);
    check('grava e devolve a mensagem', $m1['body'] === 'Tem horário sábado?' && (int)$m1['sender_id'] === $pessoa);
    check('quem enviou já leu', (int)$c['user_last_read_id'] === (int)$m1['id'] && $c['last_message_at'] !== null);
    check('dono: 1 não lida (no total e na conversa)', Conversation::unreadForOwner($dono) === 1 && Conversation::unreadCount($c, $dono, 'owner') === 1);
    check('pessoa: 0 não lidas', Conversation::unreadCount($c, $pessoa, 'user') === 0);
    $m2 = Message::create($c, $dono, 'owner', 'Tem sim, das 8h às 12h.');
    $m3 = Message::create($c, $dono, 'owner', 'Quantas pessoas?');
    $c = Conversation::findById((int)$c['id']);
    check('dono respondeu: zerou as dele', Conversation::unreadForOwner($dono) === 0);
    check('pessoa: 2 não lidas', Conversation::unreadCount($c, $pessoa, 'user') === 2);

    echo "listas\n";
    $app = Conversation::forUser($pessoa);
    check('app: a conversa aparece', count($app) === 1 && (int)$app[0]['id'] === (int)$c['id']);
    check('app: última mensagem e não lidas', (int)$app[0]['last_message']['id'] === (int)$m3['id'] && $app[0]['unread_count'] === 2);
    check('app: traz a capa (null sem foto)', array_key_exists('venue_cover', $app[0]));
    check('app: o dono não vê pelo app (só quem pergunta)', Conversation::forUser($dono) === []);
    $painel = Conversation::forOwner($dono);
    check('painel: a conversa, com o nome de quem pergunta', count($painel) === 1 && $painel[0]['user_name'] !== '' && array_key_exists('user_avatar_path', $painel[0]));
    check('painel: nunca o e-mail', !array_key_exists('email', $painel[0]) && !array_key_exists('user_email', $painel[0]));
    check('painel: filtro por quadra', count(Conversation::forOwner($dono, $arena)) === 1 && Conversation::forOwner($dono, $beach) === []);
    check('painel de outro dono: vazio', Conversation::forOwner($pessoa) === []);

    echo "ler mensagens\n";
    $ids = [(int)$m1['id'], (int)$m2['id'], (int)$m3['id']];
    check('sem cursor: em ordem crescente', $msgIds(Message::list((int)$c['id'], null, null, 30)) === $ids);
    check('sem cursor com per_page 2: as 2 mais recentes, crescente', $msgIds(Message::list((int)$c['id'], null, null, 2)) === [$ids[1], $ids[2]]);
    check('after_id: só as mais novas', $msgIds(Message::list((int)$c['id'], $ids[0], null, 30)) === [$ids[1], $ids[2]]);
    check('after_id da última: vazio (polling sem novidade)', Message::list((int)$c['id'], $ids[2], null, 30) === []);
    check('before_id: as mais antigas', $msgIds(Message::list((int)$c['id'], null, $ids[2], 30)) === [$ids[0], $ids[1]]);
    check('before_id com per_page 1: a imediatamente anterior', $msgIds(Message::list((int)$c['id'], null, $ids[2], 1)) === [$ids[1]]);

    echo "marcar como lida\n";
    check('marca até a do meio', Conversation::markRead($c, 'user', $ids[1]) === $ids[1]);
    $c = Conversation::findById((int)$c['id']);
    check('sobra 1 não lida', Conversation::unreadCount($c, $pessoa, 'user') === 1);
    check('id menor não volta', Conversation::markRead($c, 'user', $ids[0]) === $ids[1] && (int)Conversation::findById((int)$c['id'])['user_last_read_id'] === $ids[1]);
    check('id enorme vale como a última', Conversation::markRead($c, 'user', 99999999) === $ids[2]);
    $c = Conversation::findById((int)$c['id']);
    check('zerou', Conversation::unreadCount($c, $pessoa, 'user') === 0 && Conversation::forUser($pessoa)[0]['unread_count'] === 0);

    echo "bloqueio e quadra desativada\n";
    Conversation::setBlocked((int)$c['id'], true);
    $bloq = Conversation::findById((int)$c['id']);
    check('bloqueia', $bloq['blocked_at'] !== null);
    Conversation::setBlocked((int)$c['id'], true);
    check('bloquear de novo mantém a data', Conversation::findById((int)$c['id'])['blocked_at'] === $bloq['blocked_at']);
    Conversation::setBlocked((int)$c['id'], false);
    check('desbloqueia', Conversation::findById((int)$c['id'])['blocked_at'] === null);
    [$cx] = Conversation::findOrCreate($excluida, $pessoa);
    check('quadra desativada: só leitura', !Conversation::venueAvailable($cx));

    echo "limite de envio\n";
    [$cb] = Conversation::findOrCreate($beach, $pessoa);
    $antes = Message::recentBy($pessoa)['count']; // as do bloco de cima também contam
    check('abaixo do limite: pode enviar', Message::waitSeconds($pessoa) === null, (string)$antes);
    for ($i = $antes; $i < ChatRules::RATE_LIMIT; $i++) {
        Message::create($cb, $pessoa, 'user', "Mensagem $i");
    }
    check('conta as do último minuto em todas as conversas', Message::recentBy($pessoa)['count'] === ChatRules::RATE_LIMIT);
    $espera = Message::waitSeconds($pessoa);
    check('no limite: espera entre 1 e 60 segundos', $espera !== null && $espera >= 1 && $espera <= ChatRules::RATE_WINDOW, var_export($espera, true));
    check('o dono não é limitado pela pessoa', Message::waitSeconds($dono) === null);

    echo "conta inativa (excluída)\n";
    $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?")->execute([$pessoa]);
    $c = Conversation::findById((int)$c['id']);
    check('dono não vê mais a conversa', Conversation::side($c, $dono) === null && Conversation::forOwner($dono) === []);
    check('não conta nas não lidas do dono', Conversation::unreadForOwner($dono) === 0);
    check('as mensagens dela somem da conversa', $msgIds(Message::list((int)$c['id'], null, null, 30)) === [$ids[1], $ids[2]]);
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$statusOriginal, $pessoa]);
    check('reativada: volta a aparecer', count(Conversation::forOwner($dono)) === 2);

    echo "apagar\n";
    $pdo->prepare('DELETE FROM conversations WHERE id = ?')->execute([(int)$c['id']]);
    $sobrou = $pdo->prepare('SELECT COUNT(*) FROM messages WHERE conversation_id = ?');
    $sobrou->execute([(int)$c['id']]);
    check('apagar a conversa apaga as mensagens (cascata)', (int)$sobrou->fetchColumn() === 0);
} finally {
    // Devolve tudo como estava
    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$statusOriginal, $pessoa]);
    $limpar();
}

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
