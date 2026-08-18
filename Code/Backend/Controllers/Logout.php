<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';
require_once __DIR__ . '/../Config/CorsConfig.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
    $config = DevConfig::getInstance();

    $sessionKey = 'session:' . hash('sha256', $sessionToken);

    $redis = new Redis();

    $redis->connect(
        $config->getRedisHost(),
        $config->getRedisPort()
    );

    if ($config->getRedisPassword() !== null) {
        $redis->auth($config->getRedisPassword());
    }

    $deleted = $redis->del($sessionKey);

    if ($deleted === 0) {
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