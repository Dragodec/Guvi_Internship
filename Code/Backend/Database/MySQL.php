<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';

class MySQL
{
    private PDO $connection;

    public function __construct()
    {
        $config = DevConfig::getInstance();

        $host = $config->getMysqlHost();
        $port = $config->getMysqlPort();
        $database = $config->getMysqlDatabase();
        $username = $config->getMysqlUsername();
        $password = $config->getMysqlPassword();

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        try {
            $this->connection = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'MySQL connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
