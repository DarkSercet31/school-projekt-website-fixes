<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$lang     = $_SESSION['lang'] ?? 'en';
$username = $_SESSION['pk_username'] ?? '';

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Send a friend request
    if ($action === 'send_request') {
        $target = trim($_POST['target_username'] ?? '');

        if ($target === '' || $target === $username) {
            $error = ($lang === 'de') ? 'Ungültiger Benutzername.' : 'Invalid username.';
        } else {
            // Check target exists
            $stmt = mysqli_prepare($link, "SELECT pk_username FROM user WHERE pk_username = ?");
            mysqli_stmt_bind_param($stmt, 's', $target);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if (!$exists) {
                $error = ($lang === 'de') ? 'Benutzer nicht gefunden.' : 'User not found.';
            } else {
                // Check if request already exists
                $stmt = mysqli_prepare($link,
                    "SELECT pkfk_user_user FROM isfriend
                     WHERE pkfk_user_user = ? AND pkfk_user_friend = ?"
                );
                mysqli_stmt_bind_param($stmt, 'ss', $username, $target);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                $already = mysqli_stmt_num_rows($stmt) > 0;
                mysqli_stmt_close($stmt);

                if ($already) {
                    $error = ($lang === 'de')
                        ? 'Anfrage bereits gesendet oder bereits befreundet.'
                        : 'Request already sent or already friends.';
                } else {
                    $stmt = mysqli_prepare($link,
                        "INSERT INTO isfriend (pkfk_user_user, pkfk_user_friend, status)
                         VALUES (?, ?, 'pending')"
                    );
                    mysqli_stmt_bind_param($stmt, 'ss', $username, $target);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    $success = ($lang === 'de')
                        ? 'Freundschaftsanfrage gesendet.'
                        : 'Friend request sent.';
                }
            }
        }
    }

    // Accept a friend request
    if ($action === 'accept') {
        $from = trim($_POST['from_user'] ?? '');
        if ($from !== '') {
            // Update both directions to accepted
            $stmt = mysqli_prepare($link,
                "UPDATE isfriend SET status = 'accepted'
                 WHERE pkfk_user_user = ? AND pkfk_user_friend = ?"
            );
            mysqli_stmt_bind_param($stmt, 'ss', $from, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Insert reverse row if not present
            $stmt = mysqli_prepare($link,
                "INSERT IGNORE INTO isfriend (pkfk_user_user, pkfk_user_friend, status)
                 VALUES (?, ?, 'accepted')"
            );
            mysqli_stmt_bind_param($stmt, 'ss', $username, $from);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $success = ($lang === 'de') ? 'Anfrage angenommen.' : 'Request accepted.';
        }
    }

    // Decline a friend request
    if ($action === 'decline') {
        $from = trim($_POST['from_user'] ?? '');
        if ($from !== '') {
            $stmt = mysqli_prepare($link,
                "UPDATE isfriend SET status = 'declined'
                 WHERE pkfk_user_user = ? AND pkfk_user_friend = ?"
            );
            mysqli_stmt_bind_param($stmt, 'ss', $from, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = ($lang === 'de') ? 'Anfrage abgelehnt.' : 'Request declined.';
        }
    }

    // Remove a friend
    if ($action === 'remove') {
        $friend = trim($_POST['friend_username'] ?? '');
        if ($friend !== '') {
            $stmt = mysqli_prepare($link,
                "DELETE FROM isfriend
                 WHERE (pkfk_user_user = ? AND pkfk_user_friend = ?)
                    OR (pkfk_user_user = ? AND pkfk_user_friend = ?)"
            );
            mysqli_stmt_bind_param($stmt, 'ssss', $username, $friend, $friend, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $success = ($lang === 'de') ? 'Freund entfernt.' : 'Friend removed.';
        }
    }
}

// Load accepted friends
$stmt = mysqli_prepare($link,
    "SELECT u.pk_username, u.firstName, u.lastName
     FROM isfriend f
     JOIN user u ON f.pkfk_user_friend = u.pk_username
     WHERE f.pkfk_user_user = ? AND f.status = 'accepted'
     ORDER BY u.pk_username"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$friendsResult = mysqli_stmt_get_result($stmt);
$friends = [];
while ($row = mysqli_fetch_assoc($friendsResult)) {
    $friends[] = $row;
}
mysqli_stmt_close($stmt);

// Load incoming pending requests
$stmt = mysqli_prepare($link,
    "SELECT u.pk_username, u.firstName, u.lastName, f.requested_at
     FROM isfriend f
     JOIN user u ON f.pkfk_user_user = u.pk_username
     WHERE f.pkfk_user_friend = ? AND f.status = 'pending'
     ORDER BY f.requested_at DESC"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$pendingResult = mysqli_stmt_get_result($stmt);
$pending = [];
while ($row = mysqli_fetch_assoc($pendingResult)) {
    $pending[] = $row;
}
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <?php if ($success): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Send friend request -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Freunde' : 'Friends'; ?>
                    </h1>
                </div>
            </div>
            <form method="post" class="d-flex gap-2 align-items-end">
                <input type="hidden" name="action" value="send_request">
                <div class="flex-grow-1">
                    <label class="form-label">
                        <?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?>
                    </label>
                    <input type="text" name="target_username" class="form-control" required
                           placeholder="<?php echo ($lang === 'de') ? 'Benutzername eingeben' : 'Enter username'; ?>">
                </div>
                <button type="submit" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Anfrage senden' : 'Send request'; ?>
                </button>
            </form>
        </section>

        <!-- Incoming pending requests -->
        <?php if (!empty($pending)): ?>
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Eingehende Anfragen' : 'Incoming requests'; ?>
            </h2>
            <div class="table-responsive">
                <table class="table-glass w-100">
                    <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Von' : 'From'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Datum' : 'Date'; ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($p['pk_username']); ?>
                                    <span class="text-muted small ms-1">
                                        <?php echo htmlspecialchars(trim($p['firstName'] . ' ' . $p['lastName'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($p['requested_at']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="action" value="accept">
                                        <input type="hidden" name="from_user"
                                               value="<?php echo htmlspecialchars($p['pk_username']); ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <?php echo ($lang === 'de') ? 'Annehmen' : 'Accept'; ?>
                                        </button>
                                    </form>
                                    <form method="post" class="d-inline ms-1">
                                        <input type="hidden" name="action" value="decline">
                                        <input type="hidden" name="from_user"
                                               value="<?php echo htmlspecialchars($p['pk_username']); ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            <?php echo ($lang === 'de') ? 'Ablehnen' : 'Decline'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <!-- Accepted friends list -->
        <section class="glass-card">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Meine Freunde' : 'My friends'; ?>
                <span class="badge bg-secondary ms-2"><?php echo count($friends); ?></span>
            </h2>
            <?php if (empty($friends)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Noch keine Freunde. Sende eine Anfrage!'
                        : 'No friends yet. Send a request!'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($friends as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['pk_username']); ?></td>
                                    <td><?php echo htmlspecialchars(trim($f['firstName'] . ' ' . $f['lastName'])); ?></td>
                                    <td class="text-end">
                                        <a href="chat.php?with=<?php echo urlencode($f['pk_username']); ?>"
                                           class="btn btn-primary-soft btn-sm me-1">
                                            <?php echo ($lang === 'de') ? 'Chat' : 'Chat'; ?>
                                        </a>
                                        <form method="post" class="d-inline"
                                              onsubmit="return confirm('<?php echo ($lang === 'de')
                                                  ? 'Freund entfernen?'
                                                  : 'Remove friend?'; ?>');">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="friend_username"
                                                   value="<?php echo htmlspecialchars($f['pk_username']); ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <?php echo ($lang === 'de') ? 'Entfernen' : 'Remove'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
