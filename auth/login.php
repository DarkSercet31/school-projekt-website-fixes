<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → go to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ../user/dashboard.php');
    exit;
}

// Correct path to lang.php (it's in /config/)
require __DIR__ . '/../config/lang.php';
$lang = $_SESSION['lang'] ?? 'en';

// Take and clear error from session (set by login.inc.php)
$error = '';
if (!empty($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/headers.css">  <!-- Already correct -->
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>  <!-- Already correct -->

<div class="container" style="max-width: 480px; margin-top: 80px;">
    <h1 class="h4 mb-3">
        <?php echo ($lang === 'de') ? 'Anmeldung' : 'Login'; ?>
    </h1>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger py-2">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="../includes/login.inc.php" method="post">  <!-- Already correct -->
        <div class="mb-3">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'Benutzername oder E-Mail' : 'Username or Email'; ?>
            </label>
            <input
                type="text"
                name="uid"
                class="form-control"
                required
                autocomplete="username"
                value="<?php echo isset($_POST['uid']) ? htmlspecialchars($_POST['uid']) : ''; ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'Passwort' : 'Password'; ?>
            </label>
            <input
                type="password"
                name="pwd"
                class="form-control"
                required
                autocomplete="current-password">
        </div>

        <button type="submit" name="login-submit" class="btn btn-primary w-100">
            <?php echo ($lang === 'de') ? 'Einloggen' : 'Login'; ?>
        </button>
        
        <div class="mt-3 text-center">
            <p class="mb-2">
                <?php echo ($lang === 'de') ? 'Noch kein Konto?' : 'Don\'t have an account?'; ?>
                <a href="register.php">
                    <?php echo ($lang === 'de') ? 'Registrieren' : 'Register'; ?>
                </a>
            </p>
            <p class="mb-0">
                <a href="forgot_password.php" class="small text-muted">
                    <?php echo ($lang === 'de') ? 'Passwort vergessen?' : 'Forgot password?'; ?>
                </a>
            </p>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>