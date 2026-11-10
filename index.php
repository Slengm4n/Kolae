<?php


use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\LegalController;
use App\Controllers\PublicVenueController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\VenueController;
use App\Controllers\VenueHoursController;
use App\Controllers\VenueAgendaController;
use App\Controllers\VenuePhotosController;
use App\Controllers\VenueOverviewController;
use App\Controllers\PremiumController;
use App\Controllers\VenueStatsController;
use App\Controllers\VenueAdController;
use App\Controllers\SportController;
use App\Controllers\AdminController;
use App\Controllers\AdminAdController;
use App\Controllers\AuthApiController;
use App\Controllers\MeApiController;
use App\Controllers\VenueApiController;
use App\Controllers\ChatApiController;
use App\Controllers\RequestApiController;
use App\Controllers\ChatController;
use App\Controllers\SportApiController;
use App\Core\AuthHelper;




// --- CONFIGURAÇÃO DE SEGURANÇA DA SESSÃO (BLINDAGEM) ---

// Verifica se está rodando em HTTPS (Produção) ou HTTP (Localhost)
// Detecta HTTPS mesmo atrás de Proxy (Load Balancer)
$isSecure = false;
if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
    $isSecure = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
    $isSecure = true;
} elseif (isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') {
    $isSecure = true;
}

// Host sem a porta (ex: "localhost:8080" -> "localhost"); cookie não aceita porta no domínio
$hostName = explode(':', $_SERVER['HTTP_HOST'])[0];

$cookieParams = [
    'lifetime' => 86400,
    'path' => '/',
    'domain' => $hostName,
    'secure' => $isSecure, // Usa a detecção inteligente
    'httponly' => true,
    'samesite' => 'Lax'
];

session_set_cookie_params($cookieParams);
session_start();

if (!isset($_SESSION['idioma'])) {
    // Tenta pegar do navegador ou define padrão
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $_SESSION['idioma'] = ($browserLang === 'pt') ? 'pt-br' : 'en-us';
}

// --- Constantes Globais ---
define('BASE_PATH', __DIR__);


/* --- Definição Dinâmica da BASE_URL --- */

// Detecta o protocolo (http ou https)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";

// Detecta o host (o domínio, ex: "localhost" ou "colae.42web.io")
$host = $_SERVER['HTTP_HOST'];

// Local = "localhost" ou IP de rede privada/loopback (ex: 192.168.0.10, 127.0.0.1).
// O IP é o que o app no celular usa para falar com o XAMPP na mesma rede Wi-Fi (docs/setup.md).
$isLocalIp = filter_var($hostName, FILTER_VALIDATE_IP) !== false
    && filter_var($hostName, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

if ($hostName == 'localhost' || $isLocalIp) {
    // --- AMBIENTE LOCAL (SEU PC) --- (qualquer porta: XAMPP na 80 ou 8080)
    define('BASE_URL', 'http://' . $host . '/kolae');
    define('BASE_DIR_URL', '/kolae');
} else {
    // --- AMBIENTE DE PRODUÇÃO ---
    define('BASE_URL', $protocol . '://' . $host);
    define('BASE_DIR_URL', '');
}

/* -------------------------------------- */


// --- Autoloader do Composer ---
require_once BASE_PATH . '/vendor/autoload.php';

// --- Configurações Personalizadas ---
require_once BASE_PATH . '/config.php';

// --- API: qualquer erro do PHP sai como JSON (contrato em docs/api.md) ---
if (\App\Core\ApiResponse::isApiRequest()) {
    \App\Core\ApiResponse::registerErrorHandlers();
}


// --- INÍCIO GLOBAL DE CORS ---
/**
 * Manipulador Global de Preflight (OPTIONS)
 *
 * Intercepta requisições 'OPTIONS' do navegador e envia os cabeçalhos
 * de permissão CORS antes que o router tente processar a rota.
 */
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    // Permite qualquer origem para teste. Em produção, troque '*' pela URL do seu protótipo.
    // Ex: header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
    header('Access-Control-Allow-Origin: *');

    // Métodos permitidos
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

    // Cabeçalhos permitidos (IMPORTANTE incluir 'Authorization')
    header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');

    header('Access-Control-Max-Age: 86400'); // Cache de 1 dia

    http_response_code(200); // Responde OK para a sondagem

    exit; // Termina a execução. Não precisa do router.
}

AuthHelper::checkRememberMe();

// Formulários do site precisam do token CSRF (a API usa o token do cabeçalho Authorization)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !\App\Core\ApiResponse::isApiRequest()) {
    \App\Core\Csrf::verifyOrFail();
}
// --- Instância do Roteador ---
$router = new Router();


