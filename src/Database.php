<?php

namespace App;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database {
    /** @var Database A única instância da classe */
    private static ?Database $instance = null;

    /** @var PDO O objeto de conexão PDO */
    private PDO $connection;

    private function __construct() {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];

        $dsn = 'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4';

        try {
            $this->connection = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Erro de Conexão com o Banco de Dados: " . $e->getMessage());
        }
    }

    private function __clone() {
    }

    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Retorna a única instância da classe Database (Singleton).
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna o objeto PDO para realizar queries.
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
}
