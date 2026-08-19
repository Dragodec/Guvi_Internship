<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';

class RedisConnection
{
    private Redis $connection;

    private const SESSION_PREFIX = 'session:';

    private int $sessionTtl;

    public function __construct()
    {
        $config = DevConfig::getInstance();

        $host = $config->getRedisHost();
        $port = $config->getRedisPort();
        $password = $config->getRedisPassword();

        $this->sessionTtl = $config->getSessionTtl();

        try {
            $this->connection = new Redis();

            $this->connection->connect(
                $host,
                $port
            );

            if ($password !== null) {
                $this->connection->auth(
                    $password
                );
            }

            if ($this->connection->ping() !== true) {
                throw new RuntimeException(
                    'Redis server did not respond to PING.'
                );
            }

        } catch (Throwable $e) {
            throw new RuntimeException(
                'Redis connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getConnection(): Redis
    {
        return $this->connection;
    }

    public function createSession(
        string $sessionId,
        string $userId,
        string $createdAt,
        string $lastActivity
    ): bool {
        $key = self::SESSION_PREFIX . $sessionId;

        $result = $this->connection->hMSet($key, [
            'user_id' => $userId,
            'created_at' => $createdAt,
            'last_activity' => $lastActivity
        ]);

        if ($result !== true) {
            return false;
        }

        return $this->connection->expire(
            $key,
            $this->sessionTtl
        );
    }

    public function getSession(
        string $sessionId
    ): ?array {
        $key = self::SESSION_PREFIX . $sessionId;

        $session = $this->connection->hGetAll(
            $key
        );

        if (empty($session)) {
            return null;
        }

        return $session;
    }

    public function refreshSession(
        string $sessionId,
        string $lastActivity
    ): bool {
        $key = self::SESSION_PREFIX . $sessionId;

        if (!$this->connection->exists($key)) {
            return false;
        }

        $updated = $this->connection->hSet(
            $key,
            'last_activity',
            $lastActivity
        );

        if ($updated === false) {
            return false;
        }

        return $this->connection->expire(
            $key,
            $this->sessionTtl
        );
    }

    public function deleteSession(
        string $sessionId
    ): bool {
        $key = self::SESSION_PREFIX . $sessionId;

        return $this->connection->del($key) > 0;
    }

    public function getSessionTtl(
        string $sessionId
    ): int {
        $key = self::SESSION_PREFIX . $sessionId;

        return $this->connection->ttl($key);
    }
}