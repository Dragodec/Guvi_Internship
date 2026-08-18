<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/MySQL.php';

try {
    $mysql = new MySQL();
    $connection = $mysql->getConnection();

    $statement = $connection->query('SELECT VERSION() AS version');
    $result = $statement->fetch();

    echo "MySQL connection successful!" . PHP_EOL;
    echo "Server version: " . $result['version'] . PHP_EOL;

} catch (Throwable $e) {
    echo "MySQL connection failed!" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

