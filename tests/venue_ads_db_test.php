<?php
// Testa o VenueAd (anúncios, D63) no banco LOCAL (nunca na produção).
// Precisa do seed database/seeds/teste_status.sql: usa as quadras "Arena", "Beach" e "Excluida"
// do dono-s3@kolae.test, deixa o dono Premium só durante o teste e devolve tudo como estava.
// Rodar da raiz do projeto:  C:\xampp\php\php.exe tests/venue_ads_db_test.php

require __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\VenueAds;
use App\Core\VenueSchedule;
use App\Models\VenueAd;

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
$stmt = $pdo->prepare("SELECT v.name, v.id, v.user_id FROM venues v JOIN users u ON u.id = v.user_id
                       WHERE u.email = 'dono-s3@kolae.test' AND v.name IN ('Arena Teste S3', 'Beach Teste S3', 'Excluida Teste S3')");
$stmt->execute();
$quadras = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $quadras[$row['name']] = $row;
}
if (count($quadras) !== 3) {
    exit("Quadras de teste não encontradas: rode database/seeds/teste_status.sql antes.\n");
}
$arena = (int)$quadras['Arena Teste S3']['id'];
$beach = (int)$quadras['Beach Teste S3']['id'];
$excluida = (int)$quadras['Excluida Teste S3']['id'];
$dono = (int)$quadras['Arena Teste S3']['user_id'];
$planoOriginal = $pdo->query("SELECT plan, plan_until FROM users WHERE id = $dono")->fetch(PDO::FETCH_ASSOC);
$setPlano = fn(string $plan, ?string $until) => $pdo->prepare('UPDATE users SET plan = ?, plan_until = ? WHERE id = ?')->execute([$plan, $until, $dono]);
$ids = fn(array $porQuadra) => array_map('intval', array_keys($porQuadra));

$hoje = VenueSchedule::now();
$dia = $hoje->format('Y-m-d');
$valores = fn(array $over = []) => $over + ['title' => 'Terça com 20% off', 'body' => 'Reserve pelo WhatsApp.', 'starts_on' => $dia, 'ends_on' => VenueAds::lastDay($dia)];
$eventos = [];

