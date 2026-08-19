<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/CorsConfig.php';
require_once __DIR__ . '/../Config/RateLimiter.php';
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

$name = isset($_POST['name'])
    ? trim((string) $_POST['name'])
    : '';

$email = isset($_POST['email'])
    ? trim((string) $_POST['email'])
    : '';

$contact = isset($_POST['contact'])
    ? trim((string) $_POST['contact'])
    : '';

$dob = isset($_POST['dob'])
    ? trim((string) $_POST['dob'])
    : '';

$password = isset($_POST['password'])
    ? (string) $_POST['password']
    : '';

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

if (!preg_match('/^\d{10}$/', $contact)) {
    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid contact number.'
    ]);

    exit;
}

$dobDate = DateTime::createFromFormat(
    'Y-m-d',
    $dob
);

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
     * Create Redis key for registration rate limiting.
     */
    $ipKey = 'rate_limit:register:ip:' . hash(
        'sha256',
        $clientIp
    );

    /*
     * Rate limit registration attempts by IP address.
     * Maximum: 10 attempts every 15 minutes.
     */
    if (!$rateLimiter->attempt(
        $ipKey,
        10,
        900
    )) {
        $retryAfter = $rateLimiter->getRetryAfter(
            $ipKey
        );

        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Too many registration attempts. Please try again later.',
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
     * Check whether email already exists.
     */
    $checkEmail = $mysql->prepare(
        'SELECT user_id
         FROM users
         WHERE email = :email
         LIMIT 1'
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

    /*
     * Get MongoDB connection.
     */
    $mongoConnection = new Mongo();
    $mongoDatabase = $mongoConnection->getDatabase();

    $profiles = $mongoDatabase->selectCollection(
        'profiles'
    );

    /*
     * Check whether contact already exists.
     */
    $existingContact = $profiles->findOne([
        'contact' => $contact
    ]);

    if ($existingContact !== null) {
        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'Contact number already exists.'
        ]);

        exit;
    }

    /*
     * Generate UUID v4.
     */
    $bytes = random_bytes(16);

    $bytes[6] = chr(
        (ord($bytes[6]) & 0x0f) | 0x40
    );

    $bytes[8] = chr(
        (ord($bytes[8]) & 0x3f) | 0x80
    );

    $userId = vsprintf(
        '%s%s-%s-%s-%s-%s%s%s',
        str_split(bin2hex($bytes), 4)
    );

    /*
     * Hash password.
     */
    $passwordHash = password_hash(
        $password,
        PASSWORD_BCRYPT
    );

    if ($passwordHash === false) {
        throw new RuntimeException(
            'Password hashing failed.'
        );
    }

    /*
     * Create MySQL user.
     */
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
        /*
         * Create MongoDB profile.
         */
        $profiles->insertOne([
            '_id' => $userId,
            'name' => $name,
            'contact' => $contact,
            'dob' => $dob
        ]);

    } catch (Throwable $mongoException) {
        /*
         * Compensating rollback:
         * Remove MySQL user if MongoDB profile creation fails.
         */
        $deleteUser = $mysql->prepare(
            'DELETE FROM users
             WHERE user_id = :user_id'
        );

        $deleteUser->execute([
            ':user_id' => $userId
        ]);

        /*
         * Duplicate contact number.
         */
        if (
            str_contains(
                $mongoException->getMessage(),
                'E11000'
            )
        ) {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' => 'Contact number already exists.'
            ]);

            exit;
        }

        throw $mongoException;
    }

    http_response_code(201);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful.'
    ]);

} catch (PDOException $exception) {
    if (
        isset($mysql) &&
        $mysql !== null &&
        $mysql->inTransaction()
    ) {
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
    if (
        isset($mysql) &&
        $mysql !== null &&
        $mysql->inTransaction()
    ) {
        $mysql->rollBack();
    }

    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}