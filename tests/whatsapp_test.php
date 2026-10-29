<?php
// Testa a normalização do WhatsApp do dono (User::normalizeWhatsapp / formatWhatsapp, D24).
// Não usa o banco. Rodar da raiz do projeto:  php tests/whatsapp_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Models\User;

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

$aceitos = [
    '(11) 91234-5678' => '5511912345678',
    '11912345678' => '5511912345678',
    '+55 11 91234-5678' => '5511912345678',
    '55 (21) 99876-5432' => '5521998765432',
    '(11) 4567-8901' => '551145678901',   // fixo, 8 dígitos
    ' 011 91234 5678 ' => null,           // com 0 da operadora: 12 dígitos sem 55, recusado
];
foreach ($aceitos as $entrada => $esperado) {
    $got = User::normalizeWhatsapp($entrada);
    check("'$entrada' → " . ($esperado ?? 'null'), $got === $esperado, 'veio ' . var_export($got, true));
}

$recusados = ['', 'abc', '1234', '(11) 1234-5678', '(01) 91234-5678', '(11) 81234-56789', '+1 415 555 0100', '5511912345678999'];
foreach ($recusados as $entrada) {
    check("recusa '$entrada'", User::normalizeWhatsapp($entrada) === null);
}

check('formata celular', User::formatWhatsapp('5511912345678') === '(11) 91234-5678');
check('formata fixo', User::formatWhatsapp('551145678901') === '(11) 4567-8901');
check('vazio vira texto vazio', User::formatWhatsapp(null) === '');
check('formato desconhecido fica como está', User::formatWhatsapp('123') === '123');

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas ? 1 : 0);
