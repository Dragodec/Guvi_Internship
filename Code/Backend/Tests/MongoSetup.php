<?php

declare(strict_types=1);

require_once __DIR__ . '/../Database/Mongo.php';

try {
    $mongo = new Mongo();
    $database = $mongo->getDatabase();

    $collectionName = 'profiles';

    $collections = $database->listCollections();
    $collectionExists = false;

    foreach ($collections as $collection) {
        if ($collection->getName() === $collectionName) {
            $collectionExists = true;
            break;
        }
    }

    if (!$collectionExists) {
        $database->createCollection($collectionName);
        echo "MongoDB collection 'profiles' created successfully!" . PHP_EOL;
    } else {
        echo "MongoDB collection 'profiles' already exists." . PHP_EOL;
    }

    echo "Database: " . $database->getDatabaseName() . PHP_EOL;
    echo "Collection: " . $collectionName . PHP_EOL;

} catch (Throwable $e) {
    echo "MongoDB setup failed!" . PHP_EOL;
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
