<?php
// user_collections.php – My data collections

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
// Load user stations for dropdown
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
// Actions: create / update / delete collection
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Delete collection ----------------------------------------------------
    if ($action === 'delete') {
        $colId = isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0;
        if ($colId > 0) {
            $sql  = "DELETE FROM collection
                     WHERE pk_collection = ? AND fk_user_creates = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $colId, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header('Location: user_collections.php');
        exit;
    }

    // Update collection ----------------------------------------------------
    if ($action === 'update') {
        $colId       = isset($_POST['collection_id']) ? (int)$_POST['collection_id'] : 0;
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($colId > 0 && $name !== '') {
            $sql  = "UPDATE collection
                     SET name = ?, description = ?
                     WHERE pk_collection = ? AND fk_user_creates = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ssis', $name, $description, $colId, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_collections.php');
        exit;
    }

    // Create collection ----------------------------------------------------
    if ($action === 'create') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $stationSel  = $_POST['station'] ?? '';
        $from        = $_POST['from'] ?? '';
        $to          = $_POST['to'] ?? '';

        if ($name !== '' && $stationSel !== '' && $from !== '' && $to !== '') {
            $fromSql = date('Y-m-d H:i:s', strtotime($from));
            $toSql   = date('Y-m-d H:i:s', strtotime($to));

            $sql  = "INSERT INTO collection (name, description, fk_user_creates)
                     VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'sss', $name, $description, $currentUser);
            mysqli_stmt_execute($stmt);
            $newCollectionId = mysqli_insert_id($link);
            mysqli_stmt_close($stmt);

            if ($newCollectionId > 0) {
                $sql  = "SELECT pk_measurement
                         FROM measurement
                         WHERE fk_station_records = ?
                           AND timestamp BETWEEN ? AND ?";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'sss', $stationSel, $fromSql, $toSql);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);

                $sqlInsert = "INSERT INTO contains (pkfk_measurement, pkfk_collection)
                              VALUES (?, ?)";
                $stmtIns   = mysqli_prepare($link, $sqlInsert);

                while ($row = mysqli_fetch_assoc($res)) {
                    $mid = (int)$row['pk_measurement'];
                    mysqli_stmt_bind_param($stmtIns, 'ii', $mid, $newCollectionId);
                    mysqli_stmt_execute($stmtIns);
                }

                mysqli_free_result($res);
                mysqli_stmt_close($stmt);
                mysqli_stmt_close($stmtIns);
            }
        }

        header('Location: user_collections.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// Load user collections with measurement count
// -----------------------------------------------------------------------------
$collections = [];
$sql  = "SELECT c.pk_collection,
                c.name,
                c.description,
                COUNT(ct.pkfk_measurement) AS cnt
         FROM collection c
         LEFT JOIN contains ct
            ON c.pk_collection = ct.pkfk_collection
         WHERE c.fk_user_creates = ?
         GROUP BY c.pk_collection, c.name, c.description
         ORDER BY c.pk_collection DESC";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $collections[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

$lang = $_SESSION['lang'] ?? 'en';
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <!-- Card 1: Create collection -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Datensammlungen' : 'My data collections'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Erstelle Sammlungen aus Messwerten in einem bestimmten Zeitraum.'
                            : 'Create collections from measurements over a selected time range.'; ?>
                    </p>
                </div>
            </div>

            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="create">

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Sammlungsname' : 'Collection name'; ?>
                    </label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?>
                    </label>
                    <input type="text" name="description" class="form-control">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Station' : 'Station'; ?>
                    </label>
                    <select name="station" class="form-select" required>
                        <option value="">
                            <?php echo ($lang === 'de') ? 'Bitte wählen...' : 'Please choose...'; ?>
                        </option>
                        <?php foreach ($stations as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['pk_serialNumber']); ?>">
                                <?php
                                $label = $st['pk_serialNumber'] . ' (' . $st['name'] . ')';
                                echo htmlspecialchars($label);
                                ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3"></div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Von' : 'From'; ?>
                    </label>
                    <input type="datetime-local" name="from" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Bis' : 'To'; ?>
                    </label>
                    <input type="datetime-local" name="to" class="form-control" required>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary-soft w-100">
                        <?php echo ($lang === 'de') ? 'Sammlung erstellen' : 'Create collection'; ?>
                    </button>
                </div>

                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="button" id="btnLast24h" class="btn btn-chip flex-fill">24h</button>
                    <button type="button" id="btnLast7d" class="btn btn-chip flex-fill">7d</button>
                </div>
            </form>

            <p class="glass-card-sub mt-3 mb-0">
                <?php echo ($lang === 'de')
                    ? 'Alle Messwerte der ausgewählten Station im gewählten Zeitraum werden der Sammlung hinzugefügt.'
                    : 'All measurements of the selected station in the chosen period are added to the collection.'; ?>
            </p>
        </section>

        <!-- Card 2: My collections -->
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Sammlungen' : 'My collections'; ?>
                    </h2>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Verwalte bestehende Sammlungen, bearbeite sie oder lösche sie.'
                            : 'Manage existing collections, edit them or delete them.'; ?>
                    </p>
                </div>
                <span class="glass-card-sub">
                    <?php
                    $count = count($collections);
                    echo $count . ' ' . (($lang === 'de')
                        ? ($count === 1 ? 'Sammlung' : 'Sammlungen')
                        : ($count === 1 ? 'collection' : 'collections'));
                    ?>
                </span>
            </div>

            <?php if (empty($collections)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Sie haben noch keine Sammlungen erstellt.'
                        : 'You have not created any collections yet.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?></th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($collections as $col): ?>
                            <tr>
                                <td><?php echo (int)$col['pk_collection']; ?></td>
                                <td style="min-width: 180px;">
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="collection_id"
                                               value="<?php echo (int)$col['pk_collection']; ?>">
                                        <input type="text" name="name"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($col['name']); ?>"
                                               required>
                                </td>
                                <td style="min-width: 220px;">
                                        <input type="text" name="description"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($col['description']); ?>">
                                </td>
                                <td><?php echo (int)$col['cnt']; ?></td>
                                <td class="text-end">
                                        <button type="submit" class="btn btn-sm btn-primary-soft me-1">
                                            <?php echo ($lang === 'de') ? 'Speichern' : 'Save'; ?>
                                        </button>
                                    </form>

                                    <a href="../api/collection_view.php?id=<?php echo (int)$col['pk_collection']; ?>"
                                       class="btn btn-sm btn-chip me-1">
                                        <?php echo ($lang === 'de') ? 'Ansehen' : 'See content'; ?>
                                    </a>

                                    <a href="../api/collection_share.php?id=<?php echo (int)$col['pk_collection']; ?>"
                                       class="btn btn-sm btn-chip me-1">
                                        <?php echo ($lang === 'de') ? 'Teilen' : 'Share'; ?>
                                    </a>

                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo ($lang === 'de')
                                              ? 'Sammlung wirklich löschen?'
                                              : 'Really delete this collection?'; ?>');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="collection_id"
                                               value="<?php echo (int)$col['pk_collection']; ?>">
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

<script>
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