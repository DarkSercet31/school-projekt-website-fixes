<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';


if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: auth/login.php');
    exit;
}

$selected_serial = $_GET['serial'] ?? '';
$from            = $_GET['from'] ?? '';
$to              = $_GET['to'] ?? '';
$sort            = $_GET['sort'] ?? 'timestamp_desc';

$params = [];
$types  = '';
$where  = [];

if ($selected_serial !== '') {
    $where[]  = "fk_station_records = ?";
    $params[] = $selected_serial;
    $types   .= 's';
}
if ($from !== '') {
    $where[]  = "timestamp >= ?";
    $params[] = $from;
    $types   .= 's';
}
if ($to !== '') {
    $where[]  = "timestamp <= ?";
    $params[] = $to;
    $types   .= 's';
}

switch ($sort) {
    case 'timestamp_asc':
        $orderBy = "timestamp ASC";
        break;
    case 'temp_desc':
        $orderBy = "temperature DESC";
        break;
    case 'temp_asc':
        $orderBy = "temperature ASC";
        break;
    default:
        $orderBy = "timestamp DESC";
        break;
}

$sql = "SELECT pk_measurement, fk_station_records, timestamp,
               temperature, humidity, pressure, light, gas
        FROM measurement";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY $orderBy";

$stmt = mysqli_prepare($link, $sql);
if ($stmt && $types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    die('DB error');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="measurements_export.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Station', 'Timestamp', 'Temperature', 'Humidity', 'Pressure', 'Light', 'Gas']);

while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($out, [
        $row['pk_measurement'],
        $row['fk_station_records'],
        $row['timestamp'],
        $row['temperature'],
        $row['humidity'],
        $row['pressure'],
        $row['light'],
        $row['gas'],
    ]);
}

fclose($out);
if ($stmt) { mysqli_stmt_close($stmt); }
exit;