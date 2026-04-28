<?php
// force_change_password.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../config/db_connection.php';  // Changed from 'db_connection.php'
require '../config/lang.php';          // Changed from 'lang.php'

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');  // Changed from 'login.php'
    exit;
}

$username = $_SESSION['pk_username'] ?? '';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pwd1 = $_POST['new_password'] ?? '';
    $pwd2 = $_POST['new_password_repeat'] ?? '';

    if ($pwd1 === '' || $pwd2 === '') {
        $message = 'Please enter the new password twice.';
    } elseif ($pwd1 !== $pwd2) {
        $message = 'The passwords do not match.';
    } elseif (strlen($pwd1) < 8) {
        $message = 'Password must be at least 8 characters.';
    } else {
        $hash = password_hash($pwd1, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $link,
            "UPDATE user SET password = ?, mustChangePassword = 0 WHERE pk_username = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ss', $hash, $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header('Location: ../user/dashboard.php?pwchanged=1');  // Changed from 'dashboard.php'
        exit;
    }
}

$lang = $_SESSION['lang'] ?? 'en';
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title>Change password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/headers.css">  <!-- Changed from 'headers.css' -->
</head>
<body>

<?php include '../includes/header.php'; ?>  <!-- Changed from 'header.php' -->

<div class="container" style="max-width: 480px; margin-top: 80px;">
    <h1 class="h4 mb-3">Set a new password</h1>
    <p class="text-muted">You are using a temporary password. Please choose a new personal password now.</p>

    <?php if ($message !== ''): ?>
        <div class="alert alert-danger py-2"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="post" action="force_change_password.php">
        <div class="mb-3">
            <label class="form-label">New password</label>
            <input type="password" name="new_password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Repeat new password</label>
            <input type="password" name="new_password_repeat" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Save new password</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>