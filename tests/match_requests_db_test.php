<?php
// Testa o fluxo dos pedidos de horário no banco (MatchRequest e Notification, D64), nas quadras e
// contas do seed database/seeds/teste_status.sql: pedir, avisar o dono, aprovar (vira partida e
// recusa os outros no mesmo horário), conflito na aprovação, recusar com motivo, cancelar
// (pendente, aprovado com e sem antecedência), expirar e as notificações (lista, não lidas, lida).
// Deixa tudo como encontrou. Rodar da raiz do projeto (banco local ou o do GitHub Actions):
//   php tests/match_requests_db_test.php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config.php';

use App\Core\Database;
use App\Core\VenueSchedule;
use App\Models\Game;
use App\Models\MatchRequest;
use App\Models\Notification;

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
                      WHERE u.email = 'dono-s3@kolae.test' AND v.name IN ('Arena Teste S3', 'Beach Teste S3')")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $quadras[$row['name']] = $row;
}
$pessoas = [];
foreach ($pdo->query("SELECT email, id, name FROM users WHERE email IN ('dono@teste.local', 'admin@teste.local')")->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $pessoas[$row['email']] = $row;
}
if (count($quadras) !== 2 || count($pessoas) !== 2) {
    exit("Quadras ou contas de teste não encontradas: rode database/seeds/teste_status.sql antes.\n");
}
$arena = ['id' => (int)$quadras['Arena Teste S3']['id'], 'user_id' => (int)$quadras['Arena Teste S3']['user_id']];
$beach = ['id' => (int)$quadras['Beach Teste S3']['id'], 'user_id' => (int)$quadras['Beach Teste S3']['user_id']];
$dono = $arena['user_id'];
$ana = $pessoas['dono@teste.local'];   // no papel de jogadora
$beto = $pessoas['admin@teste.local']; // no papel de outro jogador
$ana['id'] = (int)$ana['id'];
$beto['id'] = (int)$beto['id'];

$ultimaNotif = (int)$pdo->query('SELECT IFNULL(MAX(id), 0) FROM notifications')->fetchColumn();
$partidas = [];
$dia = VenueSchedule::now()->modify('+2 days')->format('Y-m-d'); // longe das partidas do seed
$v = fn(string $start, string $end, ?string $msg = null) => ['start_time' => "$dia $start:00", 'end_time' => "$dia $end:00", 'sport_id' => null, 'message' => $msg];
$notifs = function (int $userId, ?string $type = null) use ($pdo, &$ultimaNotif) {
    $sql = 'SELECT type, data FROM notifications WHERE id > ? AND user_id = ?' . ($type ? ' AND type = ?' : '') . ' ORDER BY id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$ultimaNotif, $userId], $type ? [$type] : []));
    return array_map(fn($n) => ['type' => $n['type'], 'data' => json_decode($n['data'], true)], $stmt->fetchAll(PDO::FETCH_ASSOC));
};
$limpar = function () use ($pdo, $arena, $beach) {
    $pdo->exec("DELETE FROM match_requests WHERE venue_id IN ({$arena['id']}, {$beach['id']})");
};

