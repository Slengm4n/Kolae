<?php
// Testa VenueHour e Game no banco LOCAL (nunca na produção).
// Precisa do seed database/seeds/teste_status.sql aplicado: usa a quadra "Duda Teste S3",
// que não tem horário, e deixa tudo como encontrou no final.
// Rodar da raiz do projeto:  C:\xampp\php\php.exe tests/agenda_db_test.php

require __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\VenueSchedule;
use App\Models\Game;
use App\Models\VenueHour;

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
$stmt = $pdo->prepare("SELECT v.id, v.user_id FROM venues v JOIN users u ON u.id = v.user_id
                       WHERE v.name = 'Duda Teste S3' AND u.email = 'dono-s3@kolae.test'");
$stmt->execute();
$venue = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venue) {
    exit("Quadra 'Duda Teste S3' não encontrada: rode database/seeds/teste_status.sql antes.\n");
}
$venueId = (int)$venue['id'];
$horarioOriginal = VenueHour::findByVenueId($venueId);
$criadas = [];

try {
    echo "VenueHour\n";
    $v = VenueSchedule::validateHours([
        1 => ['open' => '1', 'opens_at' => '08:00', 'closes_at' => '22:00'],
        3 => ['open' => '1', 'opens_at' => '09:30', 'closes_at' => '18:00'],
    ]);
    check('grava o horário', VenueHour::replaceForVenue($venueId, $v['hours']));
    $lido = VenueHour::findByVenueId($venueId);
    check('lê o que gravou', $lido === $v['hours'], json_encode($lido));
    check('troca a semana inteira', VenueHour::replaceForVenue($venueId, [5 => ['opens_at' => '10:00:00', 'closes_at' => '12:00:00']])
        && array_keys(VenueHour::findByVenueId($venueId)) === [5]);
    // O CHECK do banco recusa e o model registra "Erro ao salvar o horário" no log: esperado aqui
    check('horário inválido não apaga o atual', !VenueHour::replaceForVenue($venueId, [5 => ['opens_at' => '12:00:00', 'closes_at' => '10:00:00']])
        && array_keys(VenueHour::findByVenueId($venueId)) === [5]);

    echo "\nGame\n";
    $amanha = VenueSchedule::now()->modify('+1 day')->format('Y-m-d');
    $id = Game::create([
        'venue_id' => $venueId,
        'creator_user_id' => (int)$venue['user_id'],
        'start_time' => "$amanha 19:00:00",
        'end_time' => "$amanha 21:00:00",
        'renter_name' => '  Time do Carlos  ',
        'sport_id' => ''
    ]);
    $criadas[] = $id;
    check('cria a partida', is_int($id) && $id > 0);

    $lista = Game::findDetailedBetween($venueId, "$amanha 00:00:00", "$amanha 23:59:59");
    check('aparece na agenda do dia', count($lista) === 1 && (int)$lista[0]['id'] === $id);
    check('para quem: sem espaços; esporte vazio vira null', ($lista[0]['renter_name'] ?? '') === 'Time do Carlos' && $lista[0]['sport_id'] === null);
    check('aparece nas futuras (sem limite)', in_array($id, array_map('intval', array_column(Game::findDetailedBetween($venueId, VenueSchedule::now()->format('Y-m-d H:i:s')), 'id')), true));
    check('a API enxerga (findScheduledOn)', count(Game::findScheduledOn([$venueId], $amanha)[$venueId] ?? []) === 1);

    check('não cancela por outra quadra', !Game::cancel($id, $venueId + 1000000));
    check('cancela', Game::cancel($id, $venueId));
    check('não cancela duas vezes', !Game::cancel($id, $venueId));
    check('cancelada some da agenda', Game::findDetailedBetween($venueId, "$amanha 00:00:00", "$amanha 23:59:59") === []);
    check('cancelada some da API', (Game::findScheduledOn([$venueId], $amanha)[$venueId] ?? []) === []);
} finally {
    // Devolve a quadra como estava
    VenueHour::replaceForVenue($venueId, $horarioOriginal);
    $criadas = array_filter($criadas, 'is_int');
    if ($criadas) {
        $pdo->prepare('DELETE FROM matches WHERE id IN (' . implode(',', $criadas) . ')')->execute();
    }
}

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
