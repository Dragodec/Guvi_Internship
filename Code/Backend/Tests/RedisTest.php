<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/Redis.php';

try {
    $redis = new RedisConnection();

    $sessionId = bin2hex(random_bytes(16));
    $userUuid = 'test-user-' . bin2hex(random_bytes(8));

    $timestamp = date('Y-m-d H:i:s');

    echo "=== Redis Session Test ===" . PHP_EOL;

    // Create session
    $created = $redis->createSession(
        $sessionId,
        $userUuid,
        $timestamp,
        $timestamp
    );

    if (!$created) {
        throw new RuntimeException('Failed to create session.');
    }

    echo "Session created successfully!" . PHP_EOL;
    echo "Session ID: " . $sessionId . PHP_EOL;

    // Read session
    $session = $redis->getSession($sessionId);

    if ($session === null) {
        throw new RuntimeException('Session could not be retrieved.');
    }

    echo PHP_EOL . "Session data:" . PHP_EOL;
    echo "User UUID: " . $session['user_uuid'] . PHP_EOL;
    echo "Created At: " . $session['created_at'] . PHP_EOL;
    echo "Last Activity: " . $session['last_activity'] . PHP_EOL;

    // Check TTL
    $ttl = $redis->getSessionTtl($sessionId);

    echo PHP_EOL . "Initial TTL: " . $ttl . " seconds" . PHP_EOL;

    if ($ttl <= 0 || $ttl > 1800) {
        throw new RuntimeException('Unexpected session TTL.');
    }

    // Refresh session
    sleep(1);

    $newTimestamp = date('Y-m-d H:i:s');

    $refreshed = $redis->refreshSession(
        $sessionId,
        $newTimestamp
    );

    if (!$refreshed) {
        throw new RuntimeException('Failed to refresh session.');
    }

    $refreshedSession = $redis->getSession($sessionId);
    $refreshedTtl = $redis->getSessionTtl($sessionId);

    echo "Session refreshed successfully!" . PHP_EOL;
    echo "Updated Last Activity: "
        . $refreshedSession['last_activity']
        . PHP_EOL;
    echo "Refreshed TTL: "
        . $refreshedTtl
        . " seconds"
        . PHP_EOL;

    // Delete session
    $deleted = $redis->deleteSession($sessionId);

    if (!$deleted) {
        throw new RuntimeException('Failed to delete session.');
    }

    echo PHP_EOL . "Session deleted successfully!" . PHP_EOL;

    // Verify deletion
    $deletedSession = $redis->getSession($sessionId);

    if ($deletedSession !== null) {
        throw new RuntimeException(
            'Session still exists after deletion.'
        );
    }

    echo "Session lifecycle test passed!" . PHP_EOL;

} catch (Throwable $e) {
    echo PHP_EOL . "Redis session test failed!" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
