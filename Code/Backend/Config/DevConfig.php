<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class DevConfig
{
    private static ?DevConfig $instance = null;

    private string $mysqlHost;
    private int $mysqlPort;
    private string $mysqlDatabase;
    private string $mysqlUsername;
    private string $mysqlPassword;

    private string $mongoUri;
    private string $mongoDatabase;

    private string $redisHost;
    private int $redisPort;
    private string $redisPassword;
    private bool $redisTls;

    private string $clientUrl;

    private function __construct()
    {
        /*
         * Local development:
         * Loads values from .env if the file exists.
         *
         * Production (Render):
         * .env does not exist, so safeLoad() does not throw an error.
         * Environment variables provided by Render remain available.
         */
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();

        $this->mysqlHost = $_ENV['MYSQL_HOST'] ?? '';
        $this->mysqlPort = (int) ($_ENV['MYSQL_PORT'] ?? 3306);
        $this->mysqlDatabase = $_ENV['MYSQL_DATABASE'] ?? '';
        $this->mysqlUsername = $_ENV['MYSQL_USERNAME'] ?? '';
        $this->mysqlPassword = $_ENV['MYSQL_PASSWORD'] ?? '';

        $this->mongoUri = $_ENV['MONGO_URI'] ?? '';
        $this->mongoDatabase = $_ENV['MONGO_DATABASE'] ?? '';

        $this->redisHost = $_ENV['REDIS_HOST'] ?? '';
        $this->redisPort = (int) ($_ENV['REDIS_PORT'] ?? 6379);
        $this->redisPassword = $_ENV['REDIS_PASSWORD'] ?? '';
        $this->redisTls = filter_var(
            $_ENV['REDIS_TLS'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        $this->clientUrl = $_ENV['CLIENT_URL'] ?? '';
    }

    public static function getInstance(): DevConfig
    {
        if (self::$instance === null) {
            self::$instance = new DevConfig();
        }

        return self::$instance;
    }

    public function getMysqlHost(): string
    {
        return $this->mysqlHost;
    }

    public function getMysqlPort(): int
    {
        return $this->mysqlPort;
    }

    public function getMysqlDatabase(): string
    {
        return $this->mysqlDatabase;
    }

    public function getMysqlUsername(): string
    {
        return $this->mysqlUsername;
    }

    public function getMysqlPassword(): string
    {
        return $this->mysqlPassword;
    }

    public function getMongoUri(): string
    {
        return $this->mongoUri;
    }

    public function getMongoDatabase(): string
    {
        return $this->mongoDatabase;
    }

    public function getRedisHost(): string
    {
        return $this->redisHost;
    }

    public function getRedisPort(): int
    {
        return $this->redisPort;
    }

    public function getRedisPassword(): string
    {
        return $this->redisPassword;
    }

    public function getRedisTls(): bool
    {
        return $this->redisTls;
    }

    public function getClientUrl(): string
    {
        return $this->clientUrl;
    }
}