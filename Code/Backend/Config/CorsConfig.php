<?php

declare(strict_types=1);

require_once __DIR__ . '/DevConfig.php';

class CorsConfig
{
    public static function apply(): void
    {
        $config = DevConfig::getInstance();

        $allowedOrigins = [
            $config->getClientUrl(),
            'https://guvi-internship-kohl.vercel.app',
            'http://127.0.0.1:5500',
            'http://localhost:5500'
        ];

        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (
            $requestOrigin !== '' &&
            in_array($requestOrigin, $allowedOrigins, true)
        ) {
            header(
                'Access-Control-Allow-Origin: ' . $requestOrigin
            );
        }

        header(
            'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'
        );

        header(
            'Access-Control-Allow-Headers: Content-Type'
        );

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}