<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/CorsConfig.php';
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
     * Hash browser token to get Redis session ID
     */
    $sessionId = hash(
        'sha256',
        $sessionToken
    );

    /*
     * Delete Redis session
     */
    $redisConnection = new RedisConnection();

    $deleted = $redisConnection->deleteSession(
        $sessionId
    );

    if (!$deleted) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Session is no longer valid.'
        ]);

        exit;
    }

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Logout successful.'
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}