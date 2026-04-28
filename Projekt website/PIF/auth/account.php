<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

// Require login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$username  = $_SESSION['pk_username'] ?? ($_SESSION['username'] ?? '');
$firstName = $_SESSION['firstName'] ?? ($_SESSION['first_name'] ?? '');
$lastName  = $_SESSION['lastName'] ?? '';
$email     = $_SESSION['email'] ?? '';
$role      = $_SESSION['role'] ?? 'User';

$success = '';
$error   = '';

// Refresh user data from DB so session mistakes do not break the page
$sqlUser = "SELECT pk_username, firstName, lastName, email, role FROM user WHERE pk_username = ?";
$stmtUser = mysqli_prepare($link, $sqlUser);

if ($stmtUser) {
    mysqli_stmt_bind_param($stmtUser, 's', $username);
    mysqli_stmt_execute($stmtUser);
    $resUser = mysqli_stmt_get_result($stmtUser);

    if ($rowUser = mysqli_fetch_assoc($resUser)) {
        $username  = $rowUser['pk_username'];
        $firstName = $rowUser['firstName'];
        $lastName  = $rowUser['lastName'];
        $email     = $rowUser['email'];
        $role      = $rowUser['role'];

        $_SESSION['username']   = $username;
        $_SESSION['pk_username'] = $username;
        $_SESSION['firstName']  = $firstName;
        $_SESSION['first_name'] = $firstName;
        $_SESSION['lastName']   = $lastName;
        $_SESSION['email']      = $email;
        $_SESSION['role']       = $role;
    }

    mysqli_stmt_close($stmtUser);
}

