<?php
// add_measurement.php
// Simple API endpoint to add a measurement for a station

require '../includes/db_connection.php';

// Optional: simple token so not everyone can push data
$secretToken = 'CHANGE_ME_SECRET_TOKEN';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST allowed']);
    exit;
}

$token       = $_POST['token'] ?? '';
$serial      = trim($_POST['serial'] ?? '');        // station pk_serialNumber
$temperature = $_POST['temperature'] ?? null;
$humidity    = $_POST['humidity'] ?? null;
$pressure    = $_POST['pressure'] ?? null;
$light       = $_POST['light'] ?? null;
$gas         = $_POST['gas'] ?? null;

if ($token !== $secretToken) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    exit;
}

if ($serial === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing serial']);
    exit;
}

// Optional: check that station exists
$stmt = mysqli_prepare($link, "SELECT pk_serialNumber FROM station WHERE pk_serialNumber = ?");
mysqli_stmt_bind_param($stmt, 's', $serial);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Unknown station']);
    exit;
}
mysqli_stmt_close($stmt);

// Insert measurement
$stmt = mysqli_prepare(
    $link,
    "INSERT INTO measurement
        (temperature, humidity, pressure, light, gas, timestamp, fk_station_records)
     VALUES (?, ?, ?, ?, ?, NOW(), ?)"
);
mysqli_stmt_bind_param(
    $stmt,
    'ddddds',
    $temperature,
    $humidity,
    $pressure,
    $light,
    $gas,
    $serial
);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB error']);
} else {
    echo json_encode(['status' => 'ok']);
}