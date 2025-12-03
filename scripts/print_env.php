<?php
require __DIR__ . '/../vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');
echo "DATABASE_URL=" . ($_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? 'NULL') . "\n";