// --- ROTAS PÚBLICAS ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/termos', [LegalController::class, 'terms']);
$router->get('/privacidade', [LegalController::class, 'privacy']);
$router->get('/quadras', [PublicVenueController::class, 'index']);
$router->get('/quadra/{id}', [PublicVenueController::class, 'show']);
$router->get('/login', [AuthController::class, 'index']);
$router->post('/login/authenticate', [AuthController::class, 'authenticate']);
$router->get('/register', [AuthController::class, 'register']);
$router->post('/register/store', [AuthController::class, 'store']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'handleForgotPassword']);
$router->get('/reset-password', [AuthController::class, 'showResetForm']);
$router->get('/verificar-email', [AuthController::class, 'verifyEmail']);
$router->post('/reset-password', [AuthController::class, 'handleResetPassword']);

// --- ROTAS DO PAINEL DO UTILIZADOR ---
$router->group('/dashboard', function ($router) {
    $router->get('/', [UserController::class, 'dashboard']);
    $router->get('/cnpj', [UserController::class, 'addCnpj']);
    $router->post('/cnpj', [UserController::class, 'storeCnpj']);
    $router->get('/perfil', [UserController::class, 'profile']);
    $router->post('/perfil/atualizar', [UserController::class, 'updateProfile']);
    $router->get('/perfil/seguranca', [UserController::class, 'showSecurityPage']);
    $router->post('/perfil/seguranca/atualizar', [UserController::class, 'updatePasswordFromProfile']);
    $router->get('/perfil/preferencias', [UserController::class, 'preferences']);
    // Kolae Premium do dono (D61)
    $router->get('/perfil/plano', [PremiumController::class, 'show']);
    $router->post('/perfil/plano/pedir', [PremiumController::class, 'request']);
    $router->post('/verificar-email/reenviar', [AuthController::class, 'resendVerification']);

    // Mensagens do chat (D66): o dono responde quem pergunta pelo app
    $router->get('/mensagens', [ChatController::class, 'index']);
    $router->get('/mensagens/{id}', [ChatController::class, 'show']);
    $router->post('/mensagens/{id}', [ChatController::class, 'reply']);
    $router->get('/mensagens/{id}/novas', [ChatController::class, 'poll']);
    $router->post('/mensagens/{id}/bloquear', [ChatController::class, 'block']);
    $router->post('/mensagens/{id}/desbloquear', [ChatController::class, 'unblock']);

    // --- ROTAS DE QUADRAS DO UTILIZADOR ---
    $router->group('/quadras', function ($router) {
        $router->get('/criar', [VenueController::class, 'create']);
        $router->post('/salvar', [VenueController::class, 'store']);
        $router->get('/editar/{id}', [VenueController::class, 'edit']);
        $router->post('/atualizar/{id}', [VenueController::class, 'update']);
        $router->post('/excluir/{id}', [VenueController::class, 'delete']);
        // Abas da quadra (D35)
        $router->get('/{id}/horario', [VenueHoursController::class, 'show']);
        $router->post('/{id}/horario', [VenueHoursController::class, 'save']);
        $router->get('/{id}/agenda', [VenueAgendaController::class, 'show']);
        $router->post('/{id}/agenda', [VenueAgendaController::class, 'schedule']);
        $router->post('/{id}/agenda/cancelar/{matchId}', [VenueAgendaController::class, 'cancel']);
        $router->get('/{id}/visao-geral', [VenueOverviewController::class, 'show']);
        // Aba Estatísticas (D62)
        $router->get('/{id}/estatisticas', [VenueStatsController::class, 'show']);
        // Aba Anúncio (D63)
        $router->get('/{id}/anuncio', [VenueAdController::class, 'show']);
        $router->post('/{id}/anuncio', [VenueAdController::class, 'save']);
        $router->post('/{id}/anuncio/{adId}/{action}', [VenueAdController::class, 'action']);
        $router->get('/{id}/fotos', [VenuePhotosController::class, 'show']);
        $router->post('/{id}/fotos', [VenuePhotosController::class, 'upload']);
        $router->post('/{id}/fotos/{imageId}/capa', [VenuePhotosController::class, 'cover']);
        $router->post('/{id}/fotos/{imageId}/mover', [VenuePhotosController::class, 'move']);
        $router->post('/{id}/fotos/{imageId}/excluir', [VenuePhotosController::class, 'remove']);
    });
});


