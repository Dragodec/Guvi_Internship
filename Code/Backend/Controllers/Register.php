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

$name = isset($_POST['name']) ? trim((string) $_POST['name']) : '';
$email = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
$dob = isset($_POST['dob']) ? trim((string) $_POST['dob']) : '';
$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

if ($name === '' || strlen($name) > 50 || preg_match('/\d/', $name)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid name.'
    ]);

    exit;
}

if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);

    exit;
}

$dobDate = DateTime::createFromFormat('Y-m-d', $dob);

if (
    $dobDate === false ||
    $dobDate->format('Y-m-d') !== $dob ||
    $dobDate > new DateTime('today')
) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid date of birth.'
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
$userId = null;

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

    $checkEmail = $mysql->prepare(
        'SELECT user_id FROM users WHERE email = :email LIMIT 1'
    );

    $checkEmail->execute([
        ':email' => $email
    ]);

    if ($checkEmail->fetch() !== false) {
        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email already exists.'
        ]);

        exit;
    }

    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

    $userId = vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($bytes), 4)
    );

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    if ($passwordHash === false) {
        throw new RuntimeException('Password hashing failed.');
    }

    $mysql->beginTransaction();

    $insertUser = $mysql->prepare(
        'INSERT INTO users (
            user_id,
            email,
            password_hash
        ) VALUES (
            :user_id,
            :email,
            :password_hash
        )'
    );

    $insertUser->execute([
        ':user_id' => $userId,
        ':email' => $email,
        ':password_hash' => $passwordHash
    ]);

    $mysql->commit();

    try {
        $mongoClient = new Client($config->getMongoUri());
        $mongoDatabase = $mongoClient->selectDatabase(
            $config->getMongoDatabase()
        );
        $profiles = $mongoDatabase->selectCollection('profiles');

        $profiles->insertOne([
            '_id' => $userId,
            'name' => $name,
            'dob' => $dob
        ]);
    } catch (Throwable $mongoException) {
        $deleteUser = $mysql->prepare(
            'DELETE FROM users WHERE user_id = :user_id'
        );

        $deleteUser->execute([
            ':user_id' => $userId
        ]);

        throw $mongoException;
    }

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful.'
    ]);
} catch (PDOException $exception) {
    if ($mysql !== null && $mysql->inTransaction()) {
        $mysql->rollBack();
    }

    if ($exception->getCode() === '23000') {
        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Email already exists.'
        ]);

        exit;
    }

    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
} catch (Throwable $exception) {
    if ($mysql !== null && $mysql->inTransaction()) {
        $mysql->rollBack();
    }

    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}