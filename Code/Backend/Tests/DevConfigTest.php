<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';

try {
    $config = DevConfig::getInstance();

    echo "=== MySQL ===" . PHP_EOL;
    echo "Host: " . $config->getMysqlHost() . PHP_EOL;
    echo "Port: " . $config->getMysqlPort() . PHP_EOL;
    echo "Database: " . $config->getMysqlDatabase() . PHP_EOL;
    echo "Username: " . $config->getMysqlUsername() . PHP_EOL;

    echo PHP_EOL . "=== MongoDB ===" . PHP_EOL;
    echo "URI: " . $config->getMongoUri() . PHP_EOL;
    echo "Database: " . $config->getMongoDatabase() . PHP_EOL;

    echo PHP_EOL . "=== Redis ===" . PHP_EOL;
    echo "Host: " . $config->getRedisHost() . PHP_EOL;
    echo "Port: " . $config->getRedisPort() . PHP_EOL;
    echo "Password configured: " .
        ($config->getRedisPassword() !== null ? 'Yes' : 'No') .
        PHP_EOL;

    echo PHP_EOL . "DevConfig loaded successfully!" . PHP_EOL;

} catch (Throwable $e) {
    echo "DevConfig test failed!" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}