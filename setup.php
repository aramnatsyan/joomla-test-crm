#!/usr/bin/env php
<?php

echo "🚀 CRM Stages Setup\n";
echo "==================\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    die("❌ PHP 8.2 or higher is required. You have " . PHP_VERSION . "\n");
}
echo "✅ PHP " . PHP_VERSION . " detected\n";

// Check composer
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "⚠️  Dependencies not installed. Running composer install...\n";
    system('composer install');
    echo "✅ Dependencies installed\n";
} else {
    echo "✅ Dependencies already installed\n";
}

// Check/create .env
if (!file_exists(__DIR__ . '/.env')) {
    if (file_exists(__DIR__ . '/.env.example')) {
        copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
        echo "✅ Created .env file from template\n";
        echo "⚠️  Please edit .env with your database credentials\n";
    }
} else {
    echo "✅ .env file exists\n";
}

// Database connection test
echo "\n📊 Testing database connection...\n";

// Load .env if exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        putenv(trim($line));
    }
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'joomlacrm';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ MySQL connection successful\n";

    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$dbName}'");
    if ($stmt->rowCount() === 0) {
        echo "📝 Creating database '{$dbName}'...\n";
        $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database created\n";
    } else {
        echo "✅ Database '{$dbName}' exists\n";
    }

    // Connect to database
    $pdo->exec("USE `{$dbName}`");

    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES LIKE 'companies'");
    if ($stmt->rowCount() === 0) {
        echo "📝 Importing schema...\n";
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($schema);
        echo "✅ Schema imported\n";
    } else {
        echo "✅ Tables already exist\n";
    }

    // Check if we have test data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM companies");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Found {$count} companies in database\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\nPlease ensure:\n";
    echo "  1. MySQL is running\n";
    echo "  2. Credentials in .env are correct\n";
    echo "  3. User has permission to create databases\n";
    exit(1);
}

// Run tests
echo "\n🧪 Running tests...\n";
system(__DIR__ . '/vendor/bin/phpunit --testdox', $testResult);

if ($testResult === 0) {
    echo "\n✅ All tests passed!\n";
} else {
    echo "\n❌ Some tests failed\n";
    exit(1);
}

// Setup complete
echo "\n🎉 Setup complete!\n\n";
echo "To start the development server:\n";
echo "  php -S localhost:8000\n\n";
echo "Then open: http://localhost:8000\n";
