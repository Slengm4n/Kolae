<?php
// Testa a regra do Kolae Premium do dono (App\Core\PremiumPlan, D61): quando o Premium vale, os
// estados da tela e a validade escolhida pelo admin. Não usa o banco. Rodar da raiz do projeto:
//   php tests/premium_plan_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\PremiumPlan;

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
$u = fn(array $campos) => $campos + ['id' => 1, 'name' => 'Dono', 'plan' => 'free', 'plan_until' => null, 'plan_requested_at' => null];

echo "isPremium\n";
check('grátis não é Premium', !PremiumPlan::isPremium($u([]), $hoje));
check('sem usuário não é Premium', !PremiumPlan::isPremium(null, $hoje));
check('Premium sem prazo vale', PremiumPlan::isPremium($u(['plan' => 'premium']), $hoje));
check('Premium com prazo futuro vale', PremiumPlan::isPremium($u(['plan' => 'premium', 'plan_until' => '2026-12-10']), $hoje));
check('Premium vale no último dia', PremiumPlan::isPremium($u(['plan' => 'premium', 'plan_until' => '2026-11-10']), $hoje));
check('Premium vencido ontem não vale', !PremiumPlan::isPremium($u(['plan' => 'premium', 'plan_until' => '2026-11-09']), $hoje));
check('plan_until vazio conta como sem prazo', PremiumPlan::isPremium($u(['plan' => 'premium', 'plan_until' => '']), $hoje));
check('conta sem a coluna plan conta como grátis', !PremiumPlan::isPremium(['id' => 1], $hoje));

echo "state\n";
check('free', PremiumPlan::state($u([]), $hoje) === 'free');
check('requested', PremiumPlan::state($u(['plan_requested_at' => '2026-11-09 10:00:00']), $hoje) === 'requested');
check('premium', PremiumPlan::state($u(['plan' => 'premium', 'plan_until' => '2026-12-01']), $hoje) === 'premium');
check('expired', PremiumPlan::state($u(['plan' => 'premium', 'plan_until' => '2026-11-01']), $hoje) === 'expired');
check('premium ganha de um pedido esquecido', PremiumPlan::state($u(['plan' => 'premium', 'plan_requested_at' => '2026-11-01 10:00:00']), $hoje) === 'premium');

echo "defaultUntil\n";
check('hoje + 30 dias', PremiumPlan::defaultUntil($hoje) === '2026-12-10', PremiumPlan::defaultUntil($hoje));

echo "parseUntil\n";
check('sem prazo => null', PremiumPlan::parseUntil('2020-01-01', true, $hoje) === [true, null]);
check('data futura aceita', PremiumPlan::parseUntil('2026-12-31', false, $hoje) === [true, '2026-12-31']);
check('hoje aceito', PremiumPlan::parseUntil('2026-11-10', false, $hoje) === [true, '2026-11-10']);
check('ontem recusado', PremiumPlan::parseUntil('2026-11-09', false, $hoje)[0] === false);
check('data inexistente recusada', PremiumPlan::parseUntil('2026-02-30', false, $hoje)[0] === false);
check('formato errado recusado', PremiumPlan::parseUntil('10/12/2026', false, $hoje)[0] === false);
check('vazio sem "sem prazo" recusado', PremiumPlan::parseUntil('', false, $hoje)[0] === false);
check('nulo sem "sem prazo" recusado', PremiumPlan::parseUntil(null, false, $hoje)[0] === false);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
