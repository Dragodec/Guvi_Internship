<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/CorsConfig.php';
require_once __DIR__ . '/../Config/RateLimiter.php';
require_once __DIR__ . '/../Database/MySQL.php';
require_once __DIR__ . '/../Database/Redis.php';

CorsConfig::apply();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

$email = isset($_POST['email'])
    ? trim((string) $_POST['email'])
    : '';

$password = isset($_POST['password'])
    ? (string) $_POST['password']
    : '';

if (
    $email === '' ||
    strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);

    exit;
}

if ($password === '') {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Password is required.'
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
     * Create hashed Redis keys.
     */
    $emailKey = 'rate_limit:login:email:' . hash(
        'sha256',
        strtolower($email)
    );

    $ipKey = 'rate_limit:login:ip:' . hash(
        'sha256',
        $clientIp
    );

    /*
     * Rate limit by email.
     * Maximum: 5 attempts every 15 minutes.
     */
    if (!$rateLimiter->attempt(
        $emailKey,
        5,
        900
    )) {
        $retryAfter = $rateLimiter->getRetryAfter(
            $emailKey
        );

        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many login attempts. Please try again later.',
            'retry_after' => $retryAfter
        ]);

        exit;
    }

    /*
     * Rate limit by IP address.
     * Maximum: 20 attempts every 15 minutes.
     */
    if (!$rateLimiter->attempt(
        $ipKey,
        20,
        900
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
     * Get MySQL connection.
     */
    $mysqlConnection = new MySQL();
    $mysql = $mysqlConnection->getConnection();

    /*
     * Find user.
     */
    $getUser = $mysql->prepare(
        'SELECT user_id, password_hash, is_suspended
         FROM users
         WHERE email = :email
         LIMIT 1'
    );

    $getUser->execute([
        ':email' => $email
    ]);

    $user = $getUser->fetch();

    /*
     * Prevent account enumeration.
     */
    if ($user === false) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials.'
        ]);

        exit;
    }

    /*
     * Verify password.
     */
    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials.'
        ]);

        exit;
    }

    /*
     * Check account suspension.
     */
    if ((int) $user['is_suspended'] === 1) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'This account has been suspended.'
        ]);

        exit;
    }

    /*
     * Successful authentication.
     * Clear failed login attempts for this email.
     */
    $rateLimiter->reset(
        $emailKey
    );

    /*
     * Generate session token.
     */
    $sessionToken = rtrim(
        strtr(
            base64_encode(random_bytes(32)),
            '+/',
            '-_'
        ),
        '='
    );

    /*
     * Hash token before storing session in Redis.
     */
    $sessionId = hash(
        'sha256',
        $sessionToken
    );

    $createdAt = new DateTimeImmutable('now');

    $createdAtString = $createdAt->format(
        'Y-m-d H:i:s'
    );

    /*
     * Create Redis session.
     */
    $redisConnection = new RedisConnection();

    $sessionCreated = $redisConnection->createSession(
        $sessionId,
        $user['user_id'],
        $createdAtString,
        $createdAtString
    );

    if (!$sessionCreated) {
        throw new RuntimeException(
            'Failed to create session.'
        );
    }

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'token' => $sessionToken
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}
