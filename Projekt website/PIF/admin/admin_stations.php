<?php
// admin_stations.php
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

// Handle create / update / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create or update
    if (isset($_POST['save_station'])) {
        $serial      = trim($_POST['pk_serialNumber'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $owner       = trim($_POST['fk_user_owns'] ?? '');

        if ($serial === '') {
            $message = ($lang === 'de')
                ? 'Seriennummer ist ein Pflichtfeld.'
                : 'Serial number is required.';
        } else {
            if ($name === '') {
                $name = $serial;
            }

            // Check if station exists
            $stmt = mysqli_prepare($link, "SELECT pk_serialNumber FROM station WHERE pk_serialNumber = ?");
            mysqli_stmt_bind_param($stmt, 's', $serial);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                // Update existing station
                $stmt = mysqli_prepare(
                    $link,
                    "UPDATE station
                     SET name = ?, description = ?, fk_user_owns = NULLIF(?, '')
                     WHERE pk_serialNumber = ?"
                );
                mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $owner, $serial);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $message = ($lang === 'de')
                    ? 'Station aktualisiert.'
                    : 'Station updated.';
            } else {
                // Insert new station
                $stmt = mysqli_prepare(
                    $link,
                    "INSERT INTO station (pk_serialNumber, name, description, fk_user_owns)
                     VALUES (?, ?, ?, NULLIF(?, ''))"
                );
                mysqli_stmt_bind_param($stmt, 'ssss', $serial, $name, $description, $owner);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $message = ($lang === 'de')
                    ? 'Station erstellt.'
                    : 'Station created.';
            }
        }
    }

    // Delete
    if (isset($_POST['delete_station'])) {
        $serial = trim($_POST['pk_serialNumber'] ?? '');
        if ($serial !== '') {
            $stmt = mysqli_prepare($link, "DELETE FROM station WHERE pk_serialNumber = ?");
            mysqli_stmt_bind_param($stmt, 's', $serial);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message = ($lang === 'de')
                ? 'Station gelöscht.'
                : 'Station deleted.';
        }
    }
}

// Load stations
$stations_res = mysqli_query(
    $link,
    "SELECT station.pk_serialNumber, station.name, station.description,
            station.fk_user_owns, u.firstName, u.lastName
     FROM station
     LEFT JOIN user u ON station.fk_user_owns = u.pk_username
     ORDER BY station.name"
);

// Load users for owner dropdown
$users_res = mysqli_query(
    $link,
    "SELECT pk_username, firstName, lastName FROM user ORDER BY pk_username"
);
$users = [];
while ($u = mysqli_fetch_assoc($users_res)) {
    $users[] = $u;
}
mysqli_free_result($users_res);

// Count stations
$station_count = mysqli_num_rows($stations_res);
mysqli_data_seek($stations_res, 0);
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <!-- Card 1: Create station -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Stationen verwalten' : 'Manage Stations'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Erstelle neue Stationen und verwalte alle Stationen im System.'
                            : 'Create new stations and manage all stations in the system.'; ?>
                    </p>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-info py-2 mb-3">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="save_station" value="1">
                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Seriennummer' : 'Serial number'; ?>
                    </label>
                    <input type="text" name="pk_serialNumber" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Name' : 'Name'; ?>
                    </label>
                    <input type="text" name="name" class="form-control" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Eigentümer (optional)' : 'Owner (optional)'; ?>
                    </label>
                    <select name="fk_user_owns" class="form-select">
                        <option value="">
                            <?php echo ($lang === 'de') ? 'Kein Eigentümer' : 'No owner'; ?>
                        </option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo htmlspecialchars($u['pk_username']); ?>">
                                <?php echo htmlspecialchars($u['pk_username'] . ' - ' . $u['firstName'] . ' ' . $u['lastName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary-soft w-100">
                        <?php echo ($lang === 'de') ? 'Station erstellen' : 'Create Station'; ?>
                    </button>
                </div>
                <div class="col-12">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?>
                    </label>
                    <input type="text" name="description" class="form-control">
                </div>
            </form>

            <p class="glass-card-sub mt-3 mb-0">
                <?php echo ($lang === 'de')
                    ? 'Name ist optional. Wenn leer, wird die Seriennummer als Name verwendet.'
                    : 'Name is optional. If left empty, the serial number will be used as the name.'; ?>
            </p>
        </section>

        <!-- Card 2: All stations -->
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Alle Stationen' : 'All Stations'; ?>
                    </h2>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Bearbeite Namen, Beschreibung und Eigentümer aller Stationen.'
                            : 'Edit name, description and owner of all stations.'; ?>
                    </p>
                </div>
                <span class="glass-card-sub">
                    <?php
                    echo $station_count . ' ' . (($lang === 'de')
                        ? ($station_count === 1 ? 'Station' : 'Stationen')
                        : ($station_count === 1 ? 'station' : 'stations'));
                    ?>
                </span>
            </div>

            <?php if ($station_count === 0): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Keine Stationen im System vorhanden.'
                        : 'No stations found in the system.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Serial' : 'Serial'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Eigentümer' : 'Owner'; ?></th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($s = mysqli_fetch_assoc($stations_res)): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($s['pk_serialNumber']); ?>
                                </td>
                                <td style="min-width: 180px;">
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="pk_serialNumber"
                                               value="<?php echo htmlspecialchars($s['pk_serialNumber']); ?>">
                                        <input type="text" name="name"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($s['name']); ?>"
                                               placeholder="Optional">
                                </td>
                                <td style="min-width: 220px;">
                                        <input type="text" name="description"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($s['description']); ?>">
                                </td>
                                <td style="min-width: 200px;">
                                        <select name="fk_user_owns" class="form-select form-select-sm">
                                            <option value="">
                                                <?php echo ($lang === 'de') ? 'Kein Eigentümer' : 'No owner'; ?>
                                            </option>
                                            <?php foreach ($users as $u): ?>
                                                <option value="<?php echo htmlspecialchars($u['pk_username']); ?>"
                                                    <?php if (isset($s['fk_user_owns']) && $s['fk_user_owns'] === $u['pk_username']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($u['pk_username'] . ' - ' . $u['firstName'] . ' ' . $u['lastName']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                </td>
                                <td class="text-end">
                                        <button type="submit" name="save_station"
                                                class="btn btn-sm btn-primary-soft me-1">
                                            <?php echo ($lang === 'de') ? 'Speichern' : 'Save'; ?>
                                        </button>
                                    </form>

                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo ($lang === 'de')
                                              ? 'Station wirklich löschen?'
                                              : 'Really delete this station?'; ?>');">
                                        <input type="hidden" name="pk_serialNumber"
                                               value="<?php echo htmlspecialchars($s['pk_serialNumber']); ?>">
                                        <button type="submit" name="delete_station"
                                                class="btn btn-sm btn-danger">
                                            <?php echo ($lang === 'de') ? 'Löschen' : 'Delete'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>