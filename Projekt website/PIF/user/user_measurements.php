<?php
// user_measurements.php – Measurements of my stations (neues Layout)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Muss eingeloggt sein
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$currentUser = $_SESSION['pk_username'] ?? null;
if (!$currentUser) {
    header("Location: ../includes/logout.inc.php");
    exit;
}

// -----------------------------------------------------------------------------
// Stationen des Users für das Dropdown laden
// -----------------------------------------------------------------------------
$stations = [];
$sql  = "SELECT pk_serialNumber, name
         FROM station
         WHERE fk_user_owns = ?
         ORDER BY pk_serialNumber";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $stations[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// -----------------------------------------------------------------------------
// Filter: Station + Zeitraum
// -----------------------------------------------------------------------------
$selectedStation = $_GET['station'] ?? ($_POST['station'] ?? '');
$from            = $_GET['from'] ?? ($_POST['from'] ?? '');
$to              = $_GET['to'] ?? ($_POST['to'] ?? '');
$measurements    = [];

// Default: erste Station + letzte 24h
if ($selectedStation === '' && !empty($stations)) {
    $selectedStation = $stations[0]['pk_serialNumber'];
}
if ($from === '' || $to === '') {
    $now   = new DateTime();
    $fromD = (clone $now)->modify('-24 hours');
    $from  = $fromD->format('Y-m-d\TH:i');
    $to    = $now->format('Y-m-d\TH:i');
}

// Wenn „Show“ gedrückt wurde oder Station gesetzt ist: Messwerte laden
if ($selectedStation !== '' && $from !== '' && $to !== '') {
    $fromSql = date('Y-m-d H:i:s', strtotime($from));
    $toSql   = date('Y-m-d H:i:s', strtotime($to));

    $sql  = "SELECT timestamp,
                    temperature,
                    humidity,
                    pressure,
                    light,
                    gas
             FROM measurement
             WHERE fk_station_records = ?
               AND timestamp BETWEEN ? AND ?
             ORDER BY timestamp";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, 'sss', $selectedStation, $fromSql, $toSql);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $measurements[] = $row;
    }
    mysqli_free_result($res);
    mysqli_stmt_close($stmt);
}

$lang = $_SESSION['lang'] ?? 'en';
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <!-- Card 1: Filter -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Messwerte meiner Stationen'
                            : 'Measurements of my stations'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Wähle eine Station und einen Zeitraum, um Messwerte anzuzeigen.'
                            : 'Select a station and time range to view measurements.'; ?>
                    </p>
                </div>
            </div>

            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Station' : 'Station'; ?>
                    </label>
                    <select name="station" class="form-select">
                        <?php foreach ($stations as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>"
                                <?php echo ($selectedStation === $st['pk_serialNumber']) ? 'selected' : ''; ?>>
                                <?php
                                $label = $st['pk_serialNumber'] . ' (' . $st['name'] . ')';
                                echo htmlspecialchars($label);
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Von' : 'From'; ?>
                    </label>
                    <input type="datetime-local" name="from" class="form-control"
                           value="<?php echo htmlspecialchars($from); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Bis' : 'To'; ?>
                    </label>
                    <input type="datetime-local" name="to" class="form-control"
                           value="<?php echo htmlspecialchars($to); ?>">
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary-soft flex-grow-1">
                        <?php echo ($lang === 'de') ? 'Anzeigen' : 'Show'; ?>
                    </button>
                    <button type="button" id="btnLast24h" class="btn btn-chip">
                        24h
                    </button>
                    <button type="button" id="btnLast7d" class="btn btn-chip">
                        7d
                    </button>
                </div>
            </form>
        </section>

        <!-- Card 2: Temperatur-Verlauf (Platzhalter für Chart) -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <h2 class="glass-card-title mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Temperatur über die Zeit'
                        : 'Temperature over time'; ?>
                </h2>
            </div>

            <?php if (empty($measurements)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Keine Messwerte im ausgewählten Zeitraum.'
                        : 'No measurements in the selected period.'; ?>
                </p>
            <?php else: ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Hier könnte dein Diagramm eingebunden werden (z.B. Chart.js).'
                        : 'This is where your chart can be rendered (e.g. Chart.js).'; ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- Card 3: Tabelle mit Messwerten -->
        <section class="glass-card">
            <div class="glass-card-header">
                <h2 class="glass-card-title mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Messwerte (Tabelle)'
                        : 'Measurements (table)'; ?>
                </h2>
            </div>

            <?php if (empty($measurements)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Keine Messwerte gefunden.'
                        : 'No measurements found.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Zeitpunkt' : 'Time'; ?></th>
                            <th><?php echo ($lang === 'de')
                                    ? 'Temperatur [°C]'
                                    : 'Temperature [°C]'; ?></th>
                            <th><?php echo ($lang === 'de')
                                    ? 'Luftfeuchte [%]'
                                    : 'Humidity [%]'; ?></th>
                            <th><?php echo ($lang === 'de')
                                    ? 'Luftdruck [hPa]'
                                    : 'Pressure [hPa]'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Licht' : 'Light'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Gas' : 'Gas'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($measurements as $m): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($m['timestamp']); ?></td>
                                <td><?php echo htmlspecialchars($m['temperature']); ?></td>
                                <td><?php echo htmlspecialchars($m['humidity']); ?></td>
                                <td><?php echo htmlspecialchars($m['pressure']); ?></td>
                                <td><?php echo htmlspecialchars($m['light']); ?></td>
                                <td><?php echo htmlspecialchars($m['gas']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script>
// 24h / 7d Buttons für Schnellwahl
(function () {
    const btn24 = document.getElementById('btnLast24h');
    const btn7d = document.getElementById('btnLast7d');
    const fromInput = document.querySelector('input[name="from"]');
    const toInput   = document.querySelector('input[name="to"]');

    function setRange(hoursBack) {
        const now   = new Date();
        const from  = new Date(now.getTime() - hoursBack * 60 * 60 * 1000);

        const toStr   = now.toISOString().slice(0,16);
        const fromStr = from.toISOString().slice(0,16);

        if (fromInput && toInput) {
            fromInput.value = fromStr;
            toInput.value   = toStr;
        }
    }

    if (btn24) {
        btn24.addEventListener('click', function () {
            setRange(24);
        });
    }
    if (btn7d) {
        btn7d.addEventListener('click', function () {
            setRange(24 * 7);
        });
    }
})();
</script>