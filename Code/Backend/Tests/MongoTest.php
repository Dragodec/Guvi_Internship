<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/Mongo.php';

try {
    $mongo = new Mongo();

    $database = $mongo->getDatabase();
    $collection = $database->selectCollection('profiles');

    $mongo->getClient();

    echo "MongoDB connection successful!" . PHP_EOL;
    echo "Database: " . $database->getDatabaseName() . PHP_EOL;
    echo "Collection: " . $collection->getCollectionName() . PHP_EOL;
    echo "Ping successful!" . PHP_EOL;

} catch (Throwable $e) {
    echo "MongoDB test failed!" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
