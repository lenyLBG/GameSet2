<?php
$pdo = new PDO('mysql:host=192.168.56.200;port=3306;dbname=GameSet','appUser','Demaindeslaube');
$stmt = $pdo->query("SHOW TABLES LIKE '%migration%'");
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($rows)) {
    echo "No migration tables found\n";
} else {
    echo "Migration tables:\n";
    foreach ($rows as $r) echo " - $r\n";
    // show contents of migrations table if exists
    foreach ($rows as $r) {
        echo "\nContents of $r:\n";
        $q = $pdo->query("SELECT * FROM `$r` LIMIT 10");
        $cols = $q->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            print_r($c);
        }
    }
}
