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
$id       = (int)($_GET['id'] ?? 0);

// Load ticket (must belong to this user)
$stmt = $pdo->prepare(
    "SELECT pk_ticket_id, subject, body, created_at
     FROM support_ticket
     WHERE pk_ticket_id = ? AND fk_username = ?"
);
$stmt->execute([$id, $username]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: support.php');
    exit;
}

// Load replies and mark as read
$pdo->prepare(
    "UPDATE support_reply SET is_read = 1 WHERE fk_ticket_id = ?"
)->execute([$id]);

$rStmt = $pdo->prepare(
    "SELECT fk_admin, body, replied_at
     FROM support_reply
     WHERE fk_ticket_id = ?
     ORDER BY replied_at ASC"
);
$rStmt->execute([$id]);
$replies = $rStmt->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        #<?php echo (int)$ticket['pk_ticket_id']; ?>
                        <?php echo htmlspecialchars($ticket['subject']); ?>
                    </h1>
                    <span class="glass-card-sub small">
                        <?php echo htmlspecialchars($ticket['created_at']); ?>
                    </span>
                </div>
                <a href="support.php" class="btn btn-chip btn-sm">
                    ← <?php echo ($lang === 'de') ? 'Zurück' : 'Back'; ?>
                </a>
            </div>
        </section>

        <!-- Original ticket body -->
        <section class="glass-card mb-3">
            <h2 class="glass-card-sub mb-2">
                <?php echo ($lang === 'de') ? 'Deine Anfrage' : 'Your request'; ?>
            </h2>
            <p style="white-space:pre-wrap;"><?php echo htmlspecialchars($ticket['body']); ?></p>
        </section>

        <!-- Replies -->
        <?php if (!empty($replies)): ?>
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Admin-Antworten' : 'Admin replies'; ?>
            </h2>
            <?php foreach ($replies as $r): ?>
            <div class="mb-3 p-3" style="background:rgba(67,97,238,.10);border-radius:10px;">
                <div class="small fw-semibold mb-1">
                    <?php echo htmlspecialchars($r['fk_admin']); ?>
                    <span class="text-muted ms-2"><?php echo htmlspecialchars($r['replied_at']); ?></span>
                </div>
                <p class="mb-0" style="white-space:pre-wrap;"><?php echo htmlspecialchars($r['body']); ?></p>
            </div>
            <?php endforeach; ?>
        </section>
        <?php else: ?>
        <div class="glass-card mb-3">
            <p class="glass-card-sub mb-0">
                <?php echo ($lang === 'de')
                    ? 'Noch keine Antwort vom Support.'
                    : 'No reply from support yet.'; ?>
            </p>
        </div>
        <?php endif; ?>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
