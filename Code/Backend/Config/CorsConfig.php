<?php

declare(strict_types=1);

require_once __DIR__ . '/DevConfig.php';

class CorsConfig
{
    public static function apply(): void
    {
        $config = DevConfig::getInstance();

        header('Access-Control-Allow-Origin: ' . $config->getClientUrl());
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}