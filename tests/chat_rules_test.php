<?php
// Testa as regras do chat (App\Core\ChatRules, D66): texto da mensagem (limpeza, tamanho, links),
// parâmetros da lista de mensagens, limite de envio e "última lida". Não usa o banco.
// Rodar da raiz do projeto:
//   php tests/chat_rules_test.php

require __DIR__ . '/../vendor/autoload.php';

use App\Core\ChatRules;

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
$body = fn($s) => ChatRules::cleanBody($s);

echo "cleanBody: texto\n";
check('mensagem comum', $body('Tem horário sábado de manhã?') === ['Tem horário sábado de manhã?', null]);
check('tira espaços das pontas', $body("   Oi!  \n ") === ['Oi!', null]);
check('mantém quebras de linha', $body("Oi\nSomos 10\nFutebol") === ["Oi\nSomos 10\nFutebol", null]);
check('quebra do Windows vira \\n', $body("Oi\r\nTudo bem?") === ["Oi\nTudo bem?", null]);
check('tira espaços no fim das linhas', $body("Oi   \nTudo") === ["Oi\nTudo", null]);
check('corta linhas em branco demais (fica no máximo 2)', $body("Oi\n\n\n\n\n\nTchau") === ["Oi\n\n\nTchau", null], var_export($body("Oi\n\n\n\n\n\nTchau"), true));
check('vazio', $body('') === [null, 'required']);
check('só espaços e quebras', $body(" \n\t \n ") === [null, 'required']);
check('não é texto', $body(null) === [null, 'required'] && $body(['oi']) === [null, 'required'] && $body(42) === [null, 'required']);
check('1000 caracteres passa (com acento)', $body(str_repeat('é', 1000))[1] === null);
check('1001 não passa', $body(str_repeat('é', 1001)) === [null, 'too_long']);
check('emoji conta como 1', $body(str_repeat('⚽', 1000))[1] === null);

echo "cleanBody: links (D66: só texto, sem links)\n";
foreach ([
    'http://exemplo.com' => 'http',
    'Veja https://kolae.online/quadra/3' => 'https',
    'HTTPS://GOLPE.COM' => 'maiúsculas',
    'acessa www.quadras' => 'www.',
    'WWW.algo' => 'WWW.',
    'entra em kolae.online' => 'domínio .online',
    'bit.ly/abc123' => 'encurtador',
    'me chama no wa.me/5511999999999' => 'wa.me',
    'site.com.br' => '.com.br',
    'promo em quadra-top.net hoje' => 'com hífen',
    'manda pra joao@gmail.com' => 'e-mail (dado pessoal, fica de fora também)',
    'golpe.xyz!' => 'pontuação depois',
] as $texto => $caso) {
    check("bloqueia: $caso", $body($texto) === [null, 'link'], $texto);
}
foreach ([
    'Custa R$ 50.00 a hora' => 'preço com ponto',
    'Começa 19.30 e vai até 21.00' => 'horário com ponto',
    'Dia 10.11 pode?' => 'data com ponto',
    'Pode ser.De tarde?' => 'espaço esquecido antes de "De"',
    'Tá bom.Com certeza vou' => 'espaço esquecido antes de "Com"',
    'Fechado.Me avisa' => 'espaço esquecido antes de "Me"',
    'ok.to chegando' => '.to (tô) não é domínio da lista',
    'vamos.de novo' => '.de não é domínio da lista',
    'site.combo' => 'letra logo depois do domínio',
    'Somos 10, futebol society. Tem colete?' => 'frase normal com ponto',
    'Ganhei 3.5 de desconto...' => 'número decimal e reticências',
] as $texto => $caso) {
    check("aceita: $caso", $body($texto)[1] === null, $texto . ' -> ' . var_export($body($texto), true));
}

echo "listParams\n";
$p = ChatRules::listParams([]);
check('sem nada: as 30 mais recentes', $p === ['after' => null, 'before' => null, 'per_page' => 30, 'errors' => []]);
check('after_id', ChatRules::listParams(['after_id' => '340'])['after'] === 340);
check('before_id e per_page', (fn($r) => $r['before'] === 12 && $r['per_page'] === 50 && !$r['errors'])(ChatRules::listParams(['before_id' => '12', 'per_page' => '50'])));
check('vazios contam como ausentes', ChatRules::listParams(['after_id' => '', 'per_page' => ''])['errors'] === []);
check('after_id e before_id juntos: erro', isset(ChatRules::listParams(['after_id' => '1', 'before_id' => '9'])['errors']['after_id']));
foreach (['0', '-3', 'abc', '1.5', ' 7'] as $ruim) {
    check("after_id inválido ($ruim)", isset(ChatRules::listParams(['after_id' => $ruim])['errors']['after_id']));
}
check('after_id como lista: erro', isset(ChatRules::listParams(['after_id' => ['1']])['errors']['after_id']));
check('per_page 51: erro', isset(ChatRules::listParams(['per_page' => '51'])['errors']['per_page']));
check('per_page 0: erro', isset(ChatRules::listParams(['per_page' => '0'])['errors']['per_page']));
check('per_page 1 passa', ChatRules::listParams(['per_page' => '1'])['per_page'] === 1);

echo "retryAfter\n";
check('falta o resto da janela', ChatRules::retryAfter(1000, 1015) === 45);
check('nunca menos de 1 segundo', ChatRules::retryAfter(1000, 1060) === 1 && ChatRules::retryAfter(1000, 2000) === 1);

echo "nextReadPointer (só avança)\n";
check('primeira leitura', ChatRules::nextReadPointer(null, 10, 12) === 10);
check('avança', ChatRules::nextReadPointer(5, 10, 12) === 10);
check('não volta', ChatRules::nextReadPointer(10, 5, 12) === 10);
check('id maior que a última vale como a última', ChatRules::nextReadPointer(5, 999, 12) === 12);
check('conversa vazia não muda', ChatRules::nextReadPointer(null, 10, null) === null);

echo "\nRESULTADO: $ok ok, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