try {
    $limpar();

    echo "pedir\n";
    $r1 = MatchRequest::create($arena, $ana, $v('19:00', '20:00', 'Somos 10'));
    check('grava pendente, com a quadra e quem pediu', $r1['status'] === 'pending' && (int)$r1['venue_id'] === $arena['id'] && (int)$r1['user_id'] === $ana['id'] && $r1['message'] === 'Somos 10');
    $aviso = $notifs($dono, 'request_new');
    check('o dono recebe "novo pedido"', count($aviso) === 1 && $aviso[0]['data']['request_id'] === (int)$r1['id']);
    check('o aviso guarda só o primeiro nome', $aviso[0]['data']['user_name'] === explode(' ', trim($ana['name']))[0], var_export($aviso[0]['data'], true));
    check('contagem de pendentes', MatchRequest::pendingCounts($ana['id'], $arena['id']) === ['user' => 1, 'venue' => 1] && MatchRequest::pendingCounts($ana['id'], $beach['id']) === ['user' => 1, 'venue' => 0]);
    $r2 = MatchRequest::create($arena, $beto, $v('19:30', '20:30')); // mesmo horário, outra pessoa
    $r3 = MatchRequest::create($arena, $beto, $v('21:00', '22:00')); // outro horário
    $painel = MatchRequest::forOwner($dono);
    check('painel do dono: pendentes do horário mais próximo', array_map(fn($r) => (int)$r['id'], $painel['pending']) === [(int)$r1['id'], (int)$r2['id'], (int)$r3['id']]);
    check('painel: filtro por quadra', MatchRequest::forOwner($dono, $beach['id'])['pending'] === []);
    check('número de pendentes do dono', MatchRequest::pendingForOwner($dono) === 3);
    check('lista de quem pediu', MatchRequest::forUser($beto['id'], null, 1, 20)['total'] === 2 && MatchRequest::forUser($beto['id'], 'approved', 1, 20)['total'] === 0);

    echo "aprovar\n";
    $res = MatchRequest::approve($r1, $dono);
    check('aprova e cria a partida', $res['ok'] && $res['match_id'] > 0, var_export($res, true));
    $partidas[] = $res['match_id'];
    $jogo = Game::findById($res['match_id']);
    check('partida com o primeiro nome, no horário pedido, criada pelo dono', $jogo['renter_name'] === explode(' ', trim($ana['name']))[0]
        && $jogo['start_time'] === "$dia 19:00:00" && (int)$jogo['creator_user_id'] === $dono && $jogo['status'] === 'scheduled');
    $r1 = MatchRequest::findById((int)$r1['id']);
    check('pedido aprovado com a partida ligada', $r1['status'] === 'approved' && (int)$r1['match_id'] === $res['match_id'] && $r1['responded_at'] !== null);
    check('o outro pendente no mesmo horário é recusado sozinho', $res['declined'] === [(int)$r2['id']] && MatchRequest::findById((int)$r2['id'])['status'] === 'declined');
    check('o pendente em outro horário continua', MatchRequest::findById((int)$r3['id'])['status'] === 'pending');
    check('avisos: aprovado para a Ana, recusado para o Beto', count($notifs($ana['id'], 'request_approved')) === 1 && count($notifs($beto['id'], 'request_declined')) === 1);
    check('aprovar de novo: não está mais pendente', MatchRequest::approve($r1, $dono)['error'] === 'not_pending');

    echo "conflito na aprovação\n";
    $r4 = MatchRequest::create($beach, $ana, $v('10:00', '11:00'));
    $partidas[] = Game::create(['venue_id' => $beach['id'], 'creator_user_id' => $dono, 'start_time' => "$dia 10:30:00", 'end_time' => "$dia 11:30:00", 'sport_id' => null, 'renter_name' => 'Outro']);
    check('o dono marcou outra partida no meio: não aprova', MatchRequest::approve($r4, $dono)['error'] === 'overlap' && MatchRequest::findById((int)$r4['id'])['status'] === 'pending');

    echo "recusar\n";
    check('recusa com motivo', MatchRequest::decline($r4, 'Horário já reservado'));
    $r4 = MatchRequest::findById((int)$r4['id']);
    check('pedido recusado com o motivo', $r4['status'] === 'declined' && $r4['response_note'] === 'Horário já reservado');
    $rec = $notifs($ana['id'], 'request_declined');
    check('o aviso leva o motivo', count($rec) === 1 && $rec[0]['data']['response_note'] === 'Horário já reservado');
    check('recusar de novo: não muda', !MatchRequest::decline($r4, 'x'));

    echo "cancelar\n";
    check('pendente: cancela', MatchRequest::cancel(MatchRequest::findById((int)$r3['id'])) && MatchRequest::findById((int)$r3['id'])['status'] === 'cancelled');
    check('o dono recebe "cancelou"', count($notifs($dono, 'request_cancelled')) === 1);
    $perto = new DateTimeImmutable("$dia 17:30:00", VenueSchedule::timezone()); // 1h30 antes das 19:00
    check('aprovado a menos de 2 h: não cancela', !MatchRequest::cancel($r1, $perto) && MatchRequest::findById((int)$r1['id'])['status'] === 'approved');
    check('aprovado com antecedência: cancela', MatchRequest::cancel($r1));
    check('a partida sai da agenda', Game::findById($res['match_id'])['status'] === 'cancelled');
    check('recusado não cancela', !MatchRequest::cancel($r4));

    echo "expirar\n";
    $r5 = MatchRequest::create($beach, $beto, $v('15:00', '16:00'));
    check('antes do início não expira', MatchRequest::expireDue(new DateTimeImmutable("$dia 14:59:00", VenueSchedule::timezone())) === 0);
    check('no início, expira', MatchRequest::expireDue(new DateTimeImmutable("$dia 15:00:00", VenueSchedule::timezone())) === 1 && MatchRequest::findById((int)$r5['id'])['status'] === 'expired');
    check('quem pediu recebe "expirado"', count($notifs($beto['id'], 'request_expired')) === 1);
    check('expirado não se aprova', MatchRequest::approve(MatchRequest::findById((int)$r5['id']), $dono)['error'] === 'not_pending');

    echo "notificações\n";
    $lista = Notification::forUser($ana['id'], 1, 20);
    check('lista da Ana, mais recentes primeiro, com data decodificado', $lista['total'] >= 2 && is_array($lista['items'][0]['data']) && (int)$lista['items'][0]['id'] > (int)$lista['items'][1]['id']);
    $naoLidas = Notification::unreadCount($ana['id']);
    check('não lidas', $naoLidas >= 2);
    $ids = array_map(fn($n) => (int)$n['id'], array_slice($lista['items'], 0, 1));
    $doBeto = (int)$pdo->query("SELECT MAX(id) FROM notifications WHERE user_id = {$beto['id']}")->fetchColumn();
    check('marca uma como lida (a do Beto na lista é ignorada)', Notification::markRead($ana['id'], array_merge($ids, [$doBeto])) === 1 && Notification::unreadCount($ana['id']) === $naoLidas - 1);
    check('a do Beto continua não lida', (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE id = $doBeto AND read_at IS NULL")->fetchColumn() === 1);
    check('marca todas', Notification::markRead($ana['id']) === $naoLidas - 1 && Notification::unreadCount($ana['id']) === 0);
    check('lista vazia de ids não muda nada', Notification::markRead($beto['id'], []) === 0 && Notification::markRead($beto['id'], ['abc', -1]) === 0);
} finally {
    // Devolve tudo como estava
    $limpar();
    if ($partidas) {
        $pdo->exec('DELETE FROM matches WHERE id IN (' . implode(',', array_map('intval', array_filter($partidas))) . ')');
    }
    $pdo->exec("DELETE FROM notifications WHERE id > $ultimaNotif AND user_id IN ($dono, {$ana['id']}, {$beto['id']})");
}

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
