<?php
// Testa as regras dos anúncios (App\Core\VenueAds, D63): formulário, quando aparece, situação
// para o dono, ações e sorteio do dia. Não usa o banco. Rodar da raiz do projeto:
//   php tests/venue_ads_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\VenueAds;

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

$hoje = new DateTimeImmutable('2026-11-10', new DateTimeZone('America/Sao_Paulo'));
$form = fn(array $over = []) => $over + ['title' => 'Terça com 20% off', 'body' => 'Reserve pelo WhatsApp.', 'starts_on' => '2026-11-10', 'ends_on' => '2026-12-09'];
$erros = fn(array $input, ?array $current = null) => VenueAds::validate($input, $hoje, $current)['errors'];

echo "validate\n";
$r = VenueAds::validate($form(), $hoje);
check('formulário certo: sem erros', $r['errors'] === [], var_export($r, true));
check('devolve os valores limpos', $r['values'] === ['title' => 'Terça com 20% off', 'body' => 'Reserve pelo WhatsApp.', 'starts_on' => '2026-11-10', 'ends_on' => '2026-12-09']);
check('junta espaços e quebras de linha', VenueAds::validate($form(['title' => "  Terça \n  com   desconto "]), $hoje)['values']['title'] === 'Terça com desconto');
check('título vazio', $erros($form(['title' => '   '])) === ['title' => 'required']);
check('título com 60 passa', $erros($form(['title' => str_repeat('é', 60)])) === []);
check('título com 61 não passa', $erros($form(['title' => str_repeat('é', 61)])) === ['title' => 'too_long']);
check('texto vazio', $erros($form(['body' => ''])) === ['body' => 'required']);
check('texto com 140 passa', $erros($form(['body' => str_repeat('a', 140)])) === []);
check('texto com 141 não passa', $erros($form(['body' => str_repeat('a', 141)])) === ['body' => 'too_long']);
check('início inválido', $erros($form(['starts_on' => '2026-02-30'])) === ['starts_on' => 'invalid']);
check('início em outro formato', $erros($form(['starts_on' => '10/11/2026'])) === ['starts_on' => 'invalid']);
check('início no passado', $erros($form(['starts_on' => '2026-11-09', 'ends_on' => '2026-11-20'])) === ['starts_on' => 'in_past']);
check('início no futuro passa', $erros($form(['starts_on' => '2026-11-20', 'ends_on' => '2026-11-25'])) === []);
check('fim antes do início', $erros($form(['ends_on' => '2026-11-09'])) === ['ends_on' => 'before_start']);
check('um dia só (início = fim) passa', $erros($form(['ends_on' => '2026-11-10'])) === []);
check('30 dias contando o primeiro passa', $erros($form(['ends_on' => '2026-12-09'])) === []);
check('31 dias não passa', $erros($form(['ends_on' => '2026-12-10'])) === ['ends_on' => 'too_long']);
check('fim vazio', $erros($form(['ends_on' => ''])) === ['ends_on' => 'invalid']);
check('vários erros de uma vez', array_keys($erros(['title' => '', 'body' => '', 'starts_on' => 'x', 'ends_on' => 'y'])) === ['title', 'body', 'starts_on', 'ends_on']);
check('com erro, nenhum valor', VenueAds::validate($form(['title' => '']), $hoje)['values'] === []);

