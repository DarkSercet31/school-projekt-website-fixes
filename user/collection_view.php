<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$lang     = $_SESSION['lang'] ?? 'en';
$username = $_SESSION['pk_username'] ?? '';

$collectionId = (int)($_GET['id'] ?? 0);
if ($collectionId <= 0) {
    header('Location: user_collections.php');
    exit;
}

// Check user is creator or has been granted access
$stmt = mysqli_prepare($link,
    "SELECT c.pk_collection, c.name, c.description, c.fk_user_creates,
            u.firstName, u.lastName
     FROM collection c
     LEFT JOIN user u ON c.fk_user_creates = u.pk_username
     WHERE c.pk_collection = ?
       AND (c.fk_user_creates = ?
            OR EXISTS (
                SELECT 1 FROM hasaccess ha
                WHERE ha.pkfk_collection = c.pk_collection AND ha.pkfk_user = ?
            ))"
);
mysqli_stmt_bind_param($stmt, 'iss', $collectionId, $username, $username);
mysqli_stmt_execute($stmt);
$res        = mysqli_stmt_get_result($stmt);
$collection = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$collection) {
    header('Location: user_collections.php');
    exit;
}

// Load measurements in this collection
$stmt = mysqli_prepare($link,
    "SELECT m.pk_measurement, m.timestamp, m.temperature, m.humidity,
            m.pressure, m.light, m.gas, m.fk_station_records
     FROM contains ct
     JOIN measurement m ON ct.pkfk_measurement = m.pk_measurement
     WHERE ct.pkfk_collection = ?
     ORDER BY m.timestamp DESC"
);
mysqli_stmt_bind_param($stmt, 'i', $collectionId);
mysqli_stmt_execute($stmt);
$res          = mysqli_stmt_get_result($stmt);
$measurements = [];
while ($row = mysqli_fetch_assoc($res)) {
    $measurements[] = $row;
}
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo htmlspecialchars($collection['name']); ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo htmlspecialchars($collection['description'] ?? ''); ?>
                    </p>
                    <small class="text-muted">
                        <?php echo ($lang === 'de') ? 'Erstellt von' : 'Created by'; ?>:
                        <?php echo htmlspecialchars(
                            trim($collection['firstName'] . ' ' . $collection['lastName'])
                            ?: $collection['fk_user_creates']
                        ); ?>
                    </small>
                </div>
                <a href="user_collections.php" class="btn btn-chip btn-sm">
                    <?php echo ($lang === 'de') ? '← Zurück' : '← Back'; ?>
                </a>
            </div>
        </section>

        <section class="glass-card">
            <p class="glass-card-sub mb-3">
                <?php echo count($measurements); ?>
                <?php echo ($lang === 'de') ? 'Messwerte in dieser Sammlung' : 'measurements in this collection'; ?>
            </p>

            <?php if (empty($measurements)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de') ? 'Keine Messwerte.' : 'No measurements.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Station' : 'Station'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Zeitstempel' : 'Timestamp'; ?></th>
                                <th>°C</th>
                                <th>%</th>
                                <th>hPa</th>
                                <th>Lux</th>
                                <th>Gas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($measurements as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['fk_station_records']); ?></td>
                                    <td><?php echo htmlspecialchars($m['timestamp'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['temperature'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['humidity'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['pressure'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['light'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($m['gas'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
