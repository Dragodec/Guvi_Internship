<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';
require_once __DIR__ . '/../Config/CorsConfig.php';
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

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

$name = isset($_POST['name'])
    ? trim((string) $_POST['name'])
    : '';

$contact = isset($_POST['contact'])
    ? trim((string) $_POST['contact'])
    : '';

$dob = isset($_POST['dob'])
    ? trim((string) $_POST['dob'])
    : '';

if ($sessionToken === '') {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.'
    ]);

    exit;
}

if (
    $name === '' ||
    strlen($name) > 50 ||
    preg_match('/\d/', $name)
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid name.'
    ]);

    exit;
}

if (!preg_match('/^\d{10}$/', $contact)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid contact number.'
    ]);

    exit;
}

$dobDate = DateTime::createFromFormat('Y-m-d', $dob);

if (
    $dobDate === false ||
    $dobDate->format('Y-m-d') !== $dob ||
    $dobDate >= new DateTime('today')
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid date of birth.'
    ]);

    exit;
}

$config = DevConfig::getInstance();

$mysql = null;

try {
    /*
     * Redis session validation
     */
    $sessionKey = 'session:' . hash('sha256', $sessionToken);

    $redis = new Redis();

    $redis->connect(
        $config->getRedisHost(),
        $config->getRedisPort()
    );

    if ($config->getRedisPassword() !== null) {
        $redis->auth($config->getRedisPassword());
    }

    if (!$redis->exists($sessionKey)) {
        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Session expired. Please log in again.'
        ]);

        exit;
    }

    $sessionData = $redis->hGetAll($sessionKey);

    if (
        !isset($sessionData['user_id']) ||
        !isset($sessionData['created_at']) ||
        !isset($sessionData['last_activity'])
    ) {
        $redis->del($sessionKey);

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid session. Please log in again.'
        ]);

        exit;
    }

    $userId = $sessionData['user_id'];

    /*
     * Verify MySQL account
     */
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
        'SELECT user_id, is_suspended
         FROM users
         WHERE user_id = :user_id
         LIMIT 1'
    );

    $getUser->execute([
        ':user_id' => $userId
    ]);

    $user = $getUser->fetch();

    if ($user === false) {
        $redis->del($sessionKey);

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Account could not be found.'
        ]);

        exit;
    }

    if ((int) $user['is_suspended'] === 1) {
        $redis->del($sessionKey);

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'This account has been suspended.'
        ]);

        exit;
    }

    /*
     * Update MongoDB profile
     */
    $mongoClient = new Client(
        $config->getMongoUri()
    );

    $mongoDatabase = $mongoClient->selectDatabase(
        $config->getMongoDatabase()
    );

    $profiles = $mongoDatabase->selectCollection(
        'profiles'
    );

    $updateResult = $profiles->updateOne(
        [
            '_id' => $userId
        ],
        [
            '$set' => [
                'name' => $name,
                'contact' => $contact,
                'dob' => $dob
            ]
        ]
    );

    if ($updateResult->getMatchedCount() === 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Profile details could not be found.'
        ]);

        exit;
    }

    /*
     * Refresh Redis session activity
     */
    $lastActivity = new DateTimeImmutable('now');

    $redis->hSet(
        $sessionKey,
        'last_activity',
        $lastActivity->format('Y-m-d H:i:s')
    );

    $redis->expire(
        $sessionKey,
        $config->getSessionTtl()
    );

    http_response_code(200);

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully.'
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