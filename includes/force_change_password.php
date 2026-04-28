<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Path fixed: db_connection is in includes/, not config/
require __DIR__ . '/db_connection.php';
require __DIR__ . '/../config/lang.php';

// Must be logged in to reach this page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['pk_username'] ?? '';
$lang     = $_SESSION['lang'] ?? 'en';
$message  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd1 = $_POST['new_password']        ?? '';
    $pwd2 = $_POST['new_password_repeat'] ?? '';

    if ($pwd1 === '' || $pwd2 === '') {
        $message = ($lang === 'de')
            ? 'Bitte das neue Passwort zweimal eingeben.'
            : 'Please enter the new password twice.';
    } elseif ($pwd1 !== $pwd2) {
        $message = ($lang === 'de')
            ? 'Die Passwörter stimmen nicht überein.'
            : 'The passwords do not match.';
    } elseif (strlen($pwd1) < 8) {
        $message = ($lang === 'de')
            ? 'Das Passwort muss mindestens 8 Zeichen lang sein.'
            : 'Password must be at least 8 characters.';
    } else {
        // Hash and save the new password, clear the force-change flag
        $hash = password_hash($pwd1, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($link,
            "UPDATE user SET password = ?, mustChangePassword = 0 WHERE pk_username = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Route admin to admin dashboard, user to user dashboard
        if ($_SESSION['role'] === 'Admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../user/dashboard.php');
        }
        exit;
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Passwort ändern' : 'Change password'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/layout.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<div class="container main-shell" style="max-width: 480px;">
    <div class="glass-card">
        <div class="glass-card-header">
            <div>
                <h1 class="glass-card-title mb-0">
                    <?php echo ($lang === 'de') ? 'Neues Passwort setzen' : 'Set a new password'; ?>
                </h1>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Du verwendest ein temporäres Passwort. Bitte wähle jetzt ein persönliches Passwort.'
                        : 'You are using a temporary password. Please choose a new personal password now.'; ?>
                </p>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="../includes/force_change_password.php">
            <div class="mb-3">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Neues Passwort' : 'New password'; ?>
                </label>
                <input type="password" name="new_password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-4">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Passwort wiederholen' : 'Repeat new password'; ?>
                </label>
                <input type="password" name="new_password_repeat" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary-soft w-100">
                <?php echo ($lang === 'de') ? 'Passwort speichern' : 'Save new password'; ?>
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
