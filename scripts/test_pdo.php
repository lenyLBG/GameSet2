<?php
try {
    $pdo = new PDO('mysql:host=192.168.56.200;port=3306;dbname=GameSet', 'appUser', 'Demaindeslaube');
    echo "OK\n";
} catch (PDOException $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
}
