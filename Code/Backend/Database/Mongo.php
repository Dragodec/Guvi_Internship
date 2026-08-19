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

            $this->database = $this->client->selectDatabase(
                $databaseName
            );

            /*
             * Verify that MongoDB is reachable.
             */
            $this->database->command([
                'ping' => 1
            ])->toArray();

            /*
             * Create the profiles collection and its unique
             * contact index only when the collection does not exist.
             */
            $this->ensureProfilesCollection();

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

    private function ensureProfilesCollection(): void
    {
        $collections = iterator_to_array(
            $this->database->listCollectionNames()
        );

        if (in_array('profiles', $collections, true)) {
            return;
        }

        $this->database->createCollection(
            'profiles'
        );

        $profiles = $this->database->selectCollection(
            'profiles'
        );

        $profiles->createIndex(
            [
                'contact' => 1
            ],
            [
                'unique' => true,
                'name' => 'unique_contact'
            ]
        );
    }
}