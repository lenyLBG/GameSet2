<?php
require __DIR__ . '/../vendor/autoload.php';
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
$url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
try {
    $conn = DriverManager::getConnection(['url' => $url]);
    $conn->connect();
    echo "DBAL OK, db: " . $conn->getDatabase() . "\n";
} catch (\Throwable $e) {
    echo "DBAL ERR: " . $e->getMessage() . "\n";
    echo $e->__toString();
}
