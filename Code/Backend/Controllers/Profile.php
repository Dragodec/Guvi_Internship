<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/CorsConfig.php';
require_once __DIR__ . '/../Config/RateLimiter.php';
require_once __DIR__ . '/../Database/Redis.php';
require_once __DIR__ . '/../Database/MySQL.php';
require_once __DIR__ . '/../Database/Mongo.php';

CorsConfig::apply();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

$sessionToken = isset($_POST['token'])
    ? trim((string) $_POST['token'])
    : '';

if ($sessionToken === '') {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.'
    ]);

    exit;
}

try {
    /*
     * Initialize rate limiter.
     */
    $rateLimiter = new RateLimiter();

    /*
     * Get client IP address.
     */
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    /*
     * Create Redis key for profile rate limiting.
     */
    $ipKey = 'rate_limit:profile:ip:' . hash(
        'sha256',
        $clientIp
    );

    /*
     * Rate limit profile requests by IP address.
     * Maximum: 60 requests every 60 seconds.
     */
    if (!$rateLimiter->attempt(
        $ipKey,
        60,
        60
    )) {
        $retryAfter = $rateLimiter->getRetryAfter(
            $ipKey
        );

        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter
        ]);

        exit;
    }

    /*
     * Convert browser token into Redis session ID.
     */
    $sessionId = hash(
        'sha256',
        $sessionToken
    );

    /*
     * Get Redis session.
     */
    $redisConnection = new RedisConnection();

    $sessionData = $redisConnection->getSession(
        $sessionId
    );

    if ($sessionData === null) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please log in again.'
        ]);

        exit;
    }

    /*
     * Validate session structure.
     */
    if (
        !isset($sessionData['user_id']) ||
        !isset($sessionData['created_at']) ||
        !isset($sessionData['last_activity'])
    ) {
        $redisConnection->deleteSession(
            $sessionId
        );

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid session. Please log in again.'
        ]);

        exit;
    }

    $userId = $sessionData['user_id'];

    /*
     * Verify MySQL account.
     */
    $mysqlConnection = new MySQL();
    $mysql = $mysqlConnection->getConnection();

    $getUser = $mysql->prepare(
        'SELECT email, is_suspended
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $getUser->execute([
        ':user_id' => $userId
    ]);

    $user = $getUser->fetch();

    if ($user === false) {
        $redisConnection->deleteSession(
            $sessionId
        );

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Account could not be found.'
        ]);

        exit;
    }

    if ((int) $user['is_suspended'] === 1) {
        $redisConnection->deleteSession(
            $sessionId
        );

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'This account has been suspended.'
        ]);

        exit;
    }

    /*
     * Get MongoDB profile.
     */
    $mongoConnection = new Mongo();
    $mongoDatabase = $mongoConnection->getDatabase();

    $profiles = $mongoDatabase->selectCollection(
        'profiles'
    );

    $profile = $profiles->findOne([
        '_id' => $userId
    ]);

    if ($profile === null) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Profile details could not be found.'
        ]);

        exit;
    }

    /*
     * Refresh session activity and TTL.
     */
    $lastActivity = new DateTimeImmutable('now');

    $sessionRefreshed = $redisConnection->refreshSession(
        $sessionId,
        $lastActivity->format('Y-m-d H:i:s')
    );

    if (!$sessionRefreshed) {
        throw new RuntimeException(
            'Failed to refresh session.'
        );
    }

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'data' => [
            'name' => $profile['name'] ?? '',
            'email' => $user['email'],
            'contact' => $profile['contact'] ?? '',
            'dob' => $profile['dob'] ?? ''
        ]
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}