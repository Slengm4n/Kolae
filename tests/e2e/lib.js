// Configuração comum dos testes de clique (D56). Tudo tem padrão para o XAMPP do projeto
// e pode ser trocado por variável de ambiente, para rodar em outro PC:
//   KOLAE_URL      endereço do site local        (padrão http://localhost/kolae)
//   KOLAE_BROWSER  Chrome ou Edge instalado      (padrão: o primeiro que existir)
//   KOLAE_PHP      php.exe                       (padrão C:/xampp/php/php.exe ou o do PATH)
//   KOLAE_MYSQL    mysql.exe                     (padrão C:/xampp/mysql/bin/mysql.exe ou o do PATH)
//   KOLAE_DB       banco local                   (padrão kolae_db)
// Nunca apontar para a produção: preparar_banco.php recusa banco que não seja local.
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const firstExisting = (list, fallback) => list.find(p => p && fs.existsSync(p)) || fallback;
const xampp = [process.env.XAMPP_DIR, 'C:/xampp', 'F:/Programas/Xampp', 'D:/xampp'].filter(Boolean);

const BASE = (process.env.KOLAE_URL || 'http://localhost/kolae').replace(/\/$/, '');
const ROOT = path.resolve(__dirname, '..', '..');

const lib = {
    BASE,
    BASE_PATH: new URL(BASE).pathname.replace(/\/$/, ''),
    ROOT,
    UPLOADS: path.join(ROOT, 'uploads'),
    DB: process.env.KOLAE_DB || 'kolae_db',
    BROWSER: process.env.KOLAE_BROWSER || firstExisting([
        'C:/Program Files/Google/Chrome/Application/chrome.exe',
        'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
        'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
        'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
        '/usr/bin/google-chrome', '/usr/bin/chromium',
    ], 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe'),
    PHP: process.env.KOLAE_PHP || firstExisting(xampp.map(d => d + '/php/php.exe'), 'php'),
    MYSQL: process.env.KOLAE_MYSQL || firstExisting(xampp.map(d => d + '/mysql/bin/mysql.exe'), 'mysql'),
    FOTOS: path.join(__dirname, '.fotos'),
    OUT: path.join(__dirname, '.capturas'),
};

// Consulta no banco local; devolve o texto da resposta sem o \r do Windows
lib.sql = q => execFileSync(lib.MYSQL, ['--default-character-set=utf8mb4', '-uroot', '-N', lib.DB, '-e', q])
    .toString().split(String.fromCharCode(13)).join('').trim();

fs.mkdirSync(lib.OUT, { recursive: true });
module.exports = lib;
