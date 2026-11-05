<?php
// Testa as redes sociais da quadra (App\Core\VenueSocials, D60): o que o painel aceita,
// o que grava e o objeto "socials" da API. Não usa o banco. Rodar da raiz do projeto:
//   php tests/venue_socials_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\VenueSocials;

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

// entrada => esperado (string = valor gravado, null = vazio, false = recusado)
$casos = [
    'normalizeInstagram' => [
        'arenadoxuxa' => 'arenadoxuxa',
        '@arenadoxuxa' => 'arenadoxuxa',
        '  @arena.do_xuxa  ' => 'arena.do_xuxa',
        'https://www.instagram.com/arenadoxuxa/' => 'arenadoxuxa',
        'instagram.com/arenadoxuxa?igsh=abc' => 'arenadoxuxa',
        'http://instagram.com/arenadoxuxa' => 'arenadoxuxa',
        'https://m.instagram.com/arenadoxuxa' => 'arenadoxuxa',
        '' => null,
        '   ' => null,
        'arena do xuxa' => false,                          // espaço
        'arena-xuxa' => false,                             // hífen não existe no Instagram
        'https://instagram.com/p/C1234abc/' => false,      // link de post, não de perfil
        'https://instagram.com/' => false,                 // sem usuário
        'https://facebook.com/arenadoxuxa' => false,       // link de outra rede
        str_repeat('a', 31) => false,                      // passa de 30
    ],
    'normalizeTiktok' => [
        'arenadoxuxa' => 'arenadoxuxa',
        '@arenadoxuxa' => 'arenadoxuxa',
        'https://www.tiktok.com/@arenadoxuxa' => 'arenadoxuxa',
        'tiktok.com/@arenadoxuxa/video/7300000000000000000' => 'arenadoxuxa',
        '' => null,
        'https://vm.tiktok.com/ZMabc123/' => false,        // link encurtado: não dá para saber o usuário
        'https://www.tiktok.com/arenadoxuxa' => false,     // perfil do TikTok sempre tem @
        'https://instagram.com/arenadoxuxa' => false,
    ],
    'normalizeFacebookUrl' => [
        'https://facebook.com/arenadoxuxa' => 'https://facebook.com/arenadoxuxa',
        'facebook.com/arenadoxuxa' => 'https://facebook.com/arenadoxuxa',
        'http://www.facebook.com/arenadoxuxa' => 'https://www.facebook.com/arenadoxuxa',
        'https://m.facebook.com/profile.php?id=100000000000000' => 'https://m.facebook.com/profile.php?id=100000000000000',
        'https://fb.com/arenadoxuxa' => 'https://fb.com/arenadoxuxa',
        '' => null,
        'https://facebook.com/' => false,                  // sem a página
        'https://instagram.com/arenadoxuxa' => false,      // outro site
        'https://facebook.com.golpe.com/arena' => false,   // domínio que só começa com facebook.com
        'javascript:alert(1)' => false,
    ],
    'normalizeWebsiteUrl' => [
        'https://arenadoxuxa.com.br' => 'https://arenadoxuxa.com.br',
        'arenadoxuxa.com.br' => 'https://arenadoxuxa.com.br',
        'www.arenadoxuxa.com.br/agenda' => 'https://www.arenadoxuxa.com.br/agenda',
        'HTTP://arenadoxuxa.com.br' => 'https://arenadoxuxa.com.br',
        '' => null,
        'arenadoxuxa' => false,                            // sem domínio
        'javascript:alert(1)' => false,
        'ftp://arenadoxuxa.com.br' => false,
        'mailto:contato@arena.com.br' => false,
        'https://arena doxuxa.com.br' => false,            // espaço
        'https://' . str_repeat('a', 250) . '.com' => false, // passa de 255
    ],
];

foreach ($casos as $metodo => $lista) {
    echo "$metodo\n";
    foreach ($lista as $entrada => $esperado) {
        $obtido = VenueSocials::$metodo((string)$entrada);
        $nome = '"' . (strlen((string)$entrada) > 50 ? substr((string)$entrada, 0, 47) . '...' : $entrada) . '"';
        check($nome, $obtido === $esperado, 'esperado ' . var_export($esperado, true) . ', veio ' . var_export($obtido, true));
    }
}

echo "fromInput\n";
$r = VenueSocials::fromInput(['instagram' => '@arena', 'tiktok' => '', 'facebook_url' => 'facebook.com/arena', 'website_url' => '']);
check('tudo certo grava os quatro', $r['error'] === null && $r['values'] === ['instagram' => 'arena', 'tiktok' => null, 'facebook_url' => 'https://facebook.com/arena', 'website_url' => null], var_export($r, true));
$r = VenueSocials::fromInput(['instagram' => 'ok', 'tiktok' => 'com espaço', 'facebook_url' => 'x']);
check('aponta o primeiro campo inválido', $r['error'] === 'tiktok', var_export($r, true));
$r = VenueSocials::fromInput([]);
check('formulário sem os campos: tudo null', $r['error'] === null && $r['values'] === array_fill_keys(VenueSocials::FIELDS, null));

echo "forApi e links\n";
$api = VenueSocials::forApi(['instagram' => 'arena', 'tiktok' => '', 'facebook_url' => null]);
check('socials sempre com as quatro chaves', array_keys($api) === VenueSocials::FIELDS);
check('vazio e ausente viram null', $api['tiktok'] === null && $api['facebook_url'] === null && $api['website_url'] === null);
$links = VenueSocials::links(['instagram' => 'arena', 'tiktok' => 'arena.tk', 'facebook_url' => 'https://fb.com/a', 'website_url' => 'https://a.com']);
check('links das quatro redes, na ordem', array_column($links, 'network') === ['instagram', 'tiktok', 'facebook', 'website']);
check('link do Instagram', $links[0]['url'] === 'https://instagram.com/arena');
check('link do TikTok com @', $links[1]['url'] === 'https://www.tiktok.com/@arena.tk');
check('sem redes: nenhum link', VenueSocials::links([]) === []);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
