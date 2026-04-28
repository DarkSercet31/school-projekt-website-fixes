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
$error    = '';
$success  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $body    = trim($_POST['body'] ?? '');

    if ($subject === '' || $body === '') {
        $error = ($lang === 'de')
            ? 'Bitte Betreff und Nachricht ausfüllen.'
            : 'Please fill in subject and message.';
    } else {
        $pdo->prepare(
            "INSERT INTO support_ticket (fk_username, subject, body) VALUES (?, ?, ?)"
        )->execute([$username, $subject, $body]);
        $success = ($lang === 'de')
            ? 'Ticket wurde erstellt.'
            : 'Ticket created successfully.';
    }
}

// Load tickets
$stmt = $pdo->prepare(
    "SELECT pk_ticket_id, subject, created_at,
            (SELECT COUNT(*) FROM support_reply WHERE fk_ticket_id = pk_ticket_id) AS reply_count,
            (SELECT COUNT(*) FROM support_reply WHERE fk_ticket_id = pk_ticket_id AND is_read = 0) AS unread_count
     FROM support_ticket
     WHERE fk_username = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$username]);
$tickets = $stmt->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Support / Beschwerden' : 'Support / Complaints'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Erstelle ein Ticket, um den Support zu kontaktieren.'
                            : 'Create a ticket to contact support.'; ?>
                    </p>
                </div>
            </div>
        </section>

        <?php if ($error): ?>
            <div class="alert alert-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- New ticket form -->
        <section class="glass-card mb-3">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Neues Ticket' : 'New ticket'; ?>
            </h2>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label"><?php echo ($lang === 'de') ? 'Betreff' : 'Subject'; ?></label>
                    <input type="text" name="subject" class="form-control" required maxlength="150">
                </div>
                <div class="mb-3">
                    <label class="form-label"><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></label>
                    <textarea name="body" class="form-control" rows="4" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Absenden' : 'Submit'; ?>
                </button>
            </form>
        </section>

        <!-- Ticket list -->
        <section class="glass-card">
            <h2 class="glass-card-title mb-3">
                <?php echo ($lang === 'de') ? 'Meine Tickets' : 'My tickets'; ?>
            </h2>
            <?php if (empty($tickets)): ?>
                <p class="glass-card-sub mb-0">
                    <?php echo ($lang === 'de') ? 'Noch keine Tickets.' : 'No tickets yet.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?php echo ($lang === 'de') ? 'Betreff' : 'Subject'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Erstellt' : 'Created'; ?></th>
                                <th class="text-center">
                                    <?php echo ($lang === 'de') ? 'Antworten' : 'Replies'; ?>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><?php echo (int)$t['pk_ticket_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($t['subject']); ?>
                                    <?php if ($t['unread_count'] > 0): ?>
                                        <span class="badge bg-danger ms-1"><?php echo (int)$t['unread_count']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                                <td class="text-center"><?php echo (int)$t['reply_count']; ?></td>
                                <td class="text-end">
                                    <a href="support_ticket.php?id=<?php echo (int)$t['pk_ticket_id']; ?>"
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
