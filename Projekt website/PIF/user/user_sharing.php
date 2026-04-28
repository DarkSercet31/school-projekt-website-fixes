<?php
// user_sharing.php
// Page for normal users to manage sharing of their own collections with friends

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';   // must create $link (mysqli)
require '../config/lang.php';            // optional, like on other pages

$lang = $_SESSION['lang'] ?? 'en';

// Must be logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$currentUser = $_SESSION['username'] ?? null;
if (!$currentUser) {
    header('Location: ../includes/logout.inc.php');
    exit;
}

// ---------------------------------------------------------------------
// Handle form actions: add share / remove share
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Protect against CSRF a bit via simple token if you want (optional)
    // if (!isset($_POST['token']) || $_POST['token'] !== ($_SESSION['token'] ?? '')) { ... }

    // Add sharing
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $collectionId = (int)($_POST['collection'] ?? 0);
        $friendUser   = trim($_POST['friend'] ?? '');

        if ($collectionId > 0 && $friendUser !== '') {

            // Check that this collection really belongs to current user
            $sql = "SELECT 1 FROM collection 
                    WHERE pk_collection = ? AND fk_user_creates = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'is', $collectionId, $currentUser);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $ownsCollection = mysqli_num_rows($res) > 0;
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            // Check that friend relation exists
            $sql = "SELECT 1 FROM isfriend 
                    WHERE (pk_fk_user_user = ? AND pk_fk_user_friend = ?)
                       OR (pk_fk_user_user = ? AND pk_fk_user_friend = ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ssss',
                $currentUser, $friendUser,
                $friendUser, $currentUser
            );
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $isFriend = mysqli_num_rows($res) > 0;
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            if ($ownsCollection && $isFriend) {
                // Insert into hasaccess if not existing
                $sql = "INSERT IGNORE INTO hasaccess (pk_fk_collection, pk_fk_user)
                        VALUES (?, ?)";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'is', $collectionId, $friendUser);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        header('Location: user_sharing.php');
        exit;
    }

    // Remove sharing
    if (isset($_POST['action']) && $_POST['action'] === 'remove') {
        $collectionId = (int)($_POST['collection'] ?? 0);
        $friendUser   = trim($_POST['friend'] ?? '');

        if ($collectionId > 0 && $friendUser !== '') {
            // Only remove if this collection belongs to current user
            $sql = "DELETE ha FROM hasaccess ha
                    JOIN collection c ON ha.pk_fk_collection = c.pk_collection
                    WHERE ha.pk_fk_collection = ? 
                      AND ha.pk_fk_user = ?
                      AND c.fk_user_creates = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'iss',
                $collectionId,
                $friendUser,
                $currentUser
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_sharing.php');
        exit;
    }
}

// ---------------------------------------------------------------------
// Load data for dropdowns and tables
// ---------------------------------------------------------------------

// Collections owned by current user
$sql = "SELECT pk_collection, name
        FROM collection
        WHERE fk_user_creates = ?
        ORDER BY name";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$ownedCollectionsRes = mysqli_stmt_get_result($stmt);

// Friends of current user
$sql = "SELECT DISTINCT u.pk_username, u.firstName, u.lastName
        FROM isfriend f
        JOIN user u 
          ON (u.pk_username = f.pk_fk_user_friend AND f.pk_fk_user_user = ?)
          OR (u.pk_username = f.pk_fk_user_user AND f.pk_fk_user_friend = ?)
        ORDER BY u.pk_username";
$stmt2 = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt2, 'ss', $currentUser, $currentUser);
mysqli_stmt_execute($stmt2);
$friendsRes = mysqli_stmt_get_result($stmt2);

// Collections I share with others
$sql = "SELECT ha.pk_fk_collection    AS collection_id,
               c.name                 AS collection_name,
               ha.pk_fk_user          AS shared_with,
               u.firstName,
               u.lastName
        FROM hasaccess ha
        JOIN collection c ON ha.pk_fk_collection = c.pk_collection
        JOIN user u       ON ha.pk_fk_user = u.pk_username
        WHERE c.fk_user_creates = ?
        ORDER BY c.name, ha.pk_fk_user";
$stmt3 = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt3, 's', $currentUser);
mysqli_stmt_execute($stmt3);
$sharedByMeRes = mysqli_stmt_get_result($stmt3);

