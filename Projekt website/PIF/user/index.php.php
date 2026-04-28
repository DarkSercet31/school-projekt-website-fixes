<?php
// user_dashboard.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/db_connection.php';
require 'config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: auth/login.php');
    exit;
}

// adjust to your session variable for username
$currentUser = $_SESSION['username'];

// Load stations owned by this user
$stmt = mysqli_prepare(
    $link,
    "SELECT pk_serialNumber, name, description
     FROM station
     WHERE fk_user_owns = ?
     ORDER BY name"
);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$stationsRes = mysqli_stmt_get_result($stmt);
$stations    = [];
while ($row = mysqli_fetch_assoc($stationsRes)) {
    $stations[] = $row;
}
mysqli_free_result($stationsRes);
mysqli_stmt_close($stmt);

// For each station, load latest measurement
$latestByStation = [];
if (count($stations) > 0) {
    $stmt = mysqli_prepare(
        $link,
        "SELECT m.*
         FROM measurement m
         WHERE m.fk_station_records = ?
         ORDER BY m.timestamp DESC
         LIMIT 1"
    );
    foreach ($stations as $st) {
        mysqli_stmt_bind_param($stmt, 's', $st['pk_serialNumber']);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $latestByStation[$st['pk_serialNumber']] = mysqli_fetch_assoc($res) ?: null;
        mysqli_free_result($res);
    }
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title>User dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/headers.css">
    <link rel="stylesheet" href="css/sidebars.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 80px;">
    <h1 class="h4 mb-3">
        <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My stations'; ?>
    </h1>

    <?php if (count($stations) === 0): ?>
        <p class="text-muted">
            <?php echo ($lang === 'de')
                ? 'Sie besitzen noch keine Stationen.'
                : 'You do not own any stations yet.'; ?>
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-sm align-middle">
                <thead>
                    <tr>
                        <th>Serial</th>
                        <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                        <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                        <th><?php echo ($lang === 'de') ? 'Letzte Messung' : 'Latest measurement'; ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stations as $st): ?>
                    <?php $m = $latestByStation[$st['pk_serialNumber']] ?? null; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($st['pk_serialNumber']); ?></td>
                        <td><?php echo htmlspecialchars($st['name']); ?></td>
                        <td><?php echo htmlspecialchars($st['description']); ?></td>
                        <td>
                            <?php if ($m): ?>
                                <?php echo htmlspecialchars($m['timestamp']); ?>
                                <?php if ($m['temperature'] !== null): ?>
                                    , T: <?php echo htmlspecialchars($m['temperature']); ?> °C
                                <?php endif; ?>
                                <?php if ($m['humidity'] !== null): ?>
                                    , H: <?php echo htmlspecialchars($m['humidity']); ?> %
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?php echo ($lang === 'de')
                                        ? 'Keine Messungen'
                                        : 'No measurements'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="chart_station.php?serial=<?php echo urlencode($st['pk_serialNumber']); ?>">
                                <?php echo ($lang === 'de') ? 'Diagramm' : 'Chart'; ?>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary"
                               href="admin_measurements.php?serial=<?php echo urlencode($st['pk_serialNumber']); ?>">
                                <?php echo ($lang === 'de') ? 'Details' : 'Details'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>