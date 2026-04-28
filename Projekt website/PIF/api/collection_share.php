<?php
// collection_share.php
// Manage which users have access to a collection

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $_SESSION['pk_username'] ?? null;
if (!$currentUser) {
    header('Location: ../includes/logout.inc.php');
    exit;
}

$collectionId = intval($_GET['id'] ?? 0);
if ($collectionId <= 0) {
    header('Location: ../user/user_collections.php');
    exit;
}

// Check that user is owner
$sql = "SELECT name
        FROM collection
        WHERE pk_collection = ? AND fk_user_creates = ?";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'is', $collectionId, $currentUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$collection = mysqli_fetch_assoc($res);
mysqli_free_result($res);
mysqli_stmt_close($stmt);

if (!$collection) {
    echo "Collection not found or no access.";
    exit;
}

// Handle add/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $username = trim($_POST['username'] ?? '');

        if ($username !== '' && $username !== $currentUser) {
            // Check user exists
            $sql = "SELECT pk_username FROM user WHERE pk_username = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = mysqli_fetch_assoc($res);
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            if ($exists) {
                // Insert into hasaccess (ignore duplicates)
                $sql = "INSERT IGNORE INTO hasaccess (pkfk_collection, pkfk_user)
                        VALUES (?, ?)";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'is', $collectionId, $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        header('Location: collection_share.php?id=' . $collectionId);
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $username = trim($_POST['username'] ?? '');

        if ($username !== '') {
            $sql = "DELETE FROM hasaccess
                    WHERE pkfk_collection = ? AND pkfk_user = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $collectionId, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: collection_share.php?id=' . $collectionId);
        exit;
    }
}

// Load current access list
$access = [];
$sql = "SELECT h.pkfk_user
        FROM hasaccess h
        WHERE h.pkfk_collection = ?
        ORDER BY h.pkfk_user";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'i', $collectionId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $access[] = $row['pkfk_user'];
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Sammlung teilen' : 'Share collection'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/headers.css">
    <link rel="stylesheet" href="../css/sidebars.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top: 80px;">

    <h1 class="h4 mb-3">
        <?php echo ($lang === 'de') ? 'Sammlung teilen:' : 'Share collection:'; ?>
        <?php echo htmlspecialchars($collection['name']); ?>
    </h1>

    <a href="../user/user_collections.php" class="btn btn-secondary btn-sm mb-3">
        <?php echo ($lang === 'de') ? 'Zurück' : 'Back'; ?>
    </a>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Benutzer hinzufügen' : 'Add user'; ?>
            </h2>

            <form method="post" class="row gy-2 gx-2">
                <input type="hidden" name="action" value="add">
                <div class="col-md-4">
                    <label class="form-label">
                        <?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?>
                    </label>
                    <input type="text" name="username" class="form-control" placeholder="friend_username" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <?php echo ($lang === 'de') ? 'Hinzufügen' : 'Add'; ?>
                    </button>
                </div>
            </form>

            <p class="text-muted mt-2 mb-0">
                <?php echo ($lang === 'de')
                    ? 'Der Benutzer muss im System existieren. Du selbst bekommst automatisch Zugriff als Besitzer.'
                    : 'User must exist in the system. You as owner always have access automatically.'; ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Benutzer mit Zugriff' : 'Users with access'; ?>
            </h2>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?></th>
                            <th style="width: 120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($access) > 0): ?>
                        <?php foreach ($access as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo ($lang === 'de')
                                              ? 'Zugriff entfernen?'
                                              : 'Remove access?'; ?>');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($u); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <?php echo ($lang === 'de') ? 'Entfernen' : 'Remove'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Bisher wurden keine Benutzer freigeschaltet.'
                                    : 'No users have been granted access yet.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
</body>
</html>