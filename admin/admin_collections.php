<?php
// admin_measurements.php

// Show errors while developing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Only Admins allowed
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: auth/login.php');
    exit;
}

$message = '';

// ---------- Bulk delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_selected']) && !empty($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    $ids = array_filter($ids, function($v) { return $v > 0; });

    if (count($ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types        = str_repeat('i', count($ids));

        $sqlDel = "DELETE FROM measurement WHERE pk_measurement IN ($placeholders)";
        $stmtDel = mysqli_prepare($link, $sqlDel);
        if ($stmtDel) {
            mysqli_stmt_bind_param($stmtDel, $types, ...$ids);
            mysqli_stmt_execute($stmtDel);
            $deleted = mysqli_stmt_affected_rows($stmtDel);
            mysqli_stmt_close($stmtDel);

            $message = ($lang === 'de')
                ? "Es wurden $deleted Messungen gelöscht."
                : "$deleted measurements were deleted.";
        } else {
            $message = ($lang === 'de') ? 'Datenbankfehler beim Löschen.' : 'Database error while deleting.';
        }
    }
}

// ---------- Load stations for dropdown ----------
$stations_res = mysqli_query(
    $link,
    "SELECT pk_serialNumber, name FROM station ORDER BY name"
);
$stations = [];
while ($s = mysqli_fetch_assoc($stations_res)) {
    $stations[] = $s;
}
mysqli_free_result($stations_res);

// Filter values (GET)
$selected_serial = $_GET['serial'] ?? '';
$from            = $_GET['from'] ?? '';
$to              = $_GET['to'] ?? '';
$sort            = $_GET['sort'] ?? 'timestamp_desc';

// Build WHERE part
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

// Determine ORDER BY
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
        $orderBy = "timestamp DESC"; // newest first
        $sort    = 'timestamp_desc';
        break;
}

// ---------- Stats query ----------
$statsSql = "SELECT
    COUNT(*)         AS cnt,
    MIN(temperature) AS min_temp,
    MAX(temperature) AS max_temp,
    AVG(temperature) AS avg_temp,
    MIN(timestamp)   AS oldest_ts,
    MAX(timestamp)   AS newest_ts
    FROM measurement";

if (count($where) > 0) {
    $statsSql .= " WHERE " . implode(" AND ", $where);
}

$statsStmt = mysqli_prepare($link, $statsSql);
if ($statsStmt && $types !== '') {
    mysqli_stmt_bind_param($statsStmt, $types, ...$params);
}
if ($statsStmt) {
    mysqli_stmt_execute($statsStmt);
    $statsRes = mysqli_stmt_get_result($statsStmt);
    $stats    = mysqli_fetch_assoc($statsRes);
    mysqli_free_result($statsRes);
    mysqli_stmt_close($statsStmt);
} else {
    $stats = null;
}

// ---------- Main data query ----------
$sql = "SELECT pk_measurement, temperature, humidity, pressure, light, gas, timestamp, fk_station_records
        FROM measurement";

