<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/Redis.php';

class RateLimiter
{
    private Redis $redis;

    public function __construct()
    {
        $redisConnection = new RedisConnection();

        $this->redis = $redisConnection->getConnection();
    }

    public function attempt(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): bool {
        $attempts = $this->redis->incr($key);

        if ($attempts === 1) {
            $this->redis->expire(
                $key,
                $windowSeconds
            );
        }

        return $attempts <= $maxAttempts;
    }

    public function getRetryAfter(
        string $key
    ): int {
        $ttl = $this->redis->ttl($key);

        return $ttl > 0
            ? $ttl
            : 0;
    }

    public function reset(
        string $key
    ): void {
        $this->redis->del($key);
    }
}