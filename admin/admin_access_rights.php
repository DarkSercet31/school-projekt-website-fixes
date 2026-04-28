<?php
// admin_access_rights.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Only Admins
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';

// ---------- Load collections ----------
$col_res = mysqli_query(
    $link,
    "SELECT pk_collection, name
     FROM collection
     ORDER BY pk_collection DESC"
);
$collections = [];
while ($c = mysqli_fetch_assoc($col_res)) {
    $collections[] = $c;
}
mysqli_free_result($col_res);

// ---------- Load users ----------
$user_res = mysqli_query(
    $link,
    "SELECT pk_username, firstName, lastName
     FROM user
     ORDER BY pk_username"
);
$users = [];
while ($u = mysqli_fetch_assoc($user_res)) {
    $users[] = $u;
}
mysqli_free_result($user_res);

// ---------- Add access ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_access'])) {
    $cid  = (int)($_POST['collection'] ?? 0);
    $user = trim($_POST['user'] ?? '');

    if ($cid > 0 && $user !== '') {
        // prevent duplicates
        $stmt = mysqli_prepare(
            $link,
            "SELECT 1
             FROM hasaccess
             WHERE pkfk_collection = ? AND pkfk_user = ?"
        );
        mysqli_stmt_bind_param($stmt, 'is', $cid, $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($exists) {
            $message = ($lang === 'de')
                ? 'Zugriff besteht bereits.'
                : 'Access already exists.';
        } else {
            $stmt = mysqli_prepare(
                $link,
                "INSERT INTO hasaccess (pkfk_collection, pkfk_user)
                 VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'is', $cid, $user);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $message = ($lang === 'de')
                ? 'Zugriff hinzugefügt.'
                : 'Access added.';
        }
    } else {
        $message = ($lang === 'de') ? 'Ungültige Eingabe.' : 'Invalid input.';
    }
}

// ---------- Delete access ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_access'])) {
    $cid  = (int)($_POST['pkfk_collection'] ?? 0);
    $user = trim($_POST['pkfk_user'] ?? '');

    if ($cid > 0 && $user !== '') {
        $stmt = mysqli_prepare(
            $link,
            "DELETE FROM hasaccess
             WHERE pkfk_collection = ? AND pkfk_user = ?"
        );
        mysqli_stmt_bind_param($stmt, 'is', $cid, $user);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = ($lang === 'de')
            ? 'Zugriff entfernt.'
            : 'Access removed.';
    }
}

// ---------- Load all access rights ----------
$sql = "
    SELECT h.pkfk_collection,
           h.pkfk_user,
           c.name AS collection_name,
           u.firstName,
           u.lastName
    FROM hasaccess h
    JOIN collection c ON h.pkfk_collection = c.pk_collection
    JOIN user u       ON h.pkfk_user       = u.pk_username
    ORDER BY h.pkfk_collection DESC, h.pkfk_user ASC
";
$access_res = mysqli_query($link, $sql);
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <!-- Access Rights Card -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Zugriffsrechte (Admin)' : 'Access rights (Admin)'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Verwalten Sie Zugriffsrechte für Sammlungen.'
                            : 'Manage access rights for collections.'; ?>
                    </p>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-info mb-3 py-2">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card-body">
                <!-- Add access form -->
                <h2 class="h6 mb-3">
                    <?php echo ($lang === 'de') ? 'Zugriff hinzufügen' : 'Add access'; ?>
                </h2>
                <form method="post" action="admin_access_rights.php" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">
                            <?php echo ($lang === 'de') ? 'Sammlung' : 'Collection'; ?>
                        </label>
                        <select name="collection" class="form-select" required>
                            <option value="">-- <?php echo ($lang === 'de') ? 'Bitte wählen' : 'Please choose'; ?> --</option>
                            <?php foreach ($collections as $c): ?>
                                <option value="<?php echo htmlspecialchars($c['pk_collection']); ?>">
                                    <?php echo htmlspecialchars($c['pk_collection'].' - '.$c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">
                            <?php echo ($lang === 'de') ? 'Benutzer' : 'User'; ?>
                        </label>
                        <select name="user" class="form-select" required>
                            <option value="">-- <?php echo ($lang === 'de') ? 'Bitte wählen' : 'Please choose'; ?> --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo htmlspecialchars($u['pk_username']); ?>">
                                    <?php echo htmlspecialchars(
                                        $u['pk_username'].' ('.$u['firstName'].' '.$u['lastName'].')'
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="add_access" class="btn btn-primary-soft">
                            <?php echo ($lang === 'de') ? 'Hinzufügen' : 'Add'; ?>
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <!-- Existing access rights -->
                <h2 class="h6 mb-3">
                    <?php echo ($lang === 'de') ? 'Bestehende Zugriffsrechte' : 'Existing access rights'; ?>
                </h2>

                <?php if ($access_res && mysqli_num_rows($access_res) > 0): ?>
                    <div class="table-responsive">
                        <table class="table-glass align-middle">
                            <thead>
                                <tr>
                                    <th><?php echo ($lang === 'de') ? 'Sammlung' : 'Collection'; ?></th>
                                    <th><?php echo ($lang === 'de') ? 'Benutzer' : 'User'; ?></th>
                                    <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = mysqli_fetch_assoc($access_res)): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($row['pkfk_collection']); ?></span>
                                        <span class="text-muted"> - </span>
                                        <?php echo htmlspecialchars($row['collection_name']); ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="profile-initials-small me-2">
                                                <?php echo strtoupper(substr($row['pkfk_user'], 0, 1)); ?>
                                            </span>
                                            <div>
                                                <div class="fw-medium"><?php echo htmlspecialchars($row['pkfk_user']); ?></div>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($row['firstName'].' '.$row['lastName']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="admin_access_rights.php" class="d-inline">
                                            <input type="hidden" name="pkfk_collection"
                                                   value="<?php echo htmlspecialchars($row['pkfk_collection']); ?>">
                                            <input type="hidden" name="pkfk_user"
                                                   value="<?php echo htmlspecialchars($row['pkfk_user']); ?>">
                                            <button type="submit" name="delete_access"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('<?php echo ($lang === 'de') 
                                                        ? 'Zugriff wirklich entfernen?' 
                                                        : 'Really remove this access?'; ?>');">
                                                <?php echo ($lang === 'de') ? 'Entfernen' : 'Remove'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="text-muted mb-2">
                            <i class="fas fa-lock fa-2x mb-3"></i>
                            <p class="mb-0">
                                <?php echo ($lang === 'de')
                                    ? 'Keine Zugriffsrechte vorhanden.'
                                    : 'No access rights found.'; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </div>
</main>

<style>
    .profile-initials-small {
        background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    .table-glass {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table-glass thead th {
        background: rgba(255, 255, 255, 0.05);
        color: #94a3b8;
        font-weight: 500;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .table-glass tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .table-glass tbody tr:hover {
        background: rgba(255, 255, 255, 0.03);
    }
    
    .table-glass tbody td {
        padding: 1rem;
        vertical-align: middle;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>

<?php if ($access_res) { mysqli_free_result($access_res); } ?>