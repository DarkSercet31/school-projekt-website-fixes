<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';
$lang = $_SESSION['lang'] ?? 'en';

$success = false;
$message = '';
$token   = trim($_GET['token'] ?? '');

if ($token !== '') {
    // Look up valid, unexpired verify token
    $stmt = $pdo->prepare(
        "SELECT fk_username FROM email_tokens
         WHERE token = ? AND type = 'verify' AND expires_at > NOW()"
    );
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        // Mark user as verified
        $pdo->prepare("UPDATE user SET status = 'verified' WHERE pk_username = ?")
            ->execute([$row['fk_username']]);
        // Delete the used token
        $pdo->prepare("DELETE FROM email_tokens WHERE token = ?")
            ->execute([$token]);
        $success = true;
        $message = ($lang === 'de')
            ? 'E-Mail erfolgreich bestätigt! Du kannst dich jetzt einloggen.'
            : 'Email verified! You can now log in.';
    } else {
        $message = ($lang === 'de')
            ? 'Ungültiger oder abgelaufener Bestätigungslink.'
            : 'Invalid or expired verification link.';
    }
} else {
    $message = ($lang === 'de') ? 'Kein Token angegeben.' : 'No token provided.';
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'E-Mail bestätigen' : 'Verify Email'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/layout.css">
</head>
<body>
<div class="container main-shell" style="max-width:480px;">
    <div class="glass-card text-center">
        <div class="mb-3" style="font-size:3rem;">
            <?php echo $success ? '✅' : '❌'; ?>
        </div>
        <h1 class="glass-card-title mb-2">
            <?php echo $success
                ? ($lang === 'de' ? 'Bestätigt!' : 'Verified!')
                : ($lang === 'de' ? 'Fehler' : 'Error'); ?>
        </h1>
        <p class="glass-card-sub mb-4"><?php echo htmlspecialchars($message); ?></p>
        <a href="login.php" class="btn btn-primary-soft">
            <?php echo ($lang === 'de') ? 'Zum Login' : 'Go to Login'; ?>
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
