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

// Load user's stations for filter dropdown
$stationStmt = mysqli_prepare($link,
    "SELECT pk_serialNumber, name FROM station WHERE fk_user_owns = ? ORDER BY name"
);
mysqli_stmt_bind_param($stationStmt, 's', $username);
mysqli_stmt_execute($stationStmt);
$stationResult = mysqli_stmt_get_result($stationStmt);
$stations = [];
while ($row = mysqli_fetch_assoc($stationResult)) {
    $stations[] = $row;
}
mysqli_stmt_close($stationStmt);

// Filter parameters
$filterStation  = trim($_GET['station']    ?? '');
$filterDateFrom = trim($_GET['date_from']  ?? '');
$filterDateTo   = trim($_GET['date_to']    ?? '');

// Build dynamic query
$conditions = ["s.fk_user_owns = ?"];
$params     = [$username];
$types      = 's';

if ($filterStation !== '') {
    $conditions[] = "s.pk_serialNumber = ?";
    $params[]     = $filterStation;
    $types       .= 's';
}
if ($filterDateFrom !== '') {
    $conditions[] = "DATE(m.timestamp) >= ?";
    $params[]     = $filterDateFrom;
    $types       .= 's';
}
if ($filterDateTo !== '') {
    $conditions[] = "DATE(m.timestamp) <= ?";
    $params[]     = $filterDateTo;
    $types       .= 's';
}

$where = 'WHERE ' . implode(' AND ', $conditions);

$sql = "SELECT m.pk_measurement, m.timestamp, m.temperature, m.humidity,
               m.pressure, m.light, m.gas, s.name AS station_name
        FROM measurement m
        JOIN station s ON m.fk_station_records = s.pk_serialNumber
        $where
        ORDER BY m.timestamp DESC
        LIMIT 200";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result       = mysqli_stmt_get_result($stmt);
$measurements = [];
while ($row = mysqli_fetch_assoc($result)) {
    $measurements[] = $row;
}
mysqli_stmt_close($stmt);

// Prepare chart data (chronological order, limit 50 for chart)
$chartData = array_slice(array_reverse($measurements), 0, 50);
$chartLabels = array_map(fn($m) => substr($m['timestamp'], 0, 16), $chartData);
$chartTemp   = array_map(fn($m) => $m['temperature'], $chartData);
$chartHum    = array_map(fn($m) => $m['humidity'], $chartData);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Messwerte' : 'My Measurements'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Bis zu 200 gefilterte Messwerte.'
                            : 'Up to 200 filtered measurements.'; ?>
                    </p>
                </div>
            </div>

            <!-- Filter form -->
            <form method="get" class="row g-2 align-items-end mt-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">
                        <?php echo ($lang === 'de') ? 'Station' : 'Station'; ?>
                    </label>
                    <select name="station" class="form-select form-select-sm">
                        <option value="">
                            <?php echo ($lang === 'de') ? 'Alle Stationen' : 'All stations'; ?>
                        </option>
                        <?php foreach ($stations as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>"
                                <?php echo $filterStation === $st['pk_serialNumber'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($st['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">
                        <?php echo ($lang === 'de') ? 'Von' : 'From'; ?>
                    </label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">
                        <?php echo ($lang === 'de') ? 'Bis' : 'To'; ?>
                    </label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($filterDateTo); ?>">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary-soft btn-sm flex-grow-1">
                        <?php echo ($lang === 'de') ? 'Filtern' : 'Filter'; ?>
                    </button>
                    <a href="user_measurements.php" class="btn btn-chip btn-sm">✕</a>
                </div>
            </form>
        </section>

        <!-- Chart -->
        <?php if (!empty($chartData)): ?>
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3" style="font-size:.95rem;">
                <?php echo ($lang === 'de')
                    ? 'Temperatur & Luftfeuchtigkeit (letzte 50 Punkte)'
                    : 'Temperature & Humidity (last 50 points)'; ?>
            </h2>
            <canvas id="measurementChart" style="max-height:280px;"></canvas>
        </section>
        <?php endif; ?>

        <!-- Data table -->
        <section class="glass-card">
            <?php if (empty($measurements)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Keine Messwerte gefunden.'
                        : 'No measurements found.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Station' : 'Station'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Zeitstempel' : 'Timestamp'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Temp (°C)' : 'Temp (°C)'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Luftfeuchte (%)' : 'Humidity (%)'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Druck (hPa)' : 'Pressure (hPa)'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Licht' : 'Light'; ?></th>
                                <th>Gas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($measurements as $m): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['station_name'] ?? ''); ?></td>
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
<?php if (!empty($chartData)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    const labels = <?php echo json_encode($chartLabels); ?>;
    const temp   = <?php echo json_encode($chartTemp);   ?>;
    const hum    = <?php echo json_encode($chartHum);    ?>;

    new Chart(document.getElementById('measurementChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: '<?php echo ($lang === 'de') ? 'Temperatur (°C)' : 'Temperature (°C)'; ?>',
                    data: temp,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67,97,238,.12)',
                    tension: .3,
                    fill: true,
                    pointRadius: 2,
                },
                {
                    label: '<?php echo ($lang === 'de') ? 'Luftfeuchte (%)' : 'Humidity (%)'; ?>',
                    data: hum,
                    borderColor: '#7209b7',
                    backgroundColor: 'rgba(114,9,183,.08)',
                    tension: .3,
                    fill: false,
                    pointRadius: 2,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: '#e2e8f0' } } },
            scales: {
                x: { ticks: { color: '#94a3b8', maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,.06)' } },
                y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,.06)' } },
            },
        },
    });
})();
</script>
<?php endif; ?>
