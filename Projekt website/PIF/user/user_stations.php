<?php
// user_stations.php – My stations

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Must be logged in
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
// Register / update / delete / pause station
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Register / Claim station
    if ($action === 'register') {
        $serial      = trim($_POST['serial'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($serial !== '') {
            if ($name === '') {
                $name = $serial;
            }

            // check if station exists
            $sql  = "SELECT pk_serialNumber FROM station WHERE pk_serialNumber = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 's', $serial);
            mysqli_stmt_execute($stmt);
            $res    = mysqli_stmt_get_result($stmt);
            $exists = mysqli_num_rows($res) > 0;
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            if ($exists) {
                // set owner + update data
                $sql  = "UPDATE station
                        SET name = ?, description = ?, fk_user_owns = ?
                        WHERE pk_serialNumber = ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $currentUser, $serial);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                // create new station
                $sql  = "INSERT INTO station (pk_serialNumber, name, description, fk_user_owns, status)
                        VALUES (?, ?, ?, ?, 'Active')";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'ssss', $serial, $name, $description, $currentUser);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        header('Location: user_stations.php');
        exit;
    }

    // Update station
    if ($action === 'update') {
        $serial      = trim($_POST['serial'] ?? '');
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($serial !== '') {
            if ($name === '') {
                $name = $serial;
            }

            $sql  = "UPDATE station
                    SET name = ?, description = ?
                    WHERE pk_serialNumber = ? AND fk_user_owns = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ssss', $name, $description, $serial, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_stations.php');
        exit;
    }

    // Delete station
    if ($action === 'delete') {
        $serial = trim($_POST['serial'] ?? '');
        if ($serial !== '') {
            $sql  = "DELETE FROM station WHERE pk_serialNumber = ? AND fk_user_owns = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $serial, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: user_stations.php');
        exit;
    }

    // Pause / Resume
    if ($action === 'pause' || $action === 'resume') {
        $serial = trim($_POST['serial'] ?? '');
        if ($serial !== '') {
            $newStatus = ($action === 'pause') ? 'Paused' : 'Active';
            $sql       = "UPDATE station
                         SET status = ?
                         WHERE pk_serialNumber = ? AND fk_user_owns = ?";
            $stmt      = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $newStatus, $serial, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: user_stations.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// Load current user's stations
// -----------------------------------------------------------------------------
$sql  = "SELECT pk_serialNumber, name, description, status
        FROM station
        WHERE fk_user_owns = ?
        ORDER BY pk_serialNumber";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$stationsRes = mysqli_stmt_get_result($stmt);
$stations    = [];
while ($row = mysqli_fetch_assoc($stationsRes)) {
    $stations[] = $row;
}
mysqli_free_result($stationsRes);
mysqli_stmt_close($stmt);

$lang = $_SESSION['lang'] ?? 'en';
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My stations'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Registriere neue Stationen und verwalte bestehende.'
                            : 'Register new stations and manage existing ones.'; ?>
                    </p>
                </div>
            </div>

            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="register">
                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Seriennummer' : 'Serial number'; ?>
                    </label>
                    <input type="text" name="serial" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Name' : 'Name'; ?>
                    </label>
                    <input type="text" name="name" class="form-control" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?>
                    </label>
                    <input type="text" name="description" class="form-control">
                </div>
                <div class="col-md-2 d-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary-soft w-100">
                        <?php echo ($lang === 'de') ? 'Registrieren' : 'Register'; ?>
                    </button>
                </div>
            </form>

            <p class="glass-card-sub mt-3 mb-0">
                <?php echo ($lang === 'de')
                    ? 'Name ist optional. Wenn leer, wird die Seriennummer als Name verwendet.'
                    : 'Name is optional. If left empty, the serial number will be used as the name.'; ?>
            </p>
        </section>

        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine registrierten Stationen' : 'My registered stations'; ?>
                    </h2>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Bearbeite Namen, Beschreibung und Status deiner Stationen.'
                            : 'Edit name, description and status of your stations.'; ?>
                    </p>
                </div>
                <span class="glass-card-sub">
                    <?php
                    $count = count($stations);
                    echo $count . ' ' . (($lang === 'de')
                        ? ($count === 1 ? 'Station' : 'Stationen')
                        : ($count === 1 ? 'station' : 'stations'));
                    ?>
                </span>
            </div>

            <?php if (empty($stations)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Sie haben noch keine Station registriert.'
                        : 'You have not registered any stations yet.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Seriennummer' : 'Serial'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Status' : 'Status'; ?></th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stations as $st): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($st['pk_serialNumber']); ?></td>
                                <td style="min-width: 180px;">
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="serial"
                                               value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>">
                                        <input type="text" name="name"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($st['name']); ?>">
                                </td>
                                <td style="min-width: 220px;">
                                        <input type="text" name="description"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($st['description']); ?>">
                                </td>
                                <td>
                                    <?php
                                    $status = $st['status'] ?? 'Active';
                                    $badgeClass = ($status === 'Active') ? 'bg-success'
                                        : (($status === 'Paused') ? 'bg-warning text-dark' : 'bg-secondary');
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                        <button type="submit" class="btn btn-sm btn-primary-soft me-1">
                                            <?php echo ($lang === 'de') ? 'Speichern' : 'Save'; ?>
                                        </button>
                                    </form>

                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="serial"
                                               value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>">
                                        <input type="hidden" name="action"
                                               value="<?php echo ($status === 'Active') ? 'pause' : 'resume'; ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-chip me-1">
                                            <?php echo ($status === 'Active')
                                                ? (($lang === 'de') ? 'Pausieren' : 'Pause')
                                                : (($lang === 'de') ? 'Fortsetzen' : 'Resume'); ?>
                                        </button>
                                    </form>

                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo ($lang === 'de')
                                              ? 'Station wirklich löschen?'
                                              : 'Really delete this station?'; ?>');">
                                        <input type="hidden" name="serial"
                                               value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <?php echo ($lang === 'de') ? 'Löschen' : 'Delete'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>