if (count($where) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY $orderBy LIMIT 500";

$stmt = mysqli_prepare($link, $sql);
if ($stmt && $types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = false;
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title>Admin Measurements</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/headers.css">
    <link rel="stylesheet" href="css/sidebars.css">
    <link rel="stylesheet" href="css/glassmorphic.css">
    <style>
        /* Simple black text override */
.table-glass,
.table-glass th,
.table-glass td,
.table-glass tr,
.table-glass thead,
.table-glass tbody {
    color: #000000 !important;
}

/* Optional: Make headers slightly darker for distinction */
.table-glass th {
    font-weight: 600;
}
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .table-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        
        .table-glass thead th {
            background: rgba(255, 255, 255, 0.9);
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
            padding: 15px;
            font-weight: 600;
        }
        
        .table-glass tbody tr {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .table-glass tbody tr:hover {
            background: rgba(255, 255, 255, 0.4);
        }
        
        .table-glass tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .glass-card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .glass-card-sub {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }
        
        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 1);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        
        .btn-primary-soft {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary-soft:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
        }
        
        .btn-chip {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 20px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        
        .btn-chip:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
        }
        
        .sort-links a {
            text-decoration: none;
            margin-left: 5px;
            color: #666;
            font-weight: bold;
        }
        
        .sort-links a:hover {
            color: #3b82f6;
        }
        
        .delete-checkbox {
            width: 18px;
            height: 18px;
        }
        
        .stats-badge {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            font-size: 0.95rem;
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border-radius: 8px;
        }
        
        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<main class="main-container">
    <!-- Filter card -->
    <section class="glass-card">
        <div>
            <h1 class="glass-card-title">
                <?php echo ($lang === 'de') ? 'Messungen (Admin)' : 'Measurements (Admin)'; ?>
            </h1>
            <p class="glass-card-sub">
                <?php echo ($lang === 'de')
                    ? 'Verwalten Sie alle Messungen im System'
                    : 'Manage all measurements in the system'; ?>
            </p>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-info py-3 mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="get" action="admin_measurements.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Station' : 'Station'; ?>
                </label>
                <select name="serial" class="form-select">
                    <option value="">
                        <?php echo ($lang === 'de') ? 'Alle Stationen' : 'All stations'; ?>
                    </option>
                    <?php foreach ($stations as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['pk_serialNumber']); ?>"
                            <?php if ($selected_serial === $s['pk_serialNumber']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($s['name'] . ' (' . $s['pk_serialNumber'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Von' : 'From'; ?>
                </label>
                <input type="datetime-local" name="from" class="form-control"
                       value="<?php echo htmlspecialchars($from); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Bis' : 'To'; ?>
                </label>
                <input type="datetime-local" name="to" class="form-control"
                       value="<?php echo htmlspecialchars($to); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary-soft flex-grow-1">
                    <?php echo ($lang === 'de') ? 'Anwenden' : 'Apply'; ?>
                </button>
                <a class="btn btn-chip flex-grow-1" href="admin_measurements_export.php?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=<?php echo urlencode($sort); ?>">
                    Export CSV
                </a>
            </div>
        </form>

        <?php if ($stats && $stats['cnt'] > 0): ?>
            <div class="stats-badge">
                <strong><?php echo ($lang === 'de') ? 'Übersicht: ' : 'Overview: '; ?></strong>
                <?php
                $textDe = "Anzahl: {$stats['cnt']}, Min: {$stats['min_temp']} °C, Max: {$stats['max_temp']} °C, "
                        . "Mittelwert: " . round($stats['avg_temp'], 1) . " °C, "
                        . "Älteste Messung: {$stats['oldest_ts']}, Neueste: {$stats['newest_ts']}";
                $textEn = "Count: {$stats['cnt']}, Min: {$stats['min_temp']} °C, Max: {$stats['max_temp']} °C, "
                        . "Average: " . round($stats['avg_temp'], 1) . " °C, "
                        . "Oldest: {$stats['oldest_ts']}, Newest: {$stats['newest_ts']}";
                echo ($lang === 'de') ? htmlspecialchars($textDe) : htmlspecialchars($textEn);
                ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Measurements table card -->
    <section class="glass-card">
        <div>
            <h2 class="glass-card-title">
                <?php echo ($lang === 'de') ? 'Messungen' : 'Measurements'; ?>
            </h2>
            <p class="glass-card-sub">
                <?php echo ($lang === 'de')
                    ? 'Wählen Sie Messungen aus, um sie zu löschen'
                    : 'Select measurements to delete them'; ?>
            </p>
        </div>

        <form method="post" action="admin_measurements.php?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=<?php echo urlencode($sort); ?>">
            <div class="table-responsive">
                <table class="table-glass align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all" class="delete-checkbox" onclick="
                                    const cbs = document.querySelectorAll('.row-check');
                                    cbs.forEach(cb => cb.checked = this.checked);
                                ">
                            </th>
                            <th>ID</th>
                            <th>Station</th>
                            <th>
                                <?php echo ($lang === 'de') ? 'Zeitstempel' : 'Timestamp'; ?>
                                <span class="sort-links">
                                    <a href="?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=timestamp_desc">↓</a>
                                    <a href="?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=timestamp_asc">↑</a>
                                </span>
                            </th>
                            <th>
                                Temp
                                <span class="sort-links">
                                    <a href="?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=temp_desc">↓</a>
                                    <a href="?serial=<?php echo urlencode($selected_serial); ?>&from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&sort=temp_asc">↑</a>
                                </span>
                            </th>
                            <th>Hum</th>
                            <th>Press</th>
                            <th>Light</th>
                            <th>Gas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="row-check delete-checkbox"
                                           name="selected_ids[]"
                                           value="<?php echo htmlspecialchars($row['pk_measurement']); ?>">
                                </td>
                                <td><?php echo htmlspecialchars($row['pk_measurement']); ?></td>
                                <td><?php echo htmlspecialchars($row['fk_station_records']); ?></td>
                                <td><?php echo htmlspecialchars($row['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($row['temperature']); ?></td>
                                <td><?php echo htmlspecialchars($row['humidity']); ?></td>
                                <td><?php echo htmlspecialchars($row['pressure']); ?></td>
                                <td><?php echo htmlspecialchars($row['light']); ?></td>
                                <td><?php echo htmlspecialchars($row['gas']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <?php echo ($lang === 'de')
                                    ? 'Keine Messungen gefunden.'
                                    : 'No measurements found.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
                <div>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <span class="text-muted">
                            <?php echo ($lang === 'de')
                                ? mysqli_num_rows($result) . ' Messungen angezeigt'
                                : mysqli_num_rows($result) . ' measurements shown'; ?>
                        </span>
                    <?php endif; ?>
                </div>
<div>
    <?php 
    $confirmMessage = ($lang === 'de') ? 'Ausgewählte Messungen löschen?' : 'Delete selected measurements?';
    ?>
    <button type="submit" name="delete_selected"
            class="btn btn-danger btn-sm"
            onclick="return confirm('<?php echo htmlspecialchars($confirmMessage, ENT_QUOTES); ?>');">
        <?php echo ($lang === 'de') ? 'Ausgewählte löschen' : 'Delete selected'; ?>
    </button>
</div>
            </div>
        </form>
    </section>
</main>

<?php if ($stmt) { mysqli_stmt_close($stmt); } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>