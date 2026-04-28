<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';
require '../config/mailtrap.php';

// Require login + admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: auth/login.php');
    exit;
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: user/dashboard.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';
$currentAdmin = $_SESSION['pk_username'] ?? ($_SESSION['username'] ?? '');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE USER
    if ($action === 'createuser') {
        $username   = trim($_POST['username'] ?? '');
        $firstNameN = trim($_POST['firstname'] ?? '');
        $lastNameN  = trim($_POST['lastname'] ?? '');
        $emailN     = trim($_POST['email'] ?? '');
        $roleN      = ($_POST['role'] ?? 'User') === 'Admin' ? 'Admin' : 'User';

        if ($username === '' || $emailN === '') {
            $error = ($lang === 'de')
                ? 'Benutzername und E-Mail sind Pflichtfelder.'
                : 'Username and email are required.';
        } elseif (!filter_var($emailN, FILTER_VALIDATE_EMAIL)) {
            $error = ($lang === 'de')
                ? 'Bitte gib eine gültige E-Mail-Adresse ein.'
                : 'Please enter a valid email address.';
        } else {
            $stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE pk_username = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, 'ss', $username, $emailN);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $exists = $res && mysqli_num_rows($res) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                $error = ($lang === 'de')
                    ? 'Benutzername oder E-Mail existiert bereits.'
                    : 'Username or email already exists.';
            } else {
                // Generate random 8-char temp password
                $chars         = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
                $passwordPlain = '';
                for ($i = 0; $i < 8; $i++) {
                    $passwordPlain .= $chars[random_int(0, strlen($chars) - 1)];
                }
                $hash = password_hash($passwordPlain, PASSWORD_DEFAULT);

                $sql = "INSERT INTO user
                            (pk_username, firstName, lastName, password, email, role, status, mustChangePassword)
                        VALUES (?, ?, ?, ?, ?, ?, 'verified', 1)";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssssss',
                    $username,
                    $firstNameN,
                    $lastNameN,
                    $hash,
                    $emailN,
                    $roleN
                );

                if (mysqli_stmt_execute($stmt)) {
                    // Send welcome email with temporary credentials
                    try {
                        $mail = createMailer();
                        $mail->addAddress($emailN, trim($firstNameN . ' ' . $lastNameN));
                        $mail->Subject = 'Your Weather Station account';
                        $mail->isHTML(true);
                        $mail->Body = "
                            <h2>Welcome to Weather Station!</h2>
                            <p>An administrator has created an account for you.</p>
                            <p><strong>Username:</strong> {$username}<br>
                               <strong>Temporary password:</strong> {$passwordPlain}</p>
                            <p>Please log in and change your password immediately.</p>
                        ";
                        $mail->AltBody = "Username: {$username}\nTemporary password: {$passwordPlain}";
                        $mail->send();
                    } catch (\Exception $e) {
                        error_log('Mailtrap admin create user error: ' . $e->getMessage());
                    }
                    $success = ($lang === 'de')
                        ? 'Benutzer wurde angelegt und E-Mail verschickt.'
                        : 'User created and credentials emailed.';
                } else {
                    $error = 'DB error: ' . mysqli_error($link);
                }

                mysqli_stmt_close($stmt);
            }
        }
    }

    // UPDATE USER INCLUDING USERNAME
    if ($action === 'updateuser') {
        $oldUsername = trim($_POST['old_username'] ?? '');
        $newUsername = trim($_POST['username'] ?? '');
        $firstU      = trim($_POST['firstname'] ?? '');
        $lastU       = trim($_POST['lastname'] ?? '');
        $emailU      = trim($_POST['email'] ?? '');
        $roleU       = ($_POST['role'] ?? 'User') === 'Admin' ? 'Admin' : 'User';

        if ($oldUsername === '' || $newUsername === '' || $emailU === '') {
            $error = ($lang === 'de')
                ? 'Benutzername und E-Mail sind Pflichtfelder.'
                : 'Username and email are required.';
        } elseif (!filter_var($emailU, FILTER_VALIDATE_EMAIL)) {
            $error = ($lang === 'de')
                ? 'Bitte gib eine gültige E-Mail-Adresse ein.'
                : 'Please enter a valid email address.';
        } else {
            // Check username conflict
            $stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE pk_username = ? AND pk_username <> ?");
            mysqli_stmt_bind_param($stmt, 'ss', $newUsername, $oldUsername);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $usernameTaken = $res && mysqli_num_rows($res) > 0;
            mysqli_stmt_close($stmt);

            if ($usernameTaken) {
                $error = ($lang === 'de')
                    ? 'Dieser Benutzername wird bereits verwendet.'
                    : 'This username is already in use.';
            } else {
                // Check email conflict
                $stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE email = ? AND pk_username <> ?");
                mysqli_stmt_bind_param($stmt, 'ss', $emailU, $oldUsername);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $emailTaken = $res && mysqli_num_rows($res) > 0;
                mysqli_stmt_close($stmt);

                if ($emailTaken) {
                    $error = ($lang === 'de')
                        ? 'Diese E-Mail-Adresse wird bereits verwendet.'
                        : 'This email address is already in use.';
                } else {
                    $sql = "UPDATE user
                            SET pk_username = ?, firstName = ?, lastName = ?, email = ?, role = ?
                            WHERE pk_username = ?";
                    $stmt = mysqli_prepare($link, $sql);
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssssss',
                        $newUsername,
                        $firstU,
                        $lastU,
                        $emailU,
                        $roleU,
                        $oldUsername
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $success = ($lang === 'de')
                            ? 'Benutzerdaten aktualisiert.'
                            : 'User data updated.';

                        // If admin changed own username, update session too
                        if ($oldUsername === $currentAdmin) {
                            $_SESSION['pk_username'] = $newUsername;
                            $_SESSION['username'] = $newUsername;
                            $_SESSION['email'] = $emailU;
                            $_SESSION['role'] = $roleU;
                            $_SESSION['firstName'] = $firstU;
                            $_SESSION['lastName'] = $lastU;
                            $currentAdmin = $newUsername;
                        }
                    } else {
                        $error = 'DB error: ' . mysqli_error($link);
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    // CHANGE PASSWORD TO ANYTHING
    if ($action === 'changepw') {
        $usernameP = trim($_POST['username'] ?? '');
        $newPassword = trim($_POST['new_password'] ?? '');

        if ($usernameP === '') {
            $error = ($lang === 'de')
                ? 'Ungültiger Benutzer.'
                : 'Invalid user.';
        } elseif ($newPassword === '') {
            $error = ($lang === 'de')
                ? 'Das Passwort darf nicht leer sein.'
                : 'Password cannot be empty.';
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $sql = "UPDATE user SET password = ?, mustChangePassword = 0 WHERE pk_username = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $hash, $usernameP);

            if (mysqli_stmt_execute($stmt)) {
                $success = ($lang === 'de')
                    ? 'Passwort wurde aktualisiert.'
                    : 'Password updated.';
            } else {
                $error = 'DB error: ' . mysqli_error($link);
            }

            mysqli_stmt_close($stmt);
        }
    }

    // DELETE USER
    if ($action === 'deleteuser') {
        $usernameD = trim($_POST['username'] ?? '');
        if ($usernameD !== '' && $usernameD !== $currentAdmin) {
            $sql = "DELETE FROM user WHERE pk_username = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 's', $usernameD);

            if (mysqli_stmt_execute($stmt)) {
                $success = ($lang === 'de') ? 'Benutzer gelöscht.' : 'User deleted.';
            } else {
                $error = 'DB error: ' . mysqli_error($link);
            }

            mysqli_stmt_close($stmt);
        }
    }

    // ADD FRIEND
    if ($action === 'addfriend') {
        $user   = trim($_POST['user'] ?? '');
        $friend = trim($_POST['friend'] ?? '');

        if ($user !== '' && $friend !== '' && $user !== $friend) {
            $sql = "SELECT pk_username FROM user WHERE pk_username IN (?, ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $user, $friend);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $count = mysqli_num_rows($res);
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            if ($count === 2) {
                $sql = "INSERT IGNORE INTO isfriend (pkfk_user_user, pkfk_user_friend)
                        VALUES (?, ?), (?, ?)";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param($stmt, 'ssss', $user, $friend, $friend, $user);

                if (mysqli_stmt_execute($stmt)) {
                    $success = ($lang === 'de')
                        ? 'Freundschaft hinzugefügt.'
                        : 'Friendship added.';
                } else {
                    $error = 'DB error: ' . mysqli_error($link);
                }

                mysqli_stmt_close($stmt);
            }
        }
    }

    // REMOVE FRIEND
    if ($action === 'removefriend') {
        $user   = trim($_POST['user'] ?? '');
        $friend = trim($_POST['friend'] ?? '');

        if ($user !== '' && $friend !== '') {
            $sql = "DELETE FROM isfriend
                    WHERE (pkfk_user_user = ? AND pkfk_user_friend = ?)
                       OR (pkfk_user_user = ? AND pkfk_user_friend = ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ssss', $user, $friend, $friend, $user);

            if (mysqli_stmt_execute($stmt)) {
                $success = ($lang === 'de')
                    ? 'Freundschaft entfernt.'
                    : 'Friendship removed.';
            } else {
                $error = 'DB error: ' . mysqli_error($link);
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Load users
$users = [];
$sql = "SELECT pk_username, firstName, lastName, email, role FROM user ORDER BY pk_username";
$usersRes = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($usersRes)) {
    $users[] = $row;
}

// Build user map
$allUsers = [];
foreach ($users as $u) {
    $allUsers[$u['pk_username']] = $u;
}

// Friendship map
$friendsMap = [];
$sql = "SELECT pkfk_user_user, pkfk_user_friend FROM isfriend";
$frRes = mysqli_query($link, $sql);
while ($row = mysqli_fetch_assoc($frRes)) {
    $user   = $row['pkfk_user_user'];
    $friend = $row['pkfk_user_friend'];
    if (!isset($friendsMap[$user])) {
        $friendsMap[$user] = [];
    }
    $friendsMap[$user][] = $friend;
}
mysqli_free_result($frRes);
mysqli_free_result($usersRes);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3 py-4">

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
                        <?php echo ($lang === 'de') ? 'Benutzer verwalten' : 'Manage users'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Lege neue Benutzer an, bearbeite Daten und verwalte Freundeslisten.'
                            : 'Create and edit users and manage friend lists.'; ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="glass-card mb-3">
            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo ($lang === 'de') ? 'Neuen Benutzer anlegen' : 'Create new user'; ?>
                </h2>

                <form method="post" class="row g-2 align-items-end">
                    <input type="hidden" name="action" value="createuser">

                    <div class="col-md-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <?php echo ($lang === 'de') ? 'Vorname' : 'First name'; ?>
                        </label>
                        <input type="text" name="firstname" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">
                            <?php echo ($lang === 'de') ? 'Nachname' : 'Last name'; ?>
                        </label>
                        <input type="text" name="lastname" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="User">User</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button type="submit" class="btn btn-primary-soft">
                            <?php echo ($lang === 'de') ? 'Anlegen' : 'Create'; ?>
                        </button>
                    </div>
                    <div class="col-12 small text-muted mt-1">
                        <?php echo ($lang === 'de')
                            ? 'Standardpasswort: ChangeMe123'
                            : 'Default password: ChangeMe123'; ?>
                    </div>
                </form>
            </div>
        </section>

        <section class="glass-card mb-3">
            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo ($lang === 'de') ? 'Benutzerliste' : 'User list'; ?>
                </h2>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 text-nowrap admin-table">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                            <th>E-Mail</th>
                            <th>Role</th>
                            <th>New Password</th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <form method="post">
                                    <input type="hidden" name="old_username"
                                           value="<?php echo htmlspecialchars($u['pk_username']); ?>">
                                    <td>
                                        <input type="text" name="username"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($u['pk_username']); ?>"
                                               required>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <input type="text" name="firstname"
                                                   class="form-control form-control-sm"
                                                   placeholder="First"
                                                   value="<?php echo htmlspecialchars($u['firstName']); ?>">
                                            <input type="text" name="lastname"
                                                   class="form-control form-control-sm"
                                                   placeholder="Last"
                                                   value="<?php echo htmlspecialchars($u['lastName']); ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <input type="email" name="email"
                                               class="form-control form-control-sm"
                                               value="<?php echo htmlspecialchars($u['email']); ?>"
                                               required>
                                    </td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="User" <?php echo $u['role'] === 'User' ? 'selected' : ''; ?>>User</option>
                                            <option value="Admin" <?php echo $u['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="password" name="new_password"
                                               class="form-control form-control-sm"
                                               placeholder="Set any password">
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="submit" name="action" value="updateuser"
                                                    class="btn btn-success">
                                                <?php echo ($lang === 'de') ? 'Speichern' : 'Save'; ?>
                                            </button>
                                            <button type="submit" name="action" value="changepw"
                                                    class="btn btn-warning">
                                                <?php echo ($lang === 'de') ? 'Passwort' : 'Set PW'; ?>
                                            </button>
                                            <button type="submit" name="action" value="deleteuser"
                                                    class="btn btn-danger"
                                                    <?php echo ($u['pk_username'] === $currentAdmin) ? 'disabled' : ''; ?>
                                                    onclick="return confirm('Delete user <?php echo htmlspecialchars($u['pk_username']); ?>?');">
                                                <?php echo ($lang === 'de') ? 'Löschen' : 'Delete'; ?>
                                            </button>
                                        </div>
                                    </td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="glass-card">
            <div class="glass-card-body">
                <h2 class="h6 mb-3">
                    <?php echo ($lang === 'de') ? 'Freundeslisten verwalten' : 'Manage friend lists'; ?>
                </h2>
                <p class="glass-card-sub">
                    <?php echo ($lang === 'de')
                        ? 'Hier kannst du als Administrator Freundschaften zwischen Benutzern einsehen, hinzufügen oder entfernen.'
                        : 'As an administrator you can view, add, and remove friendships between users here.'; ?>
                </p>

                <?php foreach ($allUsers as $username => $u): ?>
                    <div class="border rounded mb-3 p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?php echo htmlspecialchars($username); ?></strong>
                                <span class="text-muted ms-1">
                                    <?php echo htmlspecialchars(trim($u['firstName'] . ' ' . $u['lastName'])); ?>
                                </span>
                            </div>
                            <span class="badge bg-secondary">
                                <?php echo htmlspecialchars($u['role']); ?>
                            </span>
                        </div>

                        <form method="post" class="row gy-2 gx-2 align-items-end mb-2">
                            <input type="hidden" name="action" value="addfriend">
                            <input type="hidden" name="user"
                                   value="<?php echo htmlspecialchars($username); ?>">

                            <div class="col-md-5 col-sm-8">
                                <label class="form-label form-label-sm mb-1">
                                    <?php echo ($lang === 'de') ? 'Freund hinzufügen' : 'Add friend'; ?>
                                </label>
                                <select name="friend" class="form-select form-select-sm">
                                    <option value="">
                                        <?php echo ($lang === 'de')
                                            ? '-- Benutzer wählen --'
                                            : '-- Select user --'; ?>
                                    </option>
                                    <?php foreach ($allUsers as $uname2 => $u2): ?>
                                        <?php if ($uname2 === $username) continue; ?>
                                        <option value="<?php echo htmlspecialchars($uname2); ?>">
                                            <?php
                                            $fullName2 = trim($u2['firstName'] . ' ' . $u2['lastName']);
                                            echo htmlspecialchars($uname2 . ' ' . $fullName2);
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-4">
                                <button type="submit" class="btn btn-primary-soft btn-sm w-100">
                                    <?php echo ($lang === 'de') ? 'Hinzufügen' : 'Add'; ?>
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th><?php echo ($lang === 'de') ? 'Freunde' : 'Friends'; ?></th>
                                    <th class="text-end"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $friends = $friendsMap[$username] ?? [];
                                $friends = array_unique($friends);
                                if (count($friends) === 0): ?>
                                    <tr>
                                        <td colspan="2" class="text-muted">
                                            <?php echo ($lang === 'de')
                                                ? 'Noch keine Freunde.'
                                                : 'No friends yet.'; ?>
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($friends as $friend):
                                        if (!isset($allUsers[$friend])) continue;
                                        $fu = $allUsers[$friend];
                                        $fullNameF = trim($fu['firstName'] . ' ' . $fu['lastName']);
                                        ?>
                                        <tr>
                                            <td>
                                                <?php echo htmlspecialchars($friend . ' ' . $fullNameF); ?>
                                            </td>
                                            <td class="text-end">
                                                <form method="post" class="d-inline"
                                                      onsubmit="return confirm('Remove friendship between <?php echo htmlspecialchars($username); ?> and <?php echo htmlspecialchars($friend); ?>?');">
                                                    <input type="hidden" name="action" value="removefriend">
                                                    <input type="hidden" name="user"
                                                           value="<?php echo htmlspecialchars($username); ?>">
                                                    <input type="hidden" name="friend"
                                                           value="<?php echo htmlspecialchars($friend); ?>">
                                                    <button type="submit"
                                                            class="btn btn-outline-danger btn-sm">
                                                        <?php echo ($lang === 'de') ? 'Entfernen' : 'Remove'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach;
                                endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </section>

    </div>
</main>