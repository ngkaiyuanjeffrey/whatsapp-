<?php
declare(strict_types=1);

$configPath = __DIR__ . '/../config.local.php';
if (!is_file($configPath)) {
    $exampleConfigPath = __DIR__ . '/../config.example.php';
    if (is_file($exampleConfigPath)) {
        $copied = @copy($exampleConfigPath, $configPath);
        if ($copied === false) {
            http_response_code(500);
            exit('Application configuration is missing. Copy config.example.php to config.local.php and set your local database values.');
        }
    } else {
        http_response_code(500);
        exit('Application configuration is missing. Create config.local.php from config.example.php and set your local database values.');
    }
}

$config = require $configPath;
date_default_timezone_set($config['timezone']);

session_set_cookie_params([
    'lifetime' => $config['session']['lifetime'],
    'path' => '/',
    'secure' => $config['session']['secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

try {
    //echo $config['database']['dsn'], $config['database']['username'], $config['database']['password'];
    $pdo = new PDO($config['database']['dsn'], $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Database connection is unavailable. Check the local database configuration.');
}
