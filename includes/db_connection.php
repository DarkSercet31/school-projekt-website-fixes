<?php
// Database configuration — update these values to match your local setup
$host   = 'localhost';
$dbname = 'weather_station_db';
$dbuser = 'mahan085';
$dbpass = 'Anes-1234-';

// MySQLi connection — used by all existing pages
$link = mysqli_connect($host, $dbuser, $dbpass, $dbname);
if (!$link) {
    die('Database connection failed: ' . mysqli_connect_error());
}
mysqli_set_charset($link, 'utf8mb4');

// PDO connection — used by new features
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('PDO connection failed: ' . $e->getMessage());
}
