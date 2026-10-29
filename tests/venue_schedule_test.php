<?php
// Testa a regra do status e as validações do horário e das partidas (App\Core\VenueSchedule).
// Não usa o banco. Rodar da raiz do projeto:  php tests/venue_schedule_test.php
// A pasta tests/ não vai para a produção (deploy.yml).

require __DIR__ . '/../vendor/autoload.php';

use App\Core\VenueSchedule;

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
function at(string $dateTime): DateTimeImmutable
{
    return new DateTimeImmutable($dateTime, VenueSchedule::timezone());
}

// Quarta, 11/11/2026. A quadra abre 08:00–22:00 todos os dias, menos domingo.
$dia = '2026-11-11';
$wd = (int)at($dia)->format('w');
$hours = [];
foreach ([1, 2, 3, 4, 5, 6] as $d) {
    $hours[$d] = ['opens_at' => '08:00:00', 'closes_at' => '22:00:00'];
}
$partida = ['start_time' => "$dia 19:00:00", 'end_time' => "$dia 21:00:00"];

echo "Status (exemplos da seção 3 de docs/modelo-status.md)\n";
$casos = [
    ['07:30', 'closed', "$dia 08:00"],
    ['10:00', 'available', "$dia 19:00"],
    ['19:30', 'in_use', "$dia 21:00"],
    ['21:15', 'available', "$dia 22:00"],
    ['23:00', 'closed', '2026-11-12 08:00'],
];
foreach ($casos as [$hora, $status, $ate]) {
    $r = VenueSchedule::status('available', $hours, [$partida], at("$dia $hora"));
    $got = $r['status_until'] ? $r['status_until']->format('Y-m-d H:i') : 'null';
    check("$hora → $status até $ate", $r['status'] === $status && $got === $ate, "veio {$r['status']} até $got");
}
$r = VenueSchedule::status('maintenance', $hours, [], at("$dia 10:00"));
check('manutenção → closed sem previsão', $r['status'] === 'closed' && $r['status_until'] === null);
$r = VenueSchedule::status('available', [], [], at("$dia 10:00"));
check('sem horário → closed sem previsão (D23)', $r['status'] === 'closed' && $r['status_until'] === null);

echo "\nvalidateHours\n";
$v = VenueSchedule::validateHours([
    1 => ['open' => '1', 'opens_at' => '08:00', 'closes_at' => '22:00'],
    2 => ['open' => '1', 'opens_at' => '22:00', 'closes_at' => '08:00'],
    3 => ['open' => '1', 'opens_at' => '25:00', 'closes_at' => '23:00'],
    4 => ['opens_at' => '08:00', 'closes_at' => '22:00'], // sem "open" = fechado
    5 => ['open' => '1', 'opens_at' => '06:30', 'closes_at' => '23:59'],
    6 => ['open' => '1', 'opens_at' => '10:00', 'closes_at' => '10:00'],
]);
check('dia válido vira HH:MM:SS', ($v['hours'][1] ?? null) === ['opens_at' => '08:00:00', 'closes_at' => '22:00:00']);
check('fecha antes de abrir', ($v['errors'][2] ?? '') === 'closes_before_opens');
check('hora inválida', ($v['errors'][3] ?? '') === 'invalid_time');
check('sem "open" fica fechado', !isset($v['hours'][4]) && !isset($v['errors'][4]));
check('até 23:59 aceito (D22)', isset($v['hours'][5]));
check('abre e fecha na mesma hora', ($v['errors'][6] ?? '') === 'closes_before_opens');
check('domingo sem dados fica fechado', !isset($v['hours'][0]) && !isset($v['errors'][0]));
check('lixo no lugar do dia não quebra', VenueSchedule::validateHours([1 => 'x', 2 => null]) === ['hours' => [], 'errors' => []]);

echo "\nvalidateMatch (agora = $dia 12:00, já existe 19:00–21:00)\n";
$agora = at("$dia 12:00");
$m = fn(string $ini, string $fim, string $data = '2026-11-11') =>
    VenueSchedule::validateMatch($hours, [$partida], at("$data $ini"), at("$data $fim"), $agora);
check('livre 14:00–16:00', $m('14:00', '16:00') === null);
check('encostando antes 17:00–19:00', $m('17:00', '19:00') === null);
check('encostando depois 21:00–22:00', $m('21:00', '22:00') === null);
check('sobrepõe 18:00–19:30', $m('18:00', '19:30') === 'overlap');
check('dentro da outra 19:30–20:00', $m('19:30', '20:00') === 'overlap');
check('engloba a outra 18:00–22:00', $m('18:00', '22:00') === 'overlap');
check('fim antes do início', $m('16:00', '15:00') === 'end_before_start');
check('fim igual ao início', $m('16:00', '16:00') === 'end_before_start');
check('antes de abrir 07:00–09:00 (amanhã)', $m('07:00', '09:00', '2026-11-12') === 'outside_hours');
check('fora do horário vem antes da sobreposição 18:00–22:30', $m('18:00', '22:30') === 'outside_hours');
check('depois de fechar sem conflito 22:00–23:00', $m('22:00', '23:00') === 'outside_hours');
check('já terminou 09:00–11:00', $m('09:00', '11:00') === 'in_past');
check('já começou e ainda não terminou 11:00–13:00', $m('11:00', '13:00') === null);
check('domingo fechado', $m('10:00', '12:00', '2026-11-15') === 'closed_day');
check('outro dia sem conflito', $m('19:00', '21:00', '2026-11-12') === null);
check('vira o dia', VenueSchedule::validateMatch($hours, [], at("$dia 21:00"), at('2026-11-12 01:00'), $agora) === 'different_day');

echo "\nmatchesOutsideHours\n";
$novoHorario = $hours;
$novoHorario[$wd] = ['opens_at' => '08:00:00', 'closes_at' => '20:00:00'];
$fora = VenueSchedule::matchesOutsideHours($novoHorario, [
    ['id' => 1] + $partida,
    ['id' => 2, 'start_time' => "$dia 10:00:00", 'end_time' => "$dia 11:00:00"],
]);
check('fechar às 20:00 deixa a de 19:00–21:00 de fora', array_column($fora, 'id') === [1]);
$semQuarta = $hours;
unset($semQuarta[$wd]);
check('fechar o dia deixa todas de fora', count(VenueSchedule::matchesOutsideHours($semQuarta, [$partida])) === 1);
check('horário igual não avisa nada', VenueSchedule::matchesOutsideHours($hours, [$partida]) === []);

echo "\nat (data + hora do formulário)\n";
check('data e hora válidas', VenueSchedule::at('2026-11-11', '19:00')?->format(DATE_ATOM) === '2026-11-11T19:00:00-03:00');
check('31/02 recusado', VenueSchedule::at('2026-02-31', '10:00') === null);
check('hora 24:00 recusada', VenueSchedule::at('2026-11-11', '24:00') === null);
check('data em outro formato recusada', VenueSchedule::at('11/11/2026', '10:00') === null);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
