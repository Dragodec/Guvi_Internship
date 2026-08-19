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

            $this->ensureUsersTable();

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

    private function ensureUsersTable(): void
    {
        $statement = $this->connection->prepare(
            "CREATE TABLE IF NOT EXISTS users (
                user_id CHAR(36) NOT NULL,
                email VARCHAR(254) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_suspended BOOLEAN NOT NULL DEFAULT FALSE,

                PRIMARY KEY (user_id),

                UNIQUE (email),

                CONSTRAINT chk_email_format
                    CHECK (
                        email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\\\.[A-Za-z]{2,}$'
                    )
            )"
        );

        $statement->execute();
    }
}