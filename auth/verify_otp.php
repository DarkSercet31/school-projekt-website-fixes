<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';
$lang = $_SESSION['lang'] ?? 'en';

// Must have a pending OTP user in session
if (empty($_SESSION['otp_pending_user'])) {
    header('Location: login.php');
    exit;
}

$pendingUser = $_SESSION['otp_pending_user'];
$error       = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['otp_code'] ?? '');

    // Validate the code against email_tokens
    $stmt = $pdo->prepare(
        "SELECT token FROM email_tokens
         WHERE token = ? AND fk_username = ? AND type = 'otp_login'
           AND expires_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
    );
    $stmt->execute([$code, $pendingUser]);

    if ($stmt->fetch()) {
        // Delete used token
        $pdo->prepare("DELETE FROM email_tokens WHERE token = ? AND fk_username = ?")
            ->execute([$code, $pendingUser]);

        // Load full user data and set session
        $u = $pdo->prepare(
            "SELECT pk_username, role, firstName, lastName, email, mustChangePassword
             FROM user WHERE pk_username = ?"
        );
        $u->execute([$pendingUser]);
        $user = $u->fetch();

        $_SESSION['loggedin']    = true;
        $_SESSION['pk_username'] = $user['pk_username'];
        $_SESSION['username']    = $user['pk_username'];
        $_SESSION['role']        = $user['role'];
        $_SESSION['firstName']   = $user['firstName'];
        $_SESSION['lastName']    = $user['lastName'];
        $_SESSION['email']       = $user['email'];
        unset($_SESSION['otp_pending_user'], $_SESSION['otp_must_change_password']);

        // Force password change if flag is set
        if (!empty($user['mustChangePassword'])) {
            header('Location: ../includes/force_change_password.php');
            exit;
        }

        // Route by role
        header($user['role'] === 'Admin'
            ? 'Location: ../admin/dashboard.php'
            : 'Location: ../user/dashboard.php');
        exit;
    } else {
        $error = ($lang === 'de')
            ? 'Ungültiger oder abgelaufener Code.'
            : 'Invalid or expired code. Please try again.';
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Code eingeben' : 'Enter Code'; ?></title>
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
                    <?php echo ($lang === 'de') ? 'Bestätigungscode' : 'Verification Code'; ?>
                </h1>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Wir haben dir einen 6-stelligen Code per E-Mail geschickt.'
                        : 'We sent a 6-digit code to your email address.'; ?>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2 mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-4">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Einmalcode' : 'One-time code'; ?>
                </label>
                <input type="text" name="otp_code" class="form-control text-center"
                       maxlength="6" autocomplete="one-time-code"
                       style="font-size:1.5rem;letter-spacing:.3rem;" required>
            </div>
            <button type="submit" class="btn btn-primary-soft w-100">
                <?php echo ($lang === 'de') ? 'Bestätigen' : 'Verify'; ?>
            </button>
        </form>
        <div class="mt-3 text-center">
            <a href="login.php" class="small glass-card-sub">
                <?php echo ($lang === 'de') ? '← Zurück zum Login' : '← Back to login'; ?>
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
