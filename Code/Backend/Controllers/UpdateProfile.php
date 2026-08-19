<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/CorsConfig.php';
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

try {
    /*
     * Convert browser token into Redis session ID
     */
    $sessionId = hash(
        'sha256',
        $sessionToken
    );

    /*
     * Get Redis session
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
     * Validate session structure
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
     * Verify MySQL account
     */
    $mysqlConnection = new MySQL();
    $mysql = $mysqlConnection->getConnection();

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
     * Get MongoDB profile collection
     */
    $mongoConnection = new Mongo();
    $mongoDatabase = $mongoConnection->getDatabase();

    $profiles = $mongoDatabase->selectCollection(
        'profiles'
    );

    /*
     * Get current profile.
     */
    $currentProfile = $profiles->findOne([
        '_id' => $userId
    ]);

    if ($currentProfile === null) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Profile details could not be found.'
        ]);

        exit;
    }

    /*
     * Reject updates when no profile field has changed.
     */
    if (
        ($currentProfile['name'] ?? '') === $name &&
        ($currentProfile['contact'] ?? '') === $contact &&
        ($currentProfile['dob'] ?? '') === $dob
    ) {
        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'No changes were made.'
        ]);

        exit;
    }

    /*
     * Check whether another user already owns this contact number.
     *
     * The current user's own profile is excluded so they can
     * keep their existing contact number when changing another field.
     */
    $existingContact = $profiles->findOne([
        'contact' => $contact,
        '_id' => [
            '$ne' => $userId
        ]
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
     * Update MongoDB profile.
     */
    try {
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

    } catch (Throwable $mongoException) {
        /*
         * Final protection against race conditions.
         * MongoDB's unique index rejects duplicate contacts.
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

    /*
     * This should not normally happen because the profile
     * was already fetched above, but the check is retained
     * for safety.
     */
    if ($updateResult->getMatchedCount() === 0) {
        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Profile details could not be found.'
        ]);

        exit;
    }

    /*
     * Refresh Redis session activity and TTL.
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
        'message' => 'Profile updated successfully.'
    ]);

} catch (Throwable $exception) {
    error_log($exception->getMessage());

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
}