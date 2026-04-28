<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Only Admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: auth/login.php');
    exit;
}

// ---------------- USERS ----------------
$users = [];
$res = mysqli_query($link, "SELECT pk_username, firstName, lastName FROM user ORDER BY pk_username");
while ($row = mysqli_fetch_assoc($res)) {
    $users[] = $row;
}

// ---------------- STATIONS ----------------
$stations = [];
$res = mysqli_query($link, "SELECT pk_serialNumber, name FROM station ORDER BY pk_serialNumber");
while ($row = mysqli_fetch_assoc($res)) {
    $stations[] = $row;
}

// ---------------- ACTIONS ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE COLLECTION WITH MEASUREMENTS
    if ($action === 'create') {
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $creator     = trim($_POST['creator'] ?? '');
        $station     = trim($_POST['station'] ?? '');
        $from        = $_POST['from'] ?? '';
        $to          = $_POST['to'] ?? '';

        if ($name && $creator && $station && $from && $to) {

            $fromSql = date('Y-m-d H:i:s', strtotime($from));
            $toSql   = date('Y-m-d H:i:s', strtotime($to));

            // create collection
            $stmt = mysqli_prepare($link,
                "INSERT INTO collection (name, description, fk_user_creates)
                 VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'sss', $name, $description, $creator);
            mysqli_stmt_execute($stmt);

            $collectionId = mysqli_insert_id($link);
            mysqli_stmt_close($stmt);

            // add measurements
            $stmt = mysqli_prepare($link,
                "SELECT pk_measurement FROM measurement
                 WHERE fk_station_records = ? AND timestamp BETWEEN ? AND ?");
            mysqli_stmt_bind_param($stmt, 'sss', $station, $fromSql, $toSql);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            $stmtIns = mysqli_prepare($link,
                "INSERT INTO contains (pkfk_measurement, pkfk_collection) VALUES (?, ?)");

            while ($row = mysqli_fetch_assoc($res)) {
                $mid = (int)$row['pk_measurement'];
                mysqli_stmt_bind_param($stmtIns, 'ii', $mid, $collectionId);
                mysqli_stmt_execute($stmtIns);
            }

            mysqli_stmt_close($stmt);
            mysqli_stmt_close($stmtIns);
        }

        header('Location: admin_collections.php');
        exit;
    }

    // UPDATE
    if ($action === 'update') {
        $id   = (int)$_POST['collection_id'];
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $creator = trim($_POST['creator']);

        $stmt = mysqli_prepare($link,
            "UPDATE collection SET name=?, description=?, fk_user_creates=? WHERE pk_collection=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $desc, $creator, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: admin_collections.php');
        exit;
    }

    // DELETE
    if ($action === 'delete') {
        $id = (int)$_POST['collection_id'];

        $stmt = mysqli_prepare($link, "DELETE FROM collection WHERE pk_collection=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: admin_collections.php');
        exit;
    }
}

// ---------------- LOAD COLLECTIONS ----------------
$collections = [];
$sql = "SELECT c.*, COUNT(ct.pkfk_measurement) AS cnt
        FROM collection c
        LEFT JOIN contains ct ON c.pk_collection = ct.pkfk_collection
        GROUP BY c.pk_collection
        ORDER BY c.pk_collection DESC";

$res = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($res)) {
    $collections[] = $row;
}
?>

<?php include '../includes/header.php'; ?>

<main class="container mt-4">

<h2>Collections (Admin)</h2>

<!-- CREATE -->
<form method="post" class="row g-2 mb-4">
    <input type="hidden" name="action" value="create">

    <div class="col-md-3">
        <input name="name" class="form-control" placeholder="Name" required>
    </div>

    <div class="col-md-3">
        <select name="creator" class="form-select" required>
            <option value="">User</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['pk_username'] ?>">
                    <?= $u['pk_username'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <select name="station" class="form-select" required>
            <option value="">Station</option>
            <?php foreach ($stations as $s): ?>
                <option value="<?= $s['pk_serialNumber'] ?>">
                    <?= $s['pk_serialNumber'] ?> (<?= $s['name'] ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <input name="description" class="form-control" placeholder="Description">
    </div>

    <div class="col-md-3">
        <input type="datetime-local" name="from" class="form-control" required>
    </div>

    <div class="col-md-3">
        <input type="datetime-local" name="to" class="form-control" required>
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary w-100">Create</button>
    </div>
</form>

<!-- TABLE -->
<table class="table table-dark table-striped">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Description</th>
    <th>Creator</th>
    <th>Measurements</th>
    <th>Actions</th>
</tr>

<?php foreach ($collections as $c): ?>
<tr>
<form method="post">
    <td><?= $c['pk_collection'] ?></td>

    <td><input name="name" value="<?= htmlspecialchars($c['name']) ?>"></td>
    <td><input name="description" value="<?= htmlspecialchars($c['description']) ?>"></td>

    <td>
        <select name="creator">
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['pk_username'] ?>"
                    <?= $u['pk_username']==$c['fk_user_creates']?'selected':'' ?>>
                    <?= $u['pk_username'] ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>

    <td><?= $c['cnt'] ?></td>

    <td>
        <input type="hidden" name="collection_id" value="<?= $c['pk_collection'] ?>">

        <button name="action" value="update" class="btn btn-success btn-sm">Save</button>

        <a href="../api/collection_view.php?id=<?= $c['pk_collection'] ?>"
           class="btn btn-info btn-sm">View</a>

        <a href="../api/collection_share.php?id=<?= $c['pk_collection'] ?>"
           class="btn btn-warning btn-sm">Share</a>

        <button name="action" value="delete"
                class="btn btn-danger btn-sm"
                onclick="return confirm('Delete?')">
            Delete
        </button>
    </td>
</form>
</tr>
<?php endforeach; ?>
</table>

</main>