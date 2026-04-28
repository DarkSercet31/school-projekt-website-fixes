<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';
$lang  = $_SESSION['lang'] ?? 'en';
$token = trim($_GET['token'] ?? '');

$tokenRow = null;
$error    = '';
$done     = false;

// Validate token on every request
if ($token !== '') {
    $stmt = $pdo->prepare(
        "SELECT fk_username FROM email_tokens
         WHERE token = ? AND type = 'reset'
           AND expires_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();
}

if (!$tokenRow && !$done) {
    $error = ($lang === 'de')
        ? 'Ungültiger oder abgelaufener Reset-Link.'
        : 'Invalid or expired reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
    $pwd1 = $_POST['password']         ?? '';
    $pwd2 = $_POST['confirm_password'] ?? '';

    if ($pwd1 === '' || $pwd2 === '') {
        $error = ($lang === 'de') ? 'Bitte beide Felder ausfüllen.' : 'Please fill in both fields.';
    } elseif ($pwd1 !== $pwd2) {
        $error = ($lang === 'de') ? 'Passwörter stimmen nicht überein.' : 'Passwords do not match.';
    } elseif (strlen($pwd1) < 8) {
        $error = ($lang === 'de') ? 'Mindestens 8 Zeichen.' : 'At least 8 characters required.';
    } else {
        // Update password and delete token
        $hash = password_hash($pwd1, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE user SET password = ?, mustChangePassword = 0 WHERE pk_username = ?")
            ->execute([$hash, $tokenRow['fk_username']]);
        $pdo->prepare("DELETE FROM email_tokens WHERE token = ?")
            ->execute([$token]);

        $_SESSION['login_error'] = ($lang === 'de')
            ? 'Passwort geändert. Du kannst dich jetzt einloggen.'
            : 'Password changed. You can now log in.';
        header('Location: login.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Neues Passwort' : 'New Password'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/layout.css">
</head>
<body>
<div class="container main-shell" style="max-width:420px;">
    <div class="glass-card">
        <div class="glass-card-header">
            <div>
                <h1 class="glass-card-title mb-0">
                    <?php echo ($lang === 'de') ? 'Neues Passwort setzen' : 'Set New Password'; ?>
                </h1>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($tokenRow): ?>
        <form method="post" action="reset_password.php?token=<?php echo urlencode($token); ?>">
            <div class="mb-3">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Neues Passwort' : 'New password'; ?>
                </label>
                <input type="password" name="password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <div class="mb-4">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Wiederholen' : 'Confirm password'; ?>
                </label>
                <input type="password" name="confirm_password" class="form-control"
                       required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary-soft w-100">
                <?php echo ($lang === 'de') ? 'Speichern' : 'Save password'; ?>
            </button>
        </form>
        <?php else: ?>
            <a href="forgot_password.php" class="btn btn-primary-soft">
                <?php echo ($lang === 'de') ? 'Neuen Link anfordern' : 'Request new link'; ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
