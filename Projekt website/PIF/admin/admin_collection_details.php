<?php
// admin_collection_details.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Only Admins
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: auth/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Missing id.');
}

// ---------- Load collection info ----------
$stmt = mysqli_prepare(
    $link,
    "SELECT c.pk_collection,
            c.name,
            c.description,
            c.fk_user_creates,
            u.firstName,
            u.lastName
     FROM collection c
     LEFT JOIN user u ON c.fk_user_creates = u.pk_username
     WHERE c.pk_collection = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$infoRes    = mysqli_stmt_get_result($stmt);
$collection = mysqli_fetch_assoc($infoRes);
mysqli_free_result($infoRes);
mysqli_stmt_close($stmt);

if (!$collection) {
    die('Collection not found.');
}

// ---------- Load measurements contained in this collection ----------
$sql = "
    SELECT m.pk_measurement,
           m.timestamp,
           m.temperature,
           m.humidity,
           m.pressure,
           m.light,
           m.gas,
           m.fk_station_records
    FROM contains c
    JOIN measurement m ON c.pkfk_measurement = m.pk_measurement
    WHERE c.pkfk_collection = ?
    ORDER BY m.timestamp DESC
";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$measRes = mysqli_stmt_get_result($stmt);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title>Collection details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/headers.css">
    <link rel="stylesheet" href="css/sidebars.css">
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container" style="margin-top: 80px;">
    <h1 class="h4 mb-3">
        <?php echo ($lang === 'de' ? 'Sammlung' : 'Collection') . ' #' . htmlspecialchars($collection['pk_collection']); ?>
    </h1>

    <div class="mb-3">
        <p class="mb-1">
            <strong><?php echo htmlspecialchars($collection['name']); ?></strong>
        </p>
        <p class="mb-1">
            <?php echo htmlspecialchars($collection['description']); ?>
        </p>
        <p class="mb-0">
            <?php
            $creator = $collection['fk_user_creates'];
            if ($collection['firstName'] || $collection['lastName']) {
                $creator .= ' (' . $collection['firstName'] . ' ' . $collection['lastName'] . ')';
            }
            echo ($lang === 'de' ? 'Ersteller: ' : 'Creator: ') . htmlspecialchars($creator);
            ?>
        </p>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Enthaltene Messungen' : 'Contained measurements'; ?>
            </h2>

            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Station</th>
                            <th><?php echo ($lang === 'de') ? 'Zeitstempel' : 'Timestamp'; ?></th>
                            <th>Temp</th>
                            <th>Hum</th>
                            <th>Press</th>
                            <th>Light</th>
                            <th>Gas</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($measRes && mysqli_num_rows($measRes) > 0): ?>
                        <?php while ($m = mysqli_fetch_assoc($measRes)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['pk_measurement']); ?></td>
                                <td><?php echo htmlspecialchars($m['fk_station_records']); ?></td>
                                <td><?php echo htmlspecialchars($m['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($m['temperature']); ?></td>
                                <td><?php echo htmlspecialchars($m['humidity']); ?></td>
                                <td><?php echo htmlspecialchars($m['pressure']); ?></td>
                                <td><?php echo htmlspecialchars($m['light']); ?></td>
                                <td><?php echo htmlspecialchars($m['gas']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Keine Messungen in dieser Sammlung.'
                                    : 'No measurements in this collection.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="admin_collections.php" class="btn btn-secondary btn-sm mt-2">
                <?php echo ($lang === 'de') ? 'Zurück' : 'Back'; ?>
            </a>
        </div>
    </div>
</div>

<?php
if ($measRes) {
    mysqli_free_result($measRes);
}
mysqli_stmt_close($stmt);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>