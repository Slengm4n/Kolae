// Roda os testes de clique (D56). Antes de cada teste recria os dados de teste no banco
// local (preparar_banco.php) e passa a cada um os ids das quadras de que ele precisa.
// Uso (da raiz do projeto):
//   node tests/e2e/rodar.js              todos
//   node tests/e2e/rodar.js fotos visao  só esses
// Precisa do XAMPP ligado (Apache e MySQL) e de `npm install`. Ver tests/e2e/README.md.
const { execFileSync, spawnSync } = require('child_process');
const path = require('path');
const lib = require('./lib');

// Teste -> quadras que ele recebe (A Arena, B Beach, C Clay, D Duda, E Excluida)
const TESTES = {
    horario: ['A', 'D'],
    agenda: ['A', 'D'],
    dados: ['D', 'E'],
    cadastro: ['A', 'B', 'C', 'D', 'E'],
    painel: ['A', 'B', 'C', 'D', 'E'],
    s5: [],
    fotos: ['A'],
    visao: ['A', 'D'],
    otimizar: ['A', 'B'],
    publica: ['A', 'B', 'C', 'D', 'E'],
    tutorial: ['A', 'D'],
    legal: [],
    quadras: ['A'],
    erros: [],
    entrada: [],
    preferencias: ['A'],
    avisos: ['A'],
    confirmacao: [],
    qr: ['A', 'E'],
    redes: ['D', 'A'],
    plano: ['A'],
};

const pedidos = process.argv.slice(2);
const desconhecidos = pedidos.filter(t => !TESTES[t]);
if (desconhecidos.length) {
    console.error(`Teste desconhecido: ${desconhecidos.join(', ')}. Disponíveis: ${Object.keys(TESTES).join(', ')}`);
    process.exit(1);
}
const lista = pedidos.length ? pedidos : Object.keys(TESTES);

// Fotos de teste (uma vez só)
execFileSync(lib.PHP, [path.join(__dirname, 'gerar_fotos.php')], { stdio: 'ignore' });

const resumo = [];
for (const nome of lista) {
    let ids;
    try {
        ids = JSON.parse(execFileSync(lib.PHP, [path.join(__dirname, 'preparar_banco.php')], { env: { ...process.env, KOLAE_DB: lib.DB } }).toString());
    } catch (e) {
        console.error('Não foi possível preparar o banco de teste. O MySQL do XAMPP está ligado?\n' + (e.stderr || e.message));
        process.exit(1);
    }
    process.stdout.write(`\n== ${nome}\n`);
    const r = spawnSync(process.execPath, [path.join(__dirname, 'testes', nome + '.js'), ...TESTES[nome].map(k => String(ids[k]))], {
        encoding: 'utf8',
        timeout: 5 * 60 * 1000,
    });
    const saida = (r.stdout || '') + (r.stderr || '');
    // Mostra só falhas e erros; o resumo de cada teste fica na tabela do fim
    saida.split('\n').filter(l => /^FALHA|^\w*Error\b/.test(l)).forEach(l => console.log('   ' + l));
    const ok = (saida.match(/^OK/gm) || []).length;
    const falhas = (saida.match(/^FALHA/gm) || []).length;
    const quebrou = r.status !== 0 && falhas === 0;
    resumo.push({ nome, ok, falhas, quebrou });
    console.log(`   ${ok} ok, ${falhas} falha(s)${quebrou ? ', o teste parou com erro' : ''}`);
}

console.log('\n' + '='.repeat(40));
for (const t of resumo) {
    console.log(`${t.falhas || t.quebrou ? 'FALHOU' : 'ok    '}  ${t.nome.padEnd(10)} ${String(t.ok).padStart(3)} ok${t.falhas ? `, ${t.falhas} falha(s)` : ''}${t.quebrou ? ', parou com erro' : ''}`);
}
const total = resumo.reduce((n, t) => n + t.ok, 0);
const ruins = resumo.filter(t => t.falhas || t.quebrou).length;
console.log('='.repeat(40));
console.log(ruins ? `${ruins} teste(s) com problema` : `TUDO OK: ${resumo.length} testes, ${total} passos`);
console.log(`Capturas de tela em ${lib.OUT}`);
process.exit(ruins ? 1 : 0);
