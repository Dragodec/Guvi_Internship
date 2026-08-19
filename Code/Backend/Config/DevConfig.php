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
    private ?string $redisPassword;
    private bool $redisTls;

    private string $clientUrl;

    private int $sessionTtl;

    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $this->mysqlHost = $_ENV['MYSQL_HOST'];
        $this->mysqlPort = (int) $_ENV['MYSQL_PORT'];
        $this->mysqlDatabase = $_ENV['MYSQL_DATABASE'];
        $this->mysqlUsername = $_ENV['MYSQL_USERNAME'];
        $this->mysqlPassword = $_ENV['MYSQL_PASSWORD'];

        $this->mongoUri = $_ENV['MONGO_URI'];
        $this->mongoDatabase = $_ENV['MONGO_DATABASE'];

        $this->redisHost = $_ENV['REDIS_HOST'];
        $this->redisPort = (int) $_ENV['REDIS_PORT'];
        $this->redisPassword = !empty($_ENV['REDIS_PASSWORD'])
            ? $_ENV['REDIS_PASSWORD']
            : null;

        $this->redisTls = filter_var(
            $_ENV['REDIS_TLS'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        $this->clientUrl = $_ENV['CLIENT_URL'];

        $sessionTtl = filter_var(
            $_ENV['SESSION_TTL'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($sessionTtl === false || $sessionTtl <= 0) {
            throw new RuntimeException(
                'SESSION_TTL must be a positive integer.'
            );
        }

        $this->sessionTtl = $sessionTtl;
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

    public function getRedisPassword(): ?string
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

    public function getSessionTtl(): int
    {
        return $this->sessionTtl;
    }
}