echo "validate ao editar\n";
$rodando = ['starts_on' => '2026-11-01', 'ends_on' => '2026-11-20'];
check('editar mantendo o início que já passou', $erros($form(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-20']), $rodando) === []);
check('editar trocando para outro início no passado', $erros($form(['starts_on' => '2026-11-02', 'ends_on' => '2026-11-20']), $rodando) === ['starts_on' => 'in_past']);
check('editar não estica além de 30 dias do início', $erros($form(['starts_on' => '2026-11-01', 'ends_on' => '2026-12-01']), $rodando) === ['ends_on' => 'too_long']);
check('editar com fim antes de hoje', $erros($form(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-09']), $rodando) === ['ends_on' => 'in_past']);
check('lastDay', VenueAds::lastDay('2026-11-10') === '2026-12-09' && VenueAds::lastDay('2026-02-01') === '2026-03-02');

echo "isVisible\n";
$ad = ['status' => 'active', 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-20'];
$quadra = ['status' => 'available', 'deleted_at' => null];
check('ativo, no período, Premium: aparece', VenueAds::isVisible($ad, true, $quadra, $hoje));
check('dono sem Premium: não aparece', !VenueAds::isVisible($ad, false, $quadra, $hoje));
check('pausado: não aparece', !VenueAds::isVisible(['status' => 'paused'] + $ad, true, $quadra, $hoje));
check('encerrado: não aparece', !VenueAds::isVisible(['status' => 'ended'] + $ad, true, $quadra, $hoje));
check('removido: não aparece', !VenueAds::isVisible(['status' => 'removed'] + $ad, true, $quadra, $hoje));
check('começa amanhã: ainda não aparece', !VenueAds::isVisible(['starts_on' => '2026-11-11'] + $ad, true, $quadra, $hoje));
check('acabou ontem: não aparece', !VenueAds::isVisible(['ends_on' => '2026-11-09'] + $ad, true, $quadra, $hoje));
check('último dia: aparece', VenueAds::isVisible(['ends_on' => '2026-11-10'] + $ad, true, $quadra, $hoje));
check('quadra desativada (D34): não aparece', !VenueAds::isVisible($ad, true, ['status' => 'unavailable'] + $quadra, $hoje));
check('quadra excluída: não aparece', !VenueAds::isVisible($ad, true, ['deleted_at' => '2026-11-01 10:00:00'] + $quadra, $hoje));
check('quadra em manutenção: aparece', VenueAds::isVisible($ad, true, ['status' => 'maintenance'] + $quadra, $hoje));

echo "isOpen (um por quadra)\n";
check('ativo no período: em aberto', VenueAds::isOpen($ad, $hoje));
check('pausado no período: em aberto', VenueAds::isOpen(['status' => 'paused'] + $ad, $hoje));
check('agendado para depois: em aberto', VenueAds::isOpen(['starts_on' => '2026-11-15'] + $ad, $hoje));
check('vencido: libera outro', !VenueAds::isOpen(['ends_on' => '2026-11-09'] + $ad, $hoje));
check('encerrado: libera outro', !VenueAds::isOpen(['status' => 'ended'] + $ad, $hoje));
check('removido: libera outro', !VenueAds::isOpen(['status' => 'removed'] + $ad, $hoje));

echo "ownerState\n";
$estado = fn(array $over, bool $premium = true) => VenueAds::ownerState($over + $ad, $premium, $hoje);
check('aparecendo', $estado([]) === 'running');
check('agendado', $estado(['starts_on' => '2026-11-12']) === 'scheduled');
check('pausado', $estado(['status' => 'paused']) === 'paused');
check('Premium venceu', $estado([], false) === 'premium_expired');
check('pausado e sem Premium continua "pausado"', $estado(['status' => 'paused'], false) === 'paused');
check('período acabou', $estado(['ends_on' => '2026-11-09']) === 'finished');
check('encerrado pelo dono', $estado(['status' => 'ended']) === 'ended');
check('removido pelo admin', $estado(['status' => 'removed'], false) === 'removed');

echo "nextStatus\n";
check('pausar ativo', VenueAds::nextStatus('active', 'pause') === 'paused');
check('retomar pausado', VenueAds::nextStatus('paused', 'resume') === 'active');
check('encerrar ativo ou pausado', VenueAds::nextStatus('active', 'end') === 'ended' && VenueAds::nextStatus('paused', 'end') === 'ended');
check('remover qualquer um que não foi removido', VenueAds::nextStatus('ended', 'remove') === 'removed' && VenueAds::nextStatus('active', 'remove') === 'removed');
check('não pausa pausado', VenueAds::nextStatus('paused', 'pause') === null);
check('não retoma ativo', VenueAds::nextStatus('active', 'resume') === null);
check('não mexe em removido', VenueAds::nextStatus('removed', 'resume') === null && VenueAds::nextStatus('removed', 'remove') === null);
check('não reabre encerrado', VenueAds::nextStatus('ended', 'resume') === null);
check('ação desconhecida', VenueAds::nextStatus('active', 'apagar') === null);

echo "dailyPick\n";
$itens = array_map(fn($id) => ['id' => $id], range(1, 8));
$dia1 = array_column(VenueAds::dailyPick($itens, 3, $hoje), 'id');
check('limita a 3', count($dia1) === 3);
check('mesmo dia, mesma ordem', $dia1 === array_column(VenueAds::dailyPick(array_reverse($itens), 3, $hoje), 'id'));
$mudou = false;
for ($d = 1; $d <= 5 && !$mudou; $d++) {
    $mudou = array_column(VenueAds::dailyPick($itens, 8, $hoje->modify("+$d day")), 'id') !== array_column(VenueAds::dailyPick($itens, 8, $hoje), 'id');
}
check('outro dia, outra ordem', $mudou);
check('sem repetir nem perder itens', (function () use ($itens, $hoje) { $r = array_column(VenueAds::dailyPick($itens, 8, $hoje), 'id'); sort($r); return $r === range(1, 8); })());
check('lista vazia', VenueAds::dailyPick([], 3, $hoje) === []);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
