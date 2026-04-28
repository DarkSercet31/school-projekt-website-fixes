<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → go to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: ../user/dashboard.php');
    exit;
}

require __DIR__ . '/../config/lang.php';
$lang = $_SESSION['lang'] ?? 'en';

// Take and clear any registration errors from session
$error = '';
if (!empty($_SESSION['register_error'])) {
    $error = $_SESSION['register_error'];
    unset($_SESSION['register_error']);
}

// Keep form data if there was an error
$username = $_SESSION['register_username'] ?? '';
$firstName = $_SESSION['register_firstname'] ?? '';
$lastName = $_SESSION['register_lastname'] ?? '';
$email = $_SESSION['register_email'] ?? '';

unset(
    $_SESSION['register_username'],
    $_SESSION['register_firstname'],
    $_SESSION['register_lastname'],
    $_SESSION['register_email']
);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo ($lang === 'de') ? 'Registrierung' : 'Register'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/headers.css">
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container" style="max-width: 480px; margin-top: 80px;">
    <h1 class="h4 mb-3">
        <?php echo ($lang === 'de') ? 'Registrierung' : 'Register'; ?>
    </h1>
    <p class="text-muted mb-4">
        <?php echo ($lang === 'de') ? 'Erstelle ein neues Benutzerkonto' : 'Create a new user account'; ?>
    </p>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger py-2">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="../includes/register.inc.php" method="post">
        <div class="mb-3">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?>
            </label>
            <input
                type="text"
                name="username"
                class="form-control"
                value="<?php echo htmlspecialchars($username); ?>"
                required
                autocomplete="username">
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Vorname' : 'First Name'; ?>
                </label>
                <input
                    type="text"
                    name="firstName"
                    class="form-control"
                    value="<?php echo htmlspecialchars($firstName); ?>"
                    required
                    autocomplete="given-name">
            </div>
            <div class="col-md-6">
                <label class="form-label">
                    <?php echo ($lang === 'de') ? 'Nachname' : 'Last Name'; ?>
                </label>
                <input
                    type="text"
                    name="lastName"
                    class="form-control"
                    value="<?php echo htmlspecialchars($lastName); ?>"
                    required
                    autocomplete="family-name">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'E-Mail' : 'Email'; ?>
            </label>
            <input
                type="email"
                name="email"
                class="form-control"
                value="<?php echo htmlspecialchars($email); ?>"
                required
                autocomplete="email">
        </div>

        <div class="mb-3">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'Passwort' : 'Password'; ?>
            </label>
            <input
                type="password"
                name="password"
                class="form-control"
                required
                autocomplete="new-password"
                minlength="8">
        </div>

        <div class="mb-4">
            <label class="form-label">
                <?php echo ($lang === 'de') ? 'Passwort bestätigen' : 'Confirm Password'; ?>
            </label>
            <input
                type="password"
                name="confirm_password"
                class="form-control"
                required
                autocomplete="new-password">
        </div>

        <button type="submit" name="register" class="btn btn-primary w-100 mb-3">
            <?php echo ($lang === 'de') ? 'Registrieren' : 'Register'; ?>
        </button>

        <div class="text-center">
            <p class="mb-0">
                <?php echo ($lang === 'de') ? 'Bereits registriert?' : 'Already a member?'; ?>
                <a href="login.php">
                    <?php echo ($lang === 'de') ? 'Hier einloggen' : 'Login here'; ?>
                </a>
            </p>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>