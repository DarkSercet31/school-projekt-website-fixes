<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$lang     = $_SESSION['lang'] ?? 'en';
$username = $_SESSION['pk_username'] ?? '';

// Mark all as read on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_read') {
    $pdo->prepare("UPDATE message SET is_read = 1 WHERE fk_receiver = ?")->execute([$username]);
    $pdo->prepare(
        "UPDATE support_reply sr
         JOIN support_ticket st ON sr.fk_ticket_id = st.pk_ticket_id
         SET sr.is_read = 1
         WHERE st.fk_username = ?"
    )->execute([$username]);
    header('Location: notifications.php');
    exit;
}

// Unread messages grouped by sender
$stmt = $pdo->prepare(
    "SELECT fk_sender, COUNT(*) AS cnt, MAX(sent_at) AS latest
     FROM message
     WHERE fk_receiver = ? AND is_read = 0
     GROUP BY fk_sender
     ORDER BY latest DESC"
);
$stmt->execute([$username]);
$unreadMessages = $stmt->fetchAll();

// Unread support replies
$stmt2 = $pdo->prepare(
    "SELECT sr.pk_reply_id, sr.fk_ticket_id, sr.body, sr.replied_at,
            st.subject
     FROM support_reply sr
     JOIN support_ticket st ON sr.fk_ticket_id = st.pk_ticket_id
     WHERE st.fk_username = ? AND sr.is_read = 0
     ORDER BY sr.replied_at DESC"
);
$stmt2->execute([$username]);
$unreadReplies = $stmt2->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Benachrichtigungen' : 'Notifications'; ?>
                    </h1>
                </div>
                <?php if (!empty($unreadMessages) || !empty($unreadReplies)): ?>
                <form method="post">
                    <input type="hidden" name="action" value="mark_read">
                    <button type="submit" class="btn btn-chip btn-sm">
                        <?php echo ($lang === 'de') ? 'Alle als gelesen markieren' : 'Mark all as read'; ?>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </section>

        <!-- Unread messages -->
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Ungelesene Nachrichten' : 'Unread messages'; ?>
                <?php if (!empty($unreadMessages)): ?>
                    <span class="badge bg-danger ms-2"><?php echo count($unreadMessages); ?></span>
                <?php endif; ?>
            </h2>
            <?php if (empty($unreadMessages)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de') ? 'Keine ungelesenen Nachrichten.' : 'No unread messages.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Von' : 'From'; ?></th>
                                <th class="text-center">
                                    <?php echo ($lang === 'de') ? 'Ungelesen' : 'Unread'; ?>
                                </th>
                                <th><?php echo ($lang === 'de') ? 'Zuletzt' : 'Latest'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($unreadMessages as $msg): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($msg['fk_sender']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?php echo (int)$msg['cnt']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($msg['latest']); ?></td>
                                <td class="text-end">
                                    <a href="chat.php?with=<?php echo urlencode($msg['fk_sender']); ?>"
                                       class="btn btn-primary-soft btn-sm">
                                        <?php echo ($lang === 'de') ? 'Chat öffnen' : 'Open chat'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Unread support replies -->
        <section class="glass-card">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Support-Antworten' : 'Support replies'; ?>
                <?php if (!empty($unreadReplies)): ?>
                    <span class="badge bg-danger ms-2"><?php echo count($unreadReplies); ?></span>
                <?php endif; ?>
            </h2>
            <?php if (empty($unreadReplies)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de') ? 'Keine neuen Support-Antworten.' : 'No new support replies.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Ticket' : 'Ticket'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Antwort' : 'Reply'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Datum' : 'Date'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($unreadReplies as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['subject']); ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($r['body'], 0, 60)) . '…'; ?></td>
                                <td><?php echo htmlspecialchars($r['replied_at']); ?></td>
                                <td class="text-end">
                                    <a href="support_ticket.php?id=<?php echo (int)$r['fk_ticket_id']; ?>"
                                       class="btn btn-primary-soft btn-sm">
                                        <?php echo ($lang === 'de') ? 'Ansehen' : 'View'; ?>
                                    </a>
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
