<?php
// Testa as regras dos pedidos de horário (App\Core\MatchRequests, D64) e os textos das
// notificações (App\Core\NotificationText). Não usa o banco. Rodar da raiz do projeto:
//   php tests/match_requests_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\MatchRequests;
use App\Core\NotificationText;

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

$tz = new DateTimeZone('America/Sao_Paulo');
// Terça, 10/11/2026, 10:00. Quadra aberta todo dia das 08:00 às 22:00 (0 = domingo ... 6 = sábado)
$now = new DateTimeImmutable('2026-11-10 10:00:00', $tz);
$hours = [];
foreach (range(0, 6) as $d) {
    $hours[$d] = ['weekday' => $d, 'opens_at' => '08:00:00', 'closes_at' => '22:00:00'];
}
$matches = [['start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 21:00:00']];
$sports = [1, 3];
$input = fn(array $over = []) => $over + ['start_time' => '2026-11-12T17:00:00-03:00', 'end_time' => '2026-11-12T18:30:00-03:00', 'sport_id' => 1, 'message' => 'Somos 10'];
$errors = fn(array $over = []) => MatchRequests::validate($input($over), $hours, $matches, $sports, $now)['errors'];

echo "parseDateTime\n";
check('com segundos e fuso', MatchRequests::parseDateTime('2026-11-12T19:00:00-03:00')->format('Y-m-d H:i') === '2026-11-12 19:00');
check('sem segundos', MatchRequests::parseDateTime('2026-11-12T19:30-03:00')->format('H:i') === '19:30');
check('outro fuso vira o de Brasília', MatchRequests::parseDateTime('2026-11-12T22:00:00+00:00')->format('H:i') === '19:00');
foreach (['2026-11-12 19:00', '12/11/2026 19:00', '2026-02-30T19:00:00-03:00', '', 'amanhã', '2026-11-12T19:00:00'] as $ruim) {
    check("inválida: '$ruim'", MatchRequests::parseDateTime($ruim) === null);
}
check('não é texto', MatchRequests::parseDateTime(123) === null && MatchRequests::parseDateTime(null) === null);

echo "validate: pedido certo\n";
$r = MatchRequests::validate($input(), $hours, $matches, $sports, $now);
check('sem erros', $r['errors'] === [], var_export($r['errors'], true));
check('valores para o banco', $r['values'] === ['start_time' => '2026-11-12 17:00:00', 'end_time' => '2026-11-12 18:30:00', 'sport_id' => 1, 'message' => 'Somos 10']);
check('esporte e mensagem são opcionais', MatchRequests::validate(['start_time' => '2026-11-12T17:00:00-03:00', 'end_time' => '2026-11-12T18:00:00-03:00'], $hours, $matches, $sports, $now)['values']['sport_id'] === null);
check('mensagem: junta espaços; vazia vira null', MatchRequests::validate($input(['message' => "  Somos \n 10  "]), $hours, $matches, $sports, $now)['values']['message'] === 'Somos 10'
    && MatchRequests::validate($input(['message' => '   ']), $hours, $matches, $sports, $now)['values']['message'] === null);
check('encostar na partida das 19:00 pode (17:00–19:00)', $errors(['end_time' => '2026-11-12T19:00:00-03:00']) === []);

