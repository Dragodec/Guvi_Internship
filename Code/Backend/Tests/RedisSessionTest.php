<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';
require_once __DIR__ . '/../vendor/autoload.php';

$config = DevConfig::getInstance();

echo "=== Redis Login Session Test ===" . PHP_EOL . PHP_EOL;

$sessionToken = trim((string) readline(
    'Enter session token from localStorage: '
));

if ($sessionToken === '') {
    echo "[FAIL] Session token cannot be empty." . PHP_EOL;
    exit(1);
}

$redis = new Redis();

try {
    $connected = $redis->connect(
        $config->getRedisHost(),
        $config->getRedisPort(),
        5.0,
        null,
        0,
        0,
        [
            'stream' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]
    );

    if (!$connected) {
        throw new RuntimeException('Redis connection failed.');
    }

    $redisPassword = $config->getRedisPassword();

    if ($redisPassword !== null && $redisPassword !== '') {
        if (!$redis->auth($redisPassword)) {
            throw new RuntimeException(
                'Redis authentication failed.'
            );
        }
    }

    echo "[PASS] Redis connection successful." . PHP_EOL;

    $sessionHash = hash('sha256', $sessionToken);
    $sessionKey = 'session:' . $sessionHash;

    echo "[PASS] Session token hashed using SHA-256." . PHP_EOL;
    echo "Redis session key: {$sessionKey}" . PHP_EOL . PHP_EOL;

    if (!$redis->exists($sessionKey)) {
        echo "[FAIL] Redis session does not exist." . PHP_EOL;
        exit(1);
    }

    echo "[PASS] Redis session exists." . PHP_EOL;

    $sessionData = $redis->hGetAll($sessionKey);

    if (empty($sessionData)) {
        echo "[FAIL] Redis session hash is empty." . PHP_EOL;
        exit(1);
    }

    echo "[PASS] Redis session contains data." . PHP_EOL . PHP_EOL;

    $requiredFields = [
        'user_id',
        'created_at',
        'last_activity'
    ];

    foreach ($requiredFields as $field) {
        if (
            !array_key_exists($field, $sessionData)
            || $sessionData[$field] === ''
        ) {
            echo "[FAIL] Missing Redis field: {$field}" . PHP_EOL;
            exit(1);
        }

        echo "[PASS] {$field}: {$sessionData[$field]}" . PHP_EOL;
    }

    echo PHP_EOL;

    $ttl = $redis->ttl($sessionKey);

    if ($ttl === -2) {
        echo "[FAIL] Redis session key does not exist." . PHP_EOL;
        exit(1);
    }

    if ($ttl === -1) {
        echo "[FAIL] Redis session has no expiration TTL." . PHP_EOL;
        exit(1);
    }

    if ($ttl <= 0) {
        echo "[FAIL] Redis session TTL is invalid." . PHP_EOL;
        exit(1);
    }

    echo "[PASS] Redis TTL is active: {$ttl} seconds." . PHP_EOL;

    if ($sessionKey === 'session:' . $sessionToken) {
        echo "[FAIL] Raw session token is being used as Redis key." . PHP_EOL;
        exit(1);
    }

    echo "[PASS] Raw session token is not used as Redis key." . PHP_EOL;

    $expectedHash = hash('sha256', $sessionToken);

    if ($sessionHash !== $expectedHash) {
        echo "[FAIL] Session token hash verification failed." . PHP_EOL;
        exit(1);
    }

    echo "[PASS] Session token resolves to expected SHA-256 Redis key." . PHP_EOL;

    echo PHP_EOL;
    echo "=== Redis Session Verification Successful ===" . PHP_EOL;
    echo PHP_EOL;

    echo "User ID:        {$sessionData['user_id']}" . PHP_EOL;
    echo "Created At:     {$sessionData['created_at']}" . PHP_EOL;
    echo "Last Activity:  {$sessionData['last_activity']}" . PHP_EOL;
    echo "TTL:            {$ttl} seconds" . PHP_EOL;

} catch (Throwable $exception) {
    echo PHP_EOL;
    echo "[FAIL] " . $exception->getMessage() . PHP_EOL;
    exit(1);
}