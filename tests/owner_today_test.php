<?php
// Testa o bloco "Hoje" do painel do dono (App\Core\OwnerToday, D67): partidas de hoje (quantas e
// qual é a próxima) e qual anúncio mostrar. Não usa o banco. Rodar da raiz do projeto:
//   php tests/owner_today_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\OwnerToday;

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

date_default_timezone_set('America/Sao_Paulo');
$now = new DateTimeImmutable('2026-11-10 15:00:00');
$venues = [
    ['id' => 1, 'name' => 'Arena', 'status' => 'available'],
    ['id' => 2, 'name' => 'Beach', 'status' => 'available'],
    ['id' => 3, 'name' => 'Velha', 'status' => 'unavailable'],
];
$m = fn($s, $e) => ['start_time' => "2026-11-10 $s:00", 'end_time' => "2026-11-10 $e:00"];

echo "partidas de hoje\n";
$r = OwnerToday::matches($venues, [], $now);
check('sem partidas', $r === ['count' => 0, 'next' => null, 'done' => false]);
$r = OwnerToday::matches($venues, [1 => [$m('08:00', '09:00'), $m('19:00', '20:00')], 2 => [$m('17:00', '18:00')]], $now);
check('conta todas as do dia (inclusive as que já passaram)', $r['count'] === 3);
check('a próxima é a que começa mais cedo, de qualquer quadra', $r['next'] === ['venue_id' => 2, 'venue_name' => 'Beach', 'start' => '17:00', 'end' => '18:00', 'ongoing' => false], var_export($r['next'], true));
$r = OwnerToday::matches($venues, [1 => [$m('14:30', '15:30'), $m('16:00', '17:00')]], $now);
check('a que está acontecendo agora conta como a próxima', $r['next']['start'] === '14:30' && $r['next']['ongoing'] === true);
$r = OwnerToday::matches($venues, [1 => [$m('14:00', '15:00')]], $now);
check('acabou exatamente agora: não é a próxima; o dia acabou', $r === ['count' => 1, 'next' => null, 'done' => true]);
$r = OwnerToday::matches($venues, [3 => [$m('16:00', '17:00')]], $now);
check('quadra desativada não entra', $r === ['count' => 0, 'next' => null, 'done' => false]);
$r = OwnerToday::matches([['id' => 1, 'name' => 'Arena', 'status' => 'available', 'deleted_at' => '2026-11-01']], [1 => [$m('16:00', '17:00')]], $now);
check('quadra excluída não entra', $r['count'] === 0);

echo "anúncio\n";
check('nenhum', OwnerToday::ad($venues, [], []) === ['state' => 'none', 'venue_id' => null, 'venue_name' => null]);
check('no ar', OwnerToday::ad($venues, [2 => ['status' => 'active']], [2]) === ['state' => 'running', 'venue_id' => 2, 'venue_name' => 'Beach']);
check('pausado', OwnerToday::ad($venues, [1 => ['status' => 'paused']], []) === ['state' => 'paused', 'venue_id' => 1, 'venue_name' => 'Arena']);
check('no ar ganha do pausado (mesmo em outra quadra)', OwnerToday::ad($venues, [1 => ['status' => 'paused'], 2 => ['status' => 'active']], [2])['state'] === 'running');
check('ativo que não aparece hoje (agendado) não conta como no ar', OwnerToday::ad($venues, [1 => ['status' => 'active']], [])['state'] === 'none');

echo "textos nos 7 idiomas\n";
$keys = ['today_title', 'today_requests_title', 'today_requests_none', 'today_requests_one', 'today_requests_many', 'today_messages_title',
    'today_messages_none', 'today_messages_one', 'today_messages_many', 'today_matches_title', 'today_matches_none', 'today_matches_next',
    'today_matches_now', 'today_matches_done', 'today_premium_title', 'today_premium_free', 'today_premium_requested', 'today_premium_expired',
    'today_premium_until', 'today_premium_nolimit', 'today_ad_running', 'today_ad_paused', 'today_ad_none', 'today_new_venue'];
foreach (['pt-br', 'en-us', 'es-es', 'it-it', 'hi-in', 'ja-jp', 'zh-cn'] as $code) {
    $lang = require __DIR__ . "/../lang/$code.php";
    $missing = array_values(array_filter($keys, fn($k) => empty($lang[$k])));
    check("$code: todas as chaves", $missing === [], implode(', ', $missing));
}

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
