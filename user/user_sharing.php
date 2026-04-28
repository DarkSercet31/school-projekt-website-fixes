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

// Only show collections owned by the current user
$collectionId = (int)($_GET['id'] ?? 0);

$success = '';
$error   = '';

// Verify the collection belongs to this user
$collection = null;
if ($collectionId > 0) {
    $stmt = mysqli_prepare($link,
        "SELECT pk_collection, name FROM collection WHERE pk_collection = ? AND fk_user_creates = ?"
    );
    mysqli_stmt_bind_param($stmt, 'is', $collectionId, $username);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $collection = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

if ($collection === null) {
    header('Location: user_collections.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $targetUser = trim($_POST['target_user'] ?? '');

    // Grant access
    if ($action === 'grant' && $targetUser !== '') {
        // Check user exists
        $stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE pk_username = ?");
        mysqli_stmt_bind_param($stmt, 's', $targetUser);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if (!$exists) {
            $error = ($lang === 'de') ? 'Benutzer nicht gefunden.' : 'User not found.';
        } elseif ($targetUser === $username) {
            $error = ($lang === 'de')
                ? 'Du kannst dir selbst keinen Zugriff geben.'
                : 'You cannot share with yourself.';
        } else {
            $stmt = mysqli_prepare($link,
                "INSERT IGNORE INTO hasaccess (pkfk_collection, pkfk_user) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'is', $collectionId, $targetUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = ($lang === 'de') ? 'Zugriff gewährt.' : 'Access granted.';
        }
    }

    // Revoke access
    if ($action === 'revoke' && $targetUser !== '') {
        $stmt = mysqli_prepare($link,
            "DELETE FROM hasaccess WHERE pkfk_collection = ? AND pkfk_user = ?"
        );
        mysqli_stmt_bind_param($stmt, 'is', $collectionId, $targetUser);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success = ($lang === 'de') ? 'Zugriff entfernt.' : 'Access revoked.';
    }
}

// Load users who currently have access
$stmt = mysqli_prepare($link,
    "SELECT ha.pkfk_user, u.firstName, u.lastName
     FROM hasaccess ha
     JOIN user u ON ha.pkfk_user = u.pk_username
     WHERE ha.pkfk_collection = ?
     ORDER BY ha.pkfk_user"
);
mysqli_stmt_bind_param($stmt, 'i', $collectionId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$accessList = [];
while ($row = mysqli_fetch_assoc($res)) {
    $accessList[] = $row;
}
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <?php if ($success): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Sammlung teilen' : 'Share collection'; ?>:
                        <?php echo htmlspecialchars($collection['name']); ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Zugriff für andere Benutzer verwalten.'
                            : 'Manage access for other users.'; ?>
                    </p>
                </div>
                <a href="user_collections.php" class="btn btn-chip btn-sm">
                    <?php echo ($lang === 'de') ? '← Zurück' : '← Back'; ?>
                </a>
            </div>

            <!-- Grant access form -->
            <form method="post" class="d-flex gap-2 align-items-end mb-4">
                <input type="hidden" name="action" value="grant">
                <div class="flex-grow-1">
                    <label class="form-label">
                        <?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?>
                    </label>
                    <input type="text" name="target_user" class="form-control" required
                           placeholder="<?php echo ($lang === 'de') ? 'Benutzername eingeben' : 'Enter username'; ?>">
                </div>
                <button type="submit" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Zugriff gewähren' : 'Grant access'; ?>
                </button>
            </form>

            <!-- Current access list -->
            <h2 class="glass-card-title mb-2" style="font-size:.9rem;">
                <?php echo ($lang === 'de') ? 'Aktueller Zugriff' : 'Current access'; ?>
            </h2>
            <?php if (empty($accessList)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Noch kein Benutzer hat Zugriff.'
                        : 'No users have access yet.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accessList as $a): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($a['pkfk_user']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($a['firstName'] . ' ' . $a['lastName'])); ?></td>
                                    <td class="text-end">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="target_user"
                                                   value="<?php echo htmlspecialchars($a['pkfk_user']); ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <?php echo ($lang === 'de') ? 'Entfernen' : 'Revoke'; ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
