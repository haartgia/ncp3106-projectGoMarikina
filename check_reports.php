<?php
// Quick diagnostic: check if restored reports have lat/lng
if (!extension_loaded('mysqli')) {
    die("MySQLi extension not loaded. Please enable it in php.ini\n");
}
require __DIR__ . '/config/db.php';

echo "=== Recent Reports (checking lat/lng) ===\n\n";

$res = $conn->query('SELECT id, title, latitude, longitude, status FROM reports ORDER BY id DESC LIMIT 10');
if ($res) {
    while ($r = $res->fetch_assoc()) {
        echo sprintf(
            "ID: %d | Title: %s | Lat: %s | Lng: %s | Status: %s\n",
            $r['id'],
            $r['title'],
            $r['latitude'] ?? 'NULL',
            $r['longitude'] ?? 'NULL',
            $r['status']
        );
    }
} else {
    echo "Query failed: " . $conn->error . "\n";
}

echo "\n=== Archive Reports (checking if they have lat/lng) ===\n\n";
$resArchive = $conn->query('SELECT id, title, latitude, longitude FROM reports_archive ORDER BY id DESC LIMIT 5');
if ($resArchive) {
    while ($r = $resArchive->fetch_assoc()) {
        echo sprintf(
            "ID: %d | Title: %s | Lat: %s | Lng: %s\n",
            $r['id'],
            $r['title'],
            $r['latitude'] ?? 'NULL',
            $r['longitude'] ?? 'NULL'
        );
    }
} else {
    echo "Archive query failed: " . $conn->error . "\n";
}