try {
    $pdo->exec("DELETE FROM venue_ads WHERE venue_id IN ($arena, $beach, $excluida)");
    $setPlano('premium', null);

    echo "criar e ler\n";
    $id = VenueAd::create($arena, $valores());

    check('cria e devolve o id', is_int($id) && $id > 0);
    $ad = VenueAd::findById($id);
    check('nasce ativo, com os valores', $ad['status'] === 'active' && $ad['title'] === 'Terça com 20% off' && $ad['starts_on'] === $dia && $ad['image_path'] === null);
    check('é o anúncio em aberto da quadra', (int)(VenueAd::findOpenForVenue($arena, $hoje)['id'] ?? 0) === $id);
    check('outra quadra não tem anúncio em aberto', VenueAd::findOpenForVenue($beach, $hoje) === null);

    echo "aparece (visibleByVenue)\n";
    // Sem filtro vêm todas as quadras (o banco local pode ter outros anúncios): olha só as do teste
    check('aparece na lista do dia', array_values(array_intersect($ids(VenueAd::visibleByVenue($hoje)), [$arena, $beach, $excluida])) === [$arena]);
    check('filtrando pelas quadras da página', $ids(VenueAd::visibleByVenue($hoje, [$arena, $beach])) === [$arena]);
    check('filtro sem a quadra dele', VenueAd::visibleByVenue($hoje, [$beach]) === []);
    check('filtro vazio', VenueAd::visibleByVenue($hoje, []) === []);
    check('não traz os campos do dono', !array_key_exists('owner_plan', VenueAd::visibleByVenue($hoje)[$arena]));

    $setPlano('premium', $hoje->modify('-1 day')->format('Y-m-d'));
    check('Premium vencido: some', VenueAd::visibleByVenue($hoje, [$arena]) === []);
    check('Premium vencido: continua em aberto (salvo)', VenueAd::findOpenForVenue($arena, $hoje) !== null);
    $setPlano('free', null);
    check('dono Grátis: some', VenueAd::visibleByVenue($hoje, [$arena]) === []);
    $setPlano('premium', $dia);
    check('Premium vence hoje: ainda aparece', $ids(VenueAd::visibleByVenue($hoje, [$arena])) === [$arena]);

    $setPlano('premium', null); // sem prazo: os próximos passos olham dias à frente
    VenueAd::create($beach, $valores(['starts_on' => $hoje->modify('+3 days')->format('Y-m-d'), 'ends_on' => $hoje->modify('+5 days')->format('Y-m-d')]));
    check('agendado para depois: não aparece hoje', $ids(VenueAd::visibleByVenue($hoje, [$beach])) === []);
    check('agendado para depois: aparece no dia', $ids(VenueAd::visibleByVenue($hoje->modify('+3 days'), [$beach])) === [$beach]);

    VenueAd::create($excluida, $valores());
    check('quadra desativada (D34): não aparece', VenueAd::visibleByVenue($hoje, [$excluida]) === []);

    echo "editar\n";
    check('edita título e período', VenueAd::update($id, $arena, $valores(['title' => 'Quarta também'])));
    check('gravou', VenueAd::findById($id)['title'] === 'Quarta também');
    check('salvar sem mudar nada dá certo', VenueAd::update($id, $arena, $valores(['title' => 'Quarta também'])));
    check('não edita pela quadra errada', !VenueAd::update($id, $beach, $valores(['title' => 'Invasão'])) && VenueAd::findById($id)['title'] === 'Quarta também');
    check('foto: grava e volta para a capa', VenueAd::setImage($id, 'ad_x.jpg') && VenueAd::findById($id)['image_path'] === 'ad_x.jpg' && VenueAd::setImage($id, null) && VenueAd::findById($id)['image_path'] === null);

    echo "pausar, retomar, encerrar, remover\n";
    check('pausa', VenueAd::applyAction($id, $arena, 'pause') && VenueAd::findById($id)['status'] === 'paused');
    check('pausado: some', VenueAd::visibleByVenue($hoje, [$arena]) === []);
    check('pausado: continua em aberto', VenueAd::findOpenForVenue($arena, $hoje) !== null);
    check('não pausa duas vezes', !VenueAd::applyAction($id, $arena, 'pause'));
    check('não mexe pela quadra errada', !VenueAd::applyAction($id, $beach, 'resume') && VenueAd::findById($id)['status'] === 'paused');
    check('retoma e volta a aparecer', VenueAd::applyAction($id, $arena, 'resume') && $ids(VenueAd::visibleByVenue($hoje, [$arena])) === [$arena]);
    check('encerra', VenueAd::applyAction($id, $arena, 'end') && VenueAd::findById($id)['status'] === 'ended');
    check('encerrado: libera um novo', VenueAd::findOpenForVenue($arena, $hoje) === null);
    check('encerrado não reabre', !VenueAd::applyAction($id, $arena, 'resume'));

    $segundo = VenueAd::create($arena, $valores(['title' => 'Segundo anúncio']));
    check('remove com motivo (admin)', VenueAd::applyAction($segundo, $arena, 'remove', 'Texto enganoso'));
    $removido = VenueAd::findById($segundo);
    check('guarda o motivo', $removido['status'] === 'removed' && $removido['removed_reason'] === 'Texto enganoso');
    check('removido: some e libera um novo', VenueAd::visibleByVenue($hoje, [$arena]) === [] && VenueAd::findOpenForVenue($arena, $hoje) === null);
    check('histórico do mais novo para o mais antigo', array_map('intval', array_column(VenueAd::findByVenue($arena), 'id')) === [$segundo, $id]);

    echo "números (counts)\n";
    $terceiro = VenueAd::create($arena, $valores(['title' => 'Com números']));
    $ad = VenueAd::findById($terceiro);
    check('sem eventos: zero', VenueAd::counts($ad) === ['views' => 0, 'clicks' => 0]);
    $ins = $pdo->prepare("INSERT INTO venue_events (venue_id, type, source, created_at) VALUES (?, ?, 'site', ?)");
    $agora = $ad['created_at']; // no mesmo segundo em que o anúncio nasceu (o 2020 fica de fora)
    foreach ([['ad_view', $agora], ['ad_view', $agora], ['ad_click', $agora], ['view', $agora], ['ad_view', '2020-01-01 10:00:00']] as [$tipo, $quando]) {
        $ins->execute([$arena, $tipo, $quando]);
        $eventos[] = (int)$pdo->lastInsertId();
    }
    check('conta vezes que apareceu e cliques do período', VenueAd::counts($ad) === ['views' => 2, 'clicks' => 1], var_export(VenueAd::counts($ad), true));

    echo "dados do card (venuesForCards)\n";
    $cards = VenueAd::venuesForCards([$arena, $beach, $arena]);
    check('uma linha por quadra', array_map('intval', array_keys($cards)) == [$arena, $beach] || array_map('intval', array_keys($cards)) == [$beach, $arena], var_export(array_keys($cards), true));
    $c = $cards[$arena] ?? [];
    check('nome, dono, bairro e cidade', [$c['name'] ?? null, (int)($c['user_id'] ?? 0), $c['neighborhood'] ?? null, $c['city'] ?? null]
        === ['Arena Teste S3', $dono, 'Centro Teste S3', 'Ferraz Teste S3'], var_export($c, true));
    check('traz a coluna da capa (nula sem foto)', array_key_exists('cover', $c));
    check('lista vazia', VenueAd::venuesForCards([]) === []);
} finally {
    // Devolve tudo como estava
    $pdo->exec("DELETE FROM venue_ads WHERE venue_id IN ($arena, $beach, $excluida)");
    if ($eventos) {
        $pdo->exec('DELETE FROM venue_events WHERE id IN (' . implode(',', $eventos) . ')');
    }
    $setPlano($planoOriginal['plan'], $planoOriginal['plan_until']);
}

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
