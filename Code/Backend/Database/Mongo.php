<?php

declare(strict_types=1);

require_once __DIR__ . '/../Config/DevConfig.php';

use MongoDB\Client;
use MongoDB\Database;

class Mongo
{
    private Client $client;
    private Database $database;

    public function __construct()
    {
        $config = DevConfig::getInstance();

        $uri = $config->getMongoUri();
        $databaseName = $config->getMongoDatabase();

        try {
            $this->client = new Client($uri);
            $this->database = $this->client->selectDatabase($databaseName);

            // Verify that MongoDB is reachable.
            $this->database->command(['ping' => 1])->toArray();

        } catch (Throwable $e) {
            throw new RuntimeException(
                'MongoDB connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }
}