// Handle profile update including username
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $newFirst    = trim($_POST['firstName'] ?? '');
    $newLast     = trim($_POST['lastName'] ?? '');
    $newEmail    = trim($_POST['email'] ?? '');

    if ($newUsername === '' || $newFirst === '' || $newLast === '' || $newEmail === '') {
        $error = $lang === 'de'
            ? 'Alle Profilfelder sind erforderlich.'
            : 'All profile fields are required.';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $error = $lang === 'de'
            ? 'Bitte gib eine gültige E-Mail-Adresse ein.'
            : 'Please enter a valid email address.';
    } else {
        // Check username used by another user
        $sqlCheckUser = "SELECT pk_username FROM user WHERE pk_username = ? AND pk_username <> ?";
        $stmtCheckUser = mysqli_prepare($link, $sqlCheckUser);
        mysqli_stmt_bind_param($stmtCheckUser, 'ss', $newUsername, $username);
        mysqli_stmt_execute($stmtCheckUser);
        $resCheckUser = mysqli_stmt_get_result($stmtCheckUser);

        if (mysqli_fetch_assoc($resCheckUser)) {
            $error = $lang === 'de'
                ? 'Dieser Benutzername wird bereits verwendet.'
                : 'This username is already in use.';
        }
        mysqli_stmt_close($stmtCheckUser);

        // Check email used by another user
        if ($error === '') {
            $sqlCheckMail = "SELECT pk_username FROM user WHERE email = ? AND pk_username <> ?";
            $stmtCheckMail = mysqli_prepare($link, $sqlCheckMail);
            mysqli_stmt_bind_param($stmtCheckMail, 'ss', $newEmail, $username);
            mysqli_stmt_execute($stmtCheckMail);
            $resCheckMail = mysqli_stmt_get_result($stmtCheckMail);

            if (mysqli_fetch_assoc($resCheckMail)) {
                $error = $lang === 'de'
                    ? 'Diese E-Mail-Adresse wird bereits verwendet.'
                    : 'This email address is already in use.';
            }
            mysqli_stmt_close($stmtCheckMail);
        }

        // Update profile
        if ($error === '') {
            $sqlUpdate = "UPDATE user SET pk_username = ?, firstName = ?, lastName = ?, email = ? WHERE pk_username = ?";
            $stmtUpdate = mysqli_prepare($link, $sqlUpdate);
            mysqli_stmt_bind_param($stmtUpdate, 'sssss', $newUsername, $newFirst, $newLast, $newEmail, $username);

            if (mysqli_stmt_execute($stmtUpdate)) {
                $_SESSION['username']    = $newUsername;
                $_SESSION['pk_username'] = $newUsername;
                $_SESSION['firstName']   = $newFirst;
                $_SESSION['first_name']  = $newFirst;
                $_SESSION['lastName']    = $newLast;
                $_SESSION['email']       = $newEmail;

                $username  = $newUsername;
                $firstName = $newFirst;
                $lastName  = $newLast;
                $email     = $newEmail;

                $success = $lang === 'de'
                    ? 'Profil wurde aktualisiert.'
                    : 'Profile updated successfully.';
            } else {
                $error = 'DB error: ' . mysqli_error($link);
            }

            mysqli_stmt_close($stmtUpdate);
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = trim($_POST['current_password'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $error = $lang === 'de'
            ? 'Alle Passwortfelder sind erforderlich.'
            : 'All password fields are required.';
    } elseif (strlen($newPassword) < 8) {
        $error = $lang === 'de'
            ? 'Das neue Passwort muss mindestens 8 Zeichen lang sein.'
            : 'The new password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = $lang === 'de'
            ? 'Die neuen Passwörter stimmen nicht überein.'
            : 'The new passwords do not match.';
    } else {
        $sqlPass = "SELECT password FROM user WHERE pk_username = ?";
        $stmtPass = mysqli_prepare($link, $sqlPass);
        mysqli_stmt_bind_param($stmtPass, 's', $username);
        mysqli_stmt_execute($stmtPass);
        $resPass = mysqli_stmt_get_result($stmtPass);
        $userPass = mysqli_fetch_assoc($resPass);
        mysqli_stmt_close($stmtPass);

        if (!$userPass || !password_verify($currentPassword, $userPass['password'])) {
            $error = $lang === 'de'
                ? 'Das aktuelle Passwort ist falsch.'
                : 'Current password is incorrect.';
        } else {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $sqlUpdatePass = "UPDATE user SET password = ? WHERE pk_username = ?";
            $stmtUpdatePass = mysqli_prepare($link, $sqlUpdatePass);
            mysqli_stmt_bind_param($stmtUpdatePass, 'ss', $newPasswordHash, $username);

            if (mysqli_stmt_execute($stmtUpdatePass)) {
                $success = $lang === 'de'
                    ? 'Passwort wurde erfolgreich geändert.'
                    : 'Password changed successfully.';
            } else {
                $error = 'DB error: ' . mysqli_error($link);
            }

            mysqli_stmt_close($stmtUpdatePass);
        }
    }
}

$currentTheme    = $_COOKIE['theme'] ?? 'light';
$currentRemember = $_COOKIE['remember_me'] ?? '0';
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3 py-4">

        <?php if ($success !== ''): ?>
            <div class="alert alert-success mb-3">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger mb-3">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo $lang === 'de' ? 'Kontoeinstellungen' : 'Account settings'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo $lang === 'de'
                            ? 'Verwalte dein Profil und dein Passwort.'
                            : 'Manage your profile and password.'; ?>
                    </p>
                </div>
                <span class="badge bg-gradient-primary-soft text-primary-emphasis">
                    <?php echo htmlspecialchars($role); ?>
                </span>
            </div>

            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo $lang === 'de' ? 'Profil' : 'Profile'; ?>
                </h2>

                <form method="post" action="<?php echo htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Benutzername' : 'Username'; ?>
                            </label>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                value="<?php echo htmlspecialchars($username); ?>"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'E-Mail' : 'Email'; ?>
                            </label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?php echo htmlspecialchars($email); ?>"
                                required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Vorname' : 'First name'; ?>
                            </label>
                            <input
                                type="text"
                                name="firstName"
                                class="form-control"
                                value="<?php echo htmlspecialchars($firstName); ?>"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Nachname' : 'Last name'; ?>
                            </label>
                            <input
                                type="text"
                                name="lastName"
                                class="form-control"
                                value="<?php echo htmlspecialchars($lastName); ?>"
                                required>
                        </div>
                    </div>

                    <button type="submit" name="save_profile" class="btn btn-primary-soft">
                        <?php echo $lang === 'de' ? 'Profil speichern' : 'Save profile'; ?>
                    </button>
                </form>
            </div>
        </section>

        <section class="glass-card mb-3">
            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo $lang === 'de' ? 'Passwort ändern' : 'Change password'; ?>
                </h2>

                <form method="post" action="<?php echo htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Aktuelles Passwort' : 'Current password'; ?>
                            </label>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Neues Passwort' : 'New password'; ?>
                            </label>
                            <input
                                type="password"
                                name="new_password"
                                class="form-control"
                                minlength="8"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Passwort bestätigen' : 'Confirm password'; ?>
                            </label>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="8"
                                required>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-primary-soft">
                        <?php echo $lang === 'de' ? 'Passwort speichern' : 'Save password'; ?>
                    </button>
                </form>
            </div>
        </section>

        <section class="glass-card">
            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo $lang === 'de' ? 'Einstellungen' : 'Preferences'; ?>
                </h2>

                <form method="post" action="<?php echo htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <?php echo $lang === 'de' ? 'Sprache' : 'Language'; ?>
                            </label>
                            <select name="lang_pref" class="form-select">
                                <option value="en" <?php echo $lang === 'en' ? 'selected' : ''; ?>>English</option>
                                <option value="de" <?php echo $lang === 'de' ? 'selected' : ''; ?>>Deutsch</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="save_prefs" class="btn btn-primary-soft">
                        <?php echo $lang === 'de' ? 'Einstellungen speichern' : 'Save preferences'; ?>
                    </button>
                </form>
            </div>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebars.js"></script>
</body>
</html>