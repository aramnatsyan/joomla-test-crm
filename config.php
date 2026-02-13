<?php
require_once __DIR__ . '/vendor/autoload.php';

use Joomla\Component\CrmStages\Service\ServiceContainer;

// Database configuration
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'joomlacrm',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASS') ?: '',
];

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['name']
    );
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    ServiceContainer::getInstance()->setDatabase($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . 
        "\n\nPlease configure your database settings in the environment or edit config.php");
}