echo "validate: erros\n";
check('sem início e sem fim', $errors(['start_time' => '', 'end_time' => null]) === ['start_time' => 'required', 'end_time' => 'required']);
check('formato errado', $errors(['start_time' => '12/11 17h']) === ['start_time' => 'invalid']);
check('fora do bloco de 30 min', $errors(['start_time' => '2026-11-12T17:15:00-03:00']) === ['start_time' => 'not_block']);
check('fim fora do bloco', $errors(['end_time' => '2026-11-12T18:45:00-03:00']) === ['end_time' => 'not_block']);
check('com segundos não é bloco', $errors(['start_time' => '2026-11-12T17:00:30-03:00']) === ['start_time' => 'not_block']);
check('fim antes do início', $errors(['end_time' => '2026-11-12T16:00:00-03:00']) === ['end_time' => 'end_before_start']);
check('fim igual ao início', $errors(['end_time' => '2026-11-12T17:00:00-03:00']) === ['end_time' => 'end_before_start']);
check('dias diferentes', $errors(['start_time' => '2026-11-12T21:30:00-03:00', 'end_time' => '2026-11-13T00:30:00-03:00']) === ['end_time' => 'different_day']);
check('no passado', $errors(['start_time' => '2026-11-10T08:00:00-03:00', 'end_time' => '2026-11-10T09:00:00-03:00']) === ['start_time' => 'in_past']);
check('começando agora também é passado', $errors(['start_time' => '2026-11-10T10:00:00-03:00', 'end_time' => '2026-11-10T11:00:00-03:00']) === ['start_time' => 'in_past']);
check('30 dias à frente pode', $errors(['start_time' => '2026-12-10T09:00:00-03:00', 'end_time' => '2026-12-10T10:00:00-03:00']) === []);
check('mais de 30 dias não', $errors(['start_time' => '2026-12-10T10:30:00-03:00', 'end_time' => '2026-12-10T11:30:00-03:00']) === ['start_time' => 'too_far']);
check('4 horas pode', $errors(['start_time' => '2026-11-12T08:00:00-03:00', 'end_time' => '2026-11-12T12:00:00-03:00']) === []);
check('mais de 4 horas não', $errors(['start_time' => '2026-11-12T08:00:00-03:00', 'end_time' => '2026-11-12T12:30:00-03:00']) === ['end_time' => 'too_long']);
check('fora do funcionamento', $errors(['start_time' => '2026-11-12T21:30:00-03:00', 'end_time' => '2026-11-12T22:30:00-03:00']) === ['start_time' => 'outside_hours']);
$semDomingo = $hours;
unset($semDomingo[0]);
check('dia em que a quadra não abre (domingo)', MatchRequests::validate($input(['start_time' => '2026-11-15T17:00:00-03:00', 'end_time' => '2026-11-15T18:00:00-03:00']), $semDomingo, [], $sports, $now)['errors'] === ['start_time' => 'closed_day']);
check('horário ocupado', $errors(['start_time' => '2026-11-12T18:30:00-03:00', 'end_time' => '2026-11-12T19:30:00-03:00']) === ['start_time' => 'overlap']);
check('esporte que a quadra não tem', $errors(['sport_id' => 2]) === ['sport_id' => 'not_offered']);
check('esporte inválido', $errors(['sport_id' => 'futebol']) === ['sport_id' => 'invalid'] && $errors(['sport_id' => 0]) === ['sport_id' => 'invalid']);
check('esporte como texto com dígitos vale', $errors(['sport_id' => '3']) === []);
check('mensagem com 200 pode', $errors(['message' => str_repeat('é', 200)]) === []);
check('mensagem com 201 não', $errors(['message' => str_repeat('é', 201)]) === ['message' => 'too_long']);
check('mensagem que não é texto', $errors(['message' => ['oi']]) === ['message' => 'invalid']);
check('com erro, não devolve valores', MatchRequests::validate($input(['sport_id' => 2]), $hours, $matches, $sports, $now)['values'] === []);
check('todo código tem texto para a API', (function () use ($errors) {
    foreach (['start_time' => ['required', 'invalid', 'in_past', 'too_far', 'not_block', 'closed_day', 'outside_hours', 'overlap'],
              'end_time' => ['required', 'invalid', 'not_block', 'end_before_start', 'different_day', 'too_short', 'too_long'],
              'sport_id' => ['invalid', 'not_offered'], 'message' => ['invalid', 'too_long']] as $field => $codes) {
        foreach ($codes as $code) {
            if (MatchRequests::message($field, $code) === 'Valor inválido.') {
                return false;
            }
        }
    }
    return true;
})());

echo "cancelar, expirar, conflito, limite\n";
$pend = ['status' => 'pending', 'start_time' => '2026-11-10 11:00:00', 'end_time' => '2026-11-10 12:00:00'];
check('pendente: cancela a qualquer hora', MatchRequests::canCancel($pend, $now));
check('aprovado a 2 h: cancela', MatchRequests::canCancel(['status' => 'approved', 'start_time' => '2026-11-10 12:00:00'], $now));
check('aprovado a menos de 2 h: não', !MatchRequests::canCancel(['status' => 'approved', 'start_time' => '2026-11-10 11:59:00'], $now));
foreach (['declined', 'cancelled', 'expired'] as $st) {
    check("$st: não cancela", !MatchRequests::canCancel(['status' => $st, 'start_time' => '2026-11-20 10:00:00'], $now));
}
check('pendente que chegou ao início expira', MatchRequests::isDue(['status' => 'pending', 'start_time' => '2026-11-10 10:00:00'], $now));
check('pendente no futuro não expira', !MatchRequests::isDue($pend, $now));
check('aprovado não expira', !MatchRequests::isDue(['status' => 'approved', 'start_time' => '2026-11-10 09:00:00'], $now));
check('sobreposição', MatchRequests::overlaps(['start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 20:00:00'], ['start_time' => '2026-11-12 19:30:00', 'end_time' => '2026-11-12 21:00:00']));
check('encostar não sobrepõe', !MatchRequests::overlaps(['start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 20:00:00'], ['start_time' => '2026-11-12 20:00:00', 'end_time' => '2026-11-12 21:00:00']));
check('limite: livre', MatchRequests::limitReached(2, 0) === null);
check('limite: 3 pendentes', MatchRequests::limitReached(3, 0) === 'user');
check('limite: 1 nesta quadra', MatchRequests::limitReached(1, 1) === 'venue');

echo "motivo da recusa e primeiro nome\n";
check('motivo opcional', MatchRequests::cleanNote(null) === [null, null] && MatchRequests::cleanNote('') === [null, null] && MatchRequests::cleanNote('   ') === [null, null]);
check('motivo limpo', MatchRequests::cleanNote("  Quadra  em\nmanutenção ") === ['Quadra em manutenção', null]);
check('motivo com 201: erro', MatchRequests::cleanNote(str_repeat('a', 201)) === [null, 'too_long']);
check('motivo que não é texto', MatchRequests::cleanNote(['x']) === [null, 'invalid']);
check('primeiro nome', MatchRequests::firstName('  Maria Clara Souza ') === 'Maria' && MatchRequests::firstName('') === '' && MatchRequests::firstName(null) === '');