// Collections shared with me
$sql = "SELECT ha.pk_fk_collection    AS collection_id,
               c.name                 AS collection_name,
               c.fk_user_creates      AS owner_username,
               u.firstName,
               u.lastName
        FROM hasaccess ha
        JOIN collection c ON ha.pk_fk_collection = c.pk_collection
        JOIN user u       ON c.fk_user_creates = u.pk_username
        WHERE ha.pk_fk_user = ?
        ORDER BY c.name";
$stmt4 = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt4, 's', $currentUser);
mysqli_stmt_execute($stmt4);
$sharedWithMeRes = mysqli_stmt_get_result($stmt4);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Teilen' : 'Sharing'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/headers.css">
    <link rel="stylesheet" href="../css/sidebars.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="container" style="margin-top: 80px;">

    <h1 class="h4 mb-4">
        <?php echo ($lang === 'de') ? 'Sammlungen teilen' : 'Share collections'; ?>
    </h1>

    <!-- Add sharing --------------------------------------------------- -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Zugriff hinzufügen' : 'Add access'; ?>
            </h2>

            <form method="post" class="row gy-2 gx-2 align-items-end">
                <input type="hidden" name="action" value="add">

                <div class="col-md-5">
                    <label class="form-label">
                        <?php echo ($lang === 'de') ? 'Eigene Sammlung' : 'Your collection'; ?>
                    </label>
                    <select name="collection" class="form-select" required>
                        <option value=""><?php echo ($lang === 'de') ? '--' : '--'; ?></option>
                        <?php while ($row = mysqli_fetch_assoc($ownedCollectionsRes)): ?>
                            <option value="<?php echo (int)$row['pk_collection']; ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label">
                        <?php echo ($lang === 'de') ? 'Freund' : 'Friend'; ?>
                    </label>
                    <select name="friend" class="form-select" required>
                        <option value=""><?php echo ($lang === 'de') ? '--' : '--'; ?></option>
                        <?php while ($f = mysqli_fetch_assoc($friendsRes)): ?>
                            <option value="<?php echo htmlspecialchars($f['pk_username']); ?>">
                                <?php
                                $fullName = trim($f['firstName'] . ' ' . $f['lastName']);
                                echo htmlspecialchars($f['pk_username'] . ' (' . $fullName . ')');
                                ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <?php echo ($lang === 'de') ? 'Hinzufügen' : 'Add'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Collections I share with others ------------------------------- -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Von mir geteilte Sammlungen' : 'Collections shared by me'; ?>
            </h2>

            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Sammlung' : 'Collection'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Freund' : 'Friend'; ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($sharedByMeRes) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($sharedByMeRes)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['collection_name']); ?></td>
                                <td>
                                    <?php
                                    $fullName = trim($row['firstName'] . ' ' . $row['lastName']);
                                    echo htmlspecialchars($row['shared_with'] . ' (' . $fullName . ')');
                                    ?>
                                </td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="collection" value="<?php echo (int)$row['collection_id']; ?>">
                                        <input type="hidden" name="friend" value="<?php echo htmlspecialchars($row['shared_with']); ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <?php echo ($lang === 'de') ? 'Entfernen' : 'Remove'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Du teilst aktuell keine Sammlungen.'
                                    : 'You are not sharing any collections yet.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Collections shared with me ------------------------------------ -->
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">
                <?php echo ($lang === 'de') ? 'Mit mir geteilte Sammlungen' : 'Collections shared with me'; ?>
            </h2>

            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Sammlung' : 'Collection'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Besitzer' : 'Owner'; ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($sharedWithMeRes) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($sharedWithMeRes)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['collection_name']); ?></td>
                                <td>
                                    <?php
                                    $fullName = trim($row['firstName'] . ' ' . $row['lastName']);
                                    echo htmlspecialchars($row['owner_username'] . ' (' . $fullName . ')');
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Noch keine Sammlungen mit dir geteilt.'
                                    : 'No collections shared with you yet.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php
mysqli_free_result($ownedCollectionsRes);
mysqli_free_result($friendsRes);
mysqli_free_result($sharedByMeRes);
mysqli_free_result($sharedWithMeRes);
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt2);
mysqli_stmt_close($stmt3);
mysqli_stmt_close($stmt4);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>