// --- ROTAS DO PAINEL DE ADMIN ---
$router->group('/admin', function ($router) {
    $router->get('/', [AdminController::class, 'dashboard']);
    $router->get('/mapa', [AdminController::class, 'showMap']);
    $router->post('/fotos/otimizar', [AdminController::class, 'optimizePhotos']);
    // Kolae Premium (D61): o admin ativa e desativa por dono
    $router->post('/premium/{id}/ativar', [PremiumController::class, 'activate']);
    $router->post('/premium/{id}/desativar', [PremiumController::class, 'deactivate']);
    // Anúncios (D63): o admin remove com motivo, no card "Anúncios" do painel
    $router->post('/anuncios/{id}/remover', [AdminAdController::class, 'remove']);

    // Gerenciamento de Usuarios
    $router->group('/usuarios', function ($router) {
        $router->get('/', [UserController::class, 'index']);
        $router->get('/criar', [UserController::class, 'create']);
        $router->post('/salvar', [UserController::class, 'store']);
        $router->get('/editar/{id}', [UserController::class, 'edit']);
        $router->post('/atualizar', [UserController::class, 'update']);
        $router->post('/excluir/{id}', [UserController::class, 'delete']);
    });

    // Gerenciamento de eesportos
    $router->group('/esportes', function ($router) {
        $router->get('/', [SportController::class, 'index']);
        $router->get('/criar', [SportController::class, 'create']);
        $router->post('/salvar', [SportController::class, 'store']);
        $router->get('/editar/{id}', [SportController::class, 'edit']);
        $router->post('/atualizar', [SportController::class, 'update']);
        $router->post('/excluir/{id}', [SportController::class, 'delete']);
    });

    // --- ROTAS DE QUADRAS DO UTILIZADOR ---
    $router->group('/quadras', function ($router) {
        $router->get('/', [VenueController::class, 'index']);
        $router->get('/criar', [VenueController::class, 'create']);
        $router->post('/salvar', [VenueController::class, 'store']);
        $router->get('/editar/{id}', [VenueController::class, 'edit']);
        $router->post('/atualizar/{id}', [VenueController::class, 'update']);
        $router->post('/excluir/{id}', [VenueController::class, 'delete']);
        // Abas da quadra (D35)
        $router->get('/{id}/horario', [VenueHoursController::class, 'show']);
        $router->post('/{id}/horario', [VenueHoursController::class, 'save']);
        $router->get('/{id}/agenda', [VenueAgendaController::class, 'show']);
        $router->post('/{id}/agenda', [VenueAgendaController::class, 'schedule']);
        $router->post('/{id}/agenda/cancelar/{matchId}', [VenueAgendaController::class, 'cancel']);
        $router->get('/{id}/visao-geral', [VenueOverviewController::class, 'show']);
        // Aba Estatísticas (D62)
        $router->get('/{id}/estatisticas', [VenueStatsController::class, 'show']);
        // Aba Anúncio (D63)
        $router->get('/{id}/anuncio', [VenueAdController::class, 'show']);
        $router->post('/{id}/anuncio', [VenueAdController::class, 'save']);
        $router->post('/{id}/anuncio/{adId}/{action}', [VenueAdController::class, 'action']);
        $router->get('/{id}/fotos', [VenuePhotosController::class, 'show']);
        $router->post('/{id}/fotos', [VenuePhotosController::class, 'upload']);
        $router->post('/{id}/fotos/{imageId}/capa', [VenuePhotosController::class, 'cover']);
        $router->post('/{id}/fotos/{imageId}/mover', [VenuePhotosController::class, 'move']);
        $router->post('/{id}/fotos/{imageId}/excluir', [VenuePhotosController::class, 'remove']);
    });
});

// --- ROTAS DA API V1 (contrato em docs/api.md) ---
$router->group('/api/v1', function ($router) {
    // Autenticação (públicas)
    $router->post('/auth/login', [AuthApiController::class, 'login']);
    $router->post('/auth/register', [AuthApiController::class, 'register']);
    $router->post('/auth/forgot-password', [AuthApiController::class, 'forgotPassword']);

    // Pessoa logada (exigem token)
    $router->get('/me', [MeApiController::class, 'show']);
    $router->patch('/me', [MeApiController::class, 'update']);
    $router->post('/me/avatar', [MeApiController::class, 'avatar']);
    $router->put('/me/password', [MeApiController::class, 'password']);

    // Quadras (públicas): lista, detalhe e agenda do dia
    $router->get('/venues', [VenueApiController::class, 'index']);
    $router->get('/venues/{id}', [VenueApiController::class, 'show']);
    $router->post('/venues/{id}/events', [VenueApiController::class, 'event']); // estatísticas (D62)
    $router->get('/venues/{id}/schedule', [VenueApiController::class, 'schedule']);
    $router->get('/ads', [VenueApiController::class, 'ads']); // carrossel "Destaques" (D63)

    // Chat (D66, exigem token): o dono responde pelo painel do site
    $router->get('/conversations', [ChatApiController::class, 'index']);
    $router->post('/venues/{id}/conversations', [ChatApiController::class, 'open']);
    $router->get('/conversations/{id}/messages', [ChatApiController::class, 'messages']);
    $router->post('/conversations/{id}/messages', [ChatApiController::class, 'send']);
    $router->post('/conversations/{id}/read', [ChatApiController::class, 'read']);

    // Pedidos de horário e notificações (D64, exigem token): o dono responde pelo painel do site
    $router->post('/venues/{id}/requests', [RequestApiController::class, 'store']);
    $router->get('/me/requests', [RequestApiController::class, 'index']);
    $router->delete('/me/requests/{id}', [RequestApiController::class, 'cancel']);
    $router->get('/me/notifications', [RequestApiController::class, 'notifications']);
    $router->post('/me/notifications/read', [RequestApiController::class, 'readNotifications']);

    // Esportes
    $router->get('/sports', [SportApiController::class, 'getActiveSports']);
});


// Executa o roteador
$router->dispatch();