echo "NotificationText\n";
$pt = require __DIR__ . '/../lang/pt-br.php';
$en = require __DIR__ . '/../lang/en-us.php';
$dados = ['request_id' => 31, 'venue_id' => 2, 'venue_name' => 'Arena Central', 'user_name' => 'Maria', 'start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 20:00:00', 'response_note' => null];
$txt = NotificationText::render(['type' => 'request_approved', 'data' => json_encode($dados)], $pt);
check('aprovado em português', $txt === ['title' => 'Pedido aprovado', 'body' => 'Arena Central aprovou seu horário de 12/11, das 19:00 às 20:00.'], var_export($txt, true));
check('novo pedido para o dono (primeiro nome)', NotificationText::render(['type' => 'request_new', 'data' => $dados], $pt)['body'] === 'Maria pediu 12/11, das 19:00 às 20:00, em Arena Central.');
check('em inglês', NotificationText::render(['type' => 'request_approved', 'data' => $dados], $en)['title'] === 'Request approved');
check('recusa sem motivo', !str_contains(NotificationText::render(['type' => 'request_declined', 'data' => $dados], $pt)['body'], 'Motivo'));
check('recusa com motivo', str_ends_with(NotificationText::render(['type' => 'request_declined', 'data' => ['response_note' => 'Quadra fechada'] + $dados], $pt)['body'], 'Motivo: Quadra fechada'));
check('anúncio removido (dados da D63)', NotificationText::render(['type' => 'ad_removed', 'data' => ['venue_name' => 'Arena', 'ad_title' => 'Sexta 50%', 'reason' => 'Texto enganoso']], $pt)['body']
    === 'O anúncio "Sexta 50%" (Arena) foi removido pela equipe do Kolae. Motivo: Texto enganoso');
check('tipo desconhecido: texto genérico', NotificationText::render(['type' => 'plan_xyz', 'data' => []], $pt) === ['title' => 'Kolae', 'body' => 'Você tem um aviso novo.']);
check('todos os tipos têm título e texto nos 7 idiomas', (function () {
    foreach (['pt-br', 'en-us', 'es-es', 'it-it', 'hi-in', 'ja-jp', 'zh-cn'] as $code) {
        $lang = require __DIR__ . "/../lang/$code.php";
        foreach (NotificationText::TYPES as $type) {
            if (empty($lang["notif_{$type}_title"]) || empty($lang["notif_{$type}_body"])) {
                echo "    falta $code $type\n";
                return false;
            }
        }
    }
    return true;
})());
check('Accept-Language', NotificationText::langFromHeader('en-US,en;q=0.9') === 'en-us' && NotificationText::langFromHeader('es') === 'es-es'
    && NotificationText::langFromHeader('fr-FR') === 'pt-br' && NotificationText::langFromHeader(null) === 'pt-br' && NotificationText::langFromHeader('pt-BR') === 'pt-br');

echo "e-mail ao dono (Mailer::requestNewMessage)\n";
if (!defined('BASE_URL')) define('BASE_URL', 'https://kolae.online');
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
[$html, $text] = \App\Core\Mailer::requestNewMessage('Paulo Souza', ['user_name' => 'Maria Clara Souza', 'venue_name' => 'Arena Central',
    'start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 20:00:00', 'sport_name' => 'Futebol', 'message' => 'Somos 10 <b>', 'email' => 'maria@exemplo.com']);
check('texto: primeiro nome, quadra, horário, esporte e mensagem', str_contains($text, 'Olá, Paulo!') && str_contains($text, 'Maria pediu') && !str_contains($text, 'Clara')
    && str_contains($text, 'Arena Central') && str_contains($text, '12/11, das 19:00 às 20:00') && str_contains($text, 'Esporte: Futebol') && str_contains($text, '"Somos 10 <b>"'));
check('texto e HTML levam o link da tela de solicitações', str_contains($text, 'https://kolae.online/dashboard/notificacoes') && str_contains($html, 'https://kolae.online/dashboard/notificacoes'));
check('HTML escapa a mensagem de quem pediu', str_contains($html, 'Somos 10 &lt;b&gt;') && !str_contains($html, 'Somos 10 <b>'));
check('nunca o e-mail de quem pediu', !str_contains($html . $text, 'maria@exemplo.com'));
[, $semExtras] = \App\Core\Mailer::requestNewMessage('Paulo', ['user_name' => 'Maria', 'venue_name' => 'Arena', 'start_time' => '2026-11-12 19:00:00', 'end_time' => '2026-11-12 20:00:00', 'sport_name' => null, 'message' => null]);
check('sem esporte e sem mensagem, as linhas somem', !str_contains($semExtras, 'Esporte:') && !str_contains($semExtras, '""'));

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
