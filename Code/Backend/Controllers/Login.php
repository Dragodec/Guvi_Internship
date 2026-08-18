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

$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
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

$config = DevConfig::getInstance();

$mysql = null;

try {
    $mysql = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->getMysqlHost(),
            $config->getMysqlPort(),
            $config->getMysqlDatabase()
        ),
        $config->getMysqlUsername(),
        $config->getMysqlPassword(),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

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

    if ($user === false) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Couldn\'t find your account.'
        ]);

        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid credentials.'
        ]);

        exit;
    }

    if ((int) $user['is_suspended'] === 1) {
        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'This account has been suspended.'
        ]);

        exit;
    }

    $sessionToken = rtrim(
        strtr(
            base64_encode(random_bytes(32)),
            '+/',
            '-_'
        ),
        '='
    );

    $sessionKey = 'session:' . hash('sha256', $sessionToken);

    $createdAt = new DateTimeImmutable('now');
    $createdAtString = $createdAt->format('Y-m-d H:i:s');

    $redis = new Redis();

    $redis->connect(
        $config->getRedisHost(),
        $config->getRedisPort()
    );

    if ($config->getRedisPassword() !== null) {
        $redis->auth($config->getRedisPassword());
    }

    $redis->hMSet($sessionKey, [
        'user_id' => $user['user_id'],
        'created_at' => $createdAtString,
        'last_activity' => $createdAtString
    ]);

    $redis->expire($sessionKey, $config->getSessionTtl());

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'token' => $sessionToken
    ]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}