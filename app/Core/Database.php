<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Class Database
 * Gerencia a conexão Singleton com o banco de dados usando PDO.
 */
class Database
{
    /**
     * @var PDO|null A instância única da conexão PDO.
     */
    private static $pdo;

    /**
     * Obtém a conexão com o banco de dados.
     * Se a conexão ainda não existir, ela é criada.
     * @return PDO A instância da conexão PDO.
     */
    public static function getConnection()
    {
        if (!isset(self::$pdo)) {
            try {
                // As constantes DB_HOST, DB_NAME, etc., são carregadas no index.php
                self::$pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS
                );

                // Define os atributos do PDO para um tratamento de erros robusto
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // FETCH_ASSOC é geralmente mais prático que FETCH_OBJ
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // Fuso da sessão = horário de Brasília (o mesmo do PHP no config.php).
                // Sem isso, a produção lia TIMESTAMP e NOW() em UTC e o local em -03:00.
                // Offset fixo porque o InfinityFree pode não ter as tabelas de fuso nomeado
                // (o Brasil não tem horário de verão desde 2019).
                self::$pdo->exec("SET time_zone = '-03:00'");
            } catch (PDOException $e) {
                // Não expõe host/usuário do banco na tela; o detalhe vai para o log
                error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
                if (ApiResponse::isApiRequest()) {
                    ApiResponse::error('Serviço indisponível no momento.', 'DATABASE_UNAVAILABLE', 503);
                }
                http_response_code(503);
                die("Erro de conexão com o banco de dados. Tente novamente em instantes.");
            }
        }

        return self::$pdo;
    }
}
