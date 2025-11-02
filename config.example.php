<?php

/**
 * Ficheiro de Configuração de Exemplo para o Kolae
 *
 * Copie este ficheiro para 'config.php' e preencha com as suas
 * credenciais e configurações locais/de produção.
 */

// --- Configurações do Banco de Dados ---

/** @var string Host do banco de dados (ex: 'localhost' ou um IP/hostname) */
define('DB_HOST', ''); // Ou o host do seu DB de produção se for diferente

/** @var string Porta do banco de dados (MySQL/MariaDB padrão é 3306) */
// Lembre-se que no seu XAMPP local mudamos para 3307!
define('DB_PORT', ''); // Use '3307' no seu config.php local

/** @var string Nome do banco de dados */
define('DB_NAME', ''); // Ou o nome do seu DB de produção

/** @var string Nome de utilizador do banco de dados */
define('DB_USER', ''); // Ou o seu utilizador de produção

/** @var string Senha do banco de dados */
define('DB_PASS', ''); // <<-- DEIXE VAZIO OU COLOQUE 'sua_senha_aqui'

// --- Outras Configurações (Exemplos) ---

/** @var bool Ativa/Desativa modo debug (ex: para mostrar mais erros) */
// define('DEBUG_MODE', true); // Use true localmente, false em produção


// --- CONFIGURAÇÕES DE SEGURANÇA (CRIPTOGRAFIA) --- //
/**
 * Chave e Vetor de Inicialização (IV) para criptografia de dados sensíveis (CPF).
 * IMPORTANTE: Devem ser gerados aleatoriamente e mantidos em segredo.
 */
define('ENCRYPTION_KEY', '');
define('ENCRYPTION_IV', '');


// --- CONFIGURAÇÕES DE APIs EXTERNAS --- //
/**
 * Chave da API do Google Maps para as funcionalidades de geocodificação e mapas.
 */
define('GOOGLE_MAPS_API_KEY', '');


/**
 * Chave JWT
 */
define('JWT_SECRET', 'SUA_CHAVE_SECRETA_SUPER_LONGA_E_ALEATORIA_AQUI');


// --- CONFIGURAÇÕES REGIONAIS --- //
/**
 * Define o fuso horário padrão para funções de data e hora da aplicação.
 */
date_default_timezone_set('America/Sao_Paulo');
