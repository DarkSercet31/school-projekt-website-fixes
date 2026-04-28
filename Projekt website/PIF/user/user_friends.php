<?php
// user_friends.php – Friend system with requests + status in isfriend

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Logged-in check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../auth/login.php");
    exit;
}

$currentUser = $_SESSION['pk_username'] ?? null;
if (!$currentUser) {
    header("Location: ../includes/logout.inc.php");
    exit;
}

/*
isfriend:
pkfk_user_user   (requester / owner)
pkfk_user_friend (other user)
status           ENUM('pending','accepted','declined')
requested_at     DATETIME
*/

// -----------------------------------------------------------------------------
// Handle actions: send request, accept, decline, remove
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $friendUsername = trim($_POST['friend_username'] ?? '');

    if ($friendUsername === $currentUser) {
        // cannot friend yourself
        header('Location: user_friends.php');
        exit;
    }

    // SEND REQUEST ----------------------------------------------------------
    if ($action === 'send_request') {

        if ($friendUsername !== '') {
            // user exists?
            $sql  = "SELECT pk_username FROM user WHERE pk_username = ?";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 's', $friendUsername);
            mysqli_stmt_execute($stmt);
            $res        = mysqli_stmt_get_result($stmt);
            $userExists = mysqli_num_rows($res) > 0;
            mysqli_free_result($res);
            mysqli_stmt_close($stmt);

            if ($userExists) {
                // existing relation?
                $sql  = "SELECT status FROM isfriend
                         WHERE (pkfk_user_user = ? AND pkfk_user_friend = ?)
                            OR (pkfk_user_user = ? AND pkfk_user_friend = ?)
                         LIMIT 1";
                $stmt = mysqli_prepare($link, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    'ssss',
                    $currentUser,
                    $friendUsername,
                    $friendUsername,
                    $currentUser
                );
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_free_result($res);
                mysqli_stmt_close($stmt);

                if ($row) {
                    // Re-open / overwrite as pending from currentUser
                    $sql  = "UPDATE isfriend
                             SET pkfk_user_user = ?, pkfk_user_friend = ?, status = 'pending',
                                 requested_at = NOW()
                             WHERE (pkfk_user_user = ? AND pkfk_user_friend = ?)
                                OR (pkfk_user_user = ? AND pkfk_user_friend = ?)";
                    $stmt = mysqli_prepare($link, $sql);
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ssssss',
                        $currentUser,
                        $friendUsername,
                        $currentUser,
                        $friendUsername,
                        $friendUsername,
                        $currentUser
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    // create new pending request from currentUser -> friendUsername
                    $sql  = "INSERT INTO isfriend (pkfk_user_user, pkfk_user_friend, status, requested_at)
                             VALUES (?, ?, 'pending', NOW())";
                    $stmt = mysqli_prepare($link, $sql);
                    mysqli_stmt_bind_param($stmt, 'ss', $currentUser, $friendUsername);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }

                // success message
                $_SESSION['friend_message'] = ($lang === 'de')
                    ? 'Freundschaftsanfrage wurde gesendet.'
                    : 'Friend request has been sent.';
            }
        }

        header('Location: user_friends.php');
        exit;
    }

    // ACCEPT REQUEST --------------------------------------------------------
    if ($action === 'accept') {
        if ($friendUsername !== '') {
            // request must be from friend -> currentUser with status pending
            $sql  = "UPDATE isfriend
                     SET status = 'accepted'
                     WHERE pkfk_user_user = ? AND pkfk_user_friend = ? AND status = 'pending'";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $friendUsername, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // ensure symmetry (currentUser -> friend) as accepted
            $sql  = "INSERT INTO isfriend (pkfk_user_user, pkfk_user_friend, status, requested_at)
                     VALUES (?, ?, 'accepted', NOW())
                     ON DUPLICATE KEY UPDATE status = 'accepted'";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $currentUser, $friendUsername);
            @mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_friends.php');
        exit;
    }

    // DECLINE REQUEST -------------------------------------------------------
    if ($action === 'decline') {
        if ($friendUsername !== '') {
            $sql  = "UPDATE isfriend
                     SET status = 'declined'
                     WHERE pkfk_user_user = ? AND pkfk_user_friend = ? AND status = 'pending'";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, 'ss', $friendUsername, $currentUser);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_friends.php');
        exit;
    }

    // REMOVE FRIEND (after accepted) ---------------------------------------
    if ($action === 'remove') {
        if ($friendUsername !== '') {
            $sql  = "DELETE FROM isfriend
                     WHERE (pkfk_user_user = ? AND pkfk_user_friend = ?)
                        OR (pkfk_user_user = ? AND pkfk_user_friend = ?)";
            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                'ssss',
                $currentUser,
                $friendUsername,
                $friendUsername,
                $currentUser
            );
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        header('Location: user_friends.php');
        exit;
    }
}

// -----------------------------------------------------------------------------
// Load data for page: pending requests, friends
// -----------------------------------------------------------------------------

// Incoming pending requests for currentUser (friend requests)
$sql  = "SELECT pkfk_user_user AS requester
         FROM isfriend
         WHERE pkfk_user_friend = ? AND status = 'pending'
         ORDER BY requested_at DESC";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 's', $currentUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$incomingRequests = [];
while ($row = mysqli_fetch_assoc($res)) {
    $incomingRequests[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// Accepted friends (both directions counted as one list)
$sql  = "SELECT DISTINCT
            CASE
                WHEN pkfk_user_user = ? THEN pkfk_user_friend
                ELSE pkfk_user_user
            END AS friend_username
         FROM isfriend
         WHERE (pkfk_user_user = ? OR pkfk_user_friend = ?)
           AND status = 'accepted'
         ORDER BY friend_username";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, 'sss', $currentUser, $currentUser, $currentUser);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$friends = [];
while ($row = mysqli_fetch_assoc($res)) {
    $friends[] = $row;
}
mysqli_free_result($res);
mysqli_stmt_close($stmt);

// Notification count = number of pending incoming requests
$notificationCount = count($incomingRequests);

$lang = $_SESSION['lang'] ?? 'en';
?>

<?php include '../includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <?php if (!empty($_SESSION['friend_message'])): ?>
            <div class="alert alert-success mb-3">
                <?php
                echo htmlspecialchars($_SESSION['friend_message']);
                unset($_SESSION['friend_message']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Card 1: Add friend (send request) -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Freunde' : 'My friends'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Sende Freundschaftsanfragen an andere Benutzer.'
                            : 'Send friend requests to other users.'; ?>
                    </p>
                </div>
            </div>

            <form method="post" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="send_request">
                <div class="col-md-4 col-sm-8">
                    <label class="form-label mb-1">
                        <?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?>
                    </label>
                    <input type="text" name="friend_username" class="form-control" required>
                </div>
                <div class="col-md-2 col-sm-4 d-flex justify-content-md-start justify-content-sm-end">
                    <button type="submit" class="btn btn-primary-soft w-100">
                        <?php echo ($lang === 'de') ? 'Freundschaftsanfrage senden' : 'Send friend request'; ?>
                    </button>
                </div>
            </form>

            <p class="glass-card-sub mt-3 mb-0">
                <?php echo ($lang === 'de')
                    ? 'Der Benutzername muss im System existieren. Du kannst dich nicht selbst hinzufügen.'
                    : 'The username must exist in the system. You cannot add yourself.'; ?>
            </p>
        </section>

        <!-- Card 2: Incoming friend requests -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Eingehende Anfragen'
                            : 'Incoming requests'; ?>
                    </h2>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Akzeptiere oder lehne Freundschaftsanfragen ab.'
                            : 'Accept or decline friend requests.'; ?>
                    </p>
                </div>
                <span class="glass-card-sub">
                    <?php echo $notificationCount . ' ' . (($lang === 'de')
                        ? ($notificationCount === 1 ? 'Anfrage' : 'Anfragen')
                        : ($notificationCount === 1 ? 'request' : 'requests')); ?>
                </span>
            </div>

            <?php if (empty($incomingRequests)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Keine offenen Anfragen.'
                        : 'No pending requests.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?></th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktionen' : 'Actions'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($incomingRequests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['requester']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="friend_username"
                                               value="<?php echo htmlspecialchars($req['requester']); ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-sm btn-primary-soft me-1">
                                            <?php echo ($lang === 'de') ? 'Akzeptieren' : 'Accept'; ?>
                                        </button>
                                    </form>

                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="friend_username"
                                               value="<?php echo htmlspecialchars($req['requester']); ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="btn btn-sm btn-chip">
                                            <?php echo ($lang === 'de') ? 'Ablehnen' : 'Decline'; ?>
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

        <!-- Card 3: Friend list (accepted) -->
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Freundesliste' : 'Friend list'; ?>
                    </h2>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Deine akzeptierten Freunde.'
                            : 'Your accepted friends.'; ?>
                    </p>
                </div>
                <span class="glass-card-sub">
                    <?php
                    $count = count($friends);
                    echo $count . ' ' . (($lang === 'de')
                        ? ($count === 1 ? 'Freund' : 'Freunde')
                        : ($count === 1 ? 'friend' : 'friends'));
                    ?>
                </span>
            </div>

            <?php if (empty($friends)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Noch keine Freunde.'
                        : 'You do not have any friends yet.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass align-middle">
                        <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Benutzername' : 'Username'; ?></th>
                            <th class="text-end"><?php echo ($lang === 'de') ? 'Aktion' : 'Action'; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($friends as $fr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fr['friend_username']); ?></td>
                                <td class="text-end">
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('<?php echo ($lang === 'de')
                                              ? 'Freund wirklich entfernen?'
                                              : 'Really remove this friend?'; ?>');">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="friend_username"
                                               value="<?php echo htmlspecialchars($fr['friend_username']); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
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