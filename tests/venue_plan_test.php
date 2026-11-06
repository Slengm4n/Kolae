<?php
// Testa a regra do plano da quadra (App\Core\VenuePlan, D61): quando o Premium vale, os estados
// da tela e a validade escolhida pelo admin. Não usa o banco. Rodar da raiz do projeto:
//   php tests/venue_plan_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\VenuePlan;

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

$hoje = new DateTimeImmutable('2026-11-10 15:00:00', new DateTimeZone('America/Sao_Paulo'));
$q = fn(array $campos) => $campos + ['id' => 1, 'name' => 'Quadra', 'plan' => 'free', 'plan_until' => null, 'plan_requested_at' => null];

echo "isPremium\n";
check('grátis não é Premium', !VenuePlan::isPremium($q([]), $hoje));
check('Premium sem prazo vale', VenuePlan::isPremium($q(['plan' => 'premium']), $hoje));
check('Premium com prazo futuro vale', VenuePlan::isPremium($q(['plan' => 'premium', 'plan_until' => '2026-12-10']), $hoje));
check('Premium vale no último dia', VenuePlan::isPremium($q(['plan' => 'premium', 'plan_until' => '2026-11-10']), $hoje));
check('Premium vencido ontem não vale', !VenuePlan::isPremium($q(['plan' => 'premium', 'plan_until' => '2026-11-09']), $hoje));
check('plan_until vazio conta como sem prazo', VenuePlan::isPremium($q(['plan' => 'premium', 'plan_until' => '']), $hoje));
check('quadra sem a coluna plan conta como grátis', !VenuePlan::isPremium(['id' => 1], $hoje));

echo "state\n";
check('free', VenuePlan::state($q([]), $hoje) === 'free');
check('requested', VenuePlan::state($q(['plan_requested_at' => '2026-11-09 10:00:00']), $hoje) === 'requested');
check('premium', VenuePlan::state($q(['plan' => 'premium', 'plan_until' => '2026-12-01']), $hoje) === 'premium');
check('expired', VenuePlan::state($q(['plan' => 'premium', 'plan_until' => '2026-11-01']), $hoje) === 'expired');
check('premium ganha de um pedido esquecido', VenuePlan::state($q(['plan' => 'premium', 'plan_requested_at' => '2026-11-01 10:00:00']), $hoje) === 'premium');

echo "defaultUntil\n";
check('hoje + 30 dias', VenuePlan::defaultUntil($hoje) === '2026-12-10', VenuePlan::defaultUntil($hoje));

echo "parseUntil\n";
check('sem prazo => null', VenuePlan::parseUntil('2020-01-01', true, $hoje) === [true, null]);
check('data futura aceita', VenuePlan::parseUntil('2026-12-31', false, $hoje) === [true, '2026-12-31']);
check('hoje aceito', VenuePlan::parseUntil('2026-11-10', false, $hoje) === [true, '2026-11-10']);
check('ontem recusado', VenuePlan::parseUntil('2026-11-09', false, $hoje)[0] === false);
check('data inexistente recusada', VenuePlan::parseUntil('2026-02-30', false, $hoje)[0] === false);
check('formato errado recusado', VenuePlan::parseUntil('10/12/2026', false, $hoje)[0] === false);
check('vazio sem "sem prazo" recusado', VenuePlan::parseUntil('', false, $hoje)[0] === false);
check('nulo sem "sem prazo" recusado', VenuePlan::parseUntil(null, false, $hoje)[0] === false);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
