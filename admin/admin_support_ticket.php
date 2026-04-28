<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../includes/db_connection.php';
require '../config/lang.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php'); exit;
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../user/dashboard.php'); exit;
}

$lang    = $_SESSION['lang'] ?? 'en';
$admin   = $_SESSION['pk_username'] ?? '';
$id      = (int)($_GET['id'] ?? 0);
$success = '';
$error   = '';

$stmt = $pdo->prepare(
    "SELECT pk_ticket_id, fk_username, subject, body, created_at
     FROM support_ticket WHERE pk_ticket_id = ?"
);
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: admin_support.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    if ($body === '') {
        $error = ($lang === 'de') ? 'Antwort darf nicht leer sein.' : 'Reply cannot be empty.';
    } else {
        $pdo->prepare(
            "INSERT INTO support_reply (fk_ticket_id, fk_admin, body) VALUES (?, ?, ?)"
        )->execute([$id, $admin, $body]);
        $success = ($lang === 'de') ? 'Antwort gespeichert.' : 'Reply sent.';
    }
}

// Load replies
$rStmt = $pdo->prepare(
    "SELECT fk_admin, body, replied_at
     FROM support_reply WHERE fk_ticket_id = ? ORDER BY replied_at ASC"
);
$rStmt->execute([$id]);
$replies = $rStmt->fetchAll();

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
                        #<?php echo (int)$ticket['pk_ticket_id']; ?>
                        <?php echo htmlspecialchars($ticket['subject']); ?>
                    </h1>
                    <span class="glass-card-sub small">
                        <?php echo ($lang === 'de') ? 'Von: ' : 'From: '; ?>
                        <?php echo htmlspecialchars($ticket['fk_username']); ?>
                        &nbsp;·&nbsp;
                        <?php echo htmlspecialchars($ticket['created_at']); ?>
                    </span>
                </div>
                <a href="admin_support.php" class="btn btn-chip btn-sm">
                    ← <?php echo ($lang === 'de') ? 'Zurück' : 'Back'; ?>
                </a>
            </div>
        </section>

        <!-- Ticket body -->
        <section class="glass-card mb-3">
            <h2 class="glass-card-sub mb-2">
                <?php echo ($lang === 'de') ? 'Anfrage' : 'Request'; ?>
            </h2>
            <p style="white-space:pre-wrap;"><?php echo htmlspecialchars($ticket['body']); ?></p>
        </section>

        <!-- Existing replies -->
        <?php if (!empty($replies)): ?>
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Antworten' : 'Replies'; ?>
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
        <?php endif; ?>

        <!-- Reply form -->
        <section class="glass-card">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Antwort senden' : 'Send reply'; ?>
            </h2>
            <form method="post">
                <div class="mb-3">
                    <textarea name="body" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Absenden' : 'Send'; ?>
                </button>
            </form>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
