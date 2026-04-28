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
$with     = trim($_GET['with'] ?? '');

// Require a chat partner
if ($with === '' || $with === $username) {
    header('Location: user_friends.php');
    exit;
}

// Verify partner exists
$check = $pdo->prepare("SELECT pk_username FROM user WHERE pk_username = ?");
$check->execute([$with]);
if (!$check->fetch()) {
    header('Location: user_friends.php');
    exit;
}

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = trim($_POST['body'] ?? '');
    if ($body !== '') {
        $pdo->prepare(
            "INSERT INTO message (fk_sender, fk_receiver, body) VALUES (?, ?, ?)"
        )->execute([$username, $with, $body]);
    }
    header('Location: chat.php?with=' . urlencode($with));
    exit;
}

// Mark messages from partner as read
$pdo->prepare(
    "UPDATE message SET is_read = 1 WHERE fk_sender = ? AND fk_receiver = ?"
)->execute([$with, $username]);

// Load message history
$stmt = $pdo->prepare(
    "SELECT pk_message_id, fk_sender, body, sent_at
     FROM message
     WHERE (fk_sender = ? AND fk_receiver = ?)
        OR (fk_sender = ? AND fk_receiver = ?)
     ORDER BY sent_at ASC
     LIMIT 200"
);
$stmt->execute([$username, $with, $with, $username]);
$messages = $stmt->fetchAll();

$lastId = empty($messages) ? 0 : end($messages)['pk_message_id'];

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Chat mit ' : 'Chat with '; ?>
                        <?php echo htmlspecialchars($with); ?>
                    </h1>
                </div>
                <a href="user_friends.php" class="btn btn-chip btn-sm">
                    ← <?php echo ($lang === 'de') ? 'Zurück' : 'Back'; ?>
                </a>
            </div>
        </section>

        <!-- Message thread -->
        <section class="glass-card mb-3">
            <div id="messageThread" style="max-height:420px;overflow-y:auto;padding:.5rem;">
                <?php foreach ($messages as $msg): ?>
                    <?php $mine = $msg['fk_sender'] === $username; ?>
                    <div class="d-flex mb-2 <?php echo $mine ? 'justify-content-end' : 'justify-content-start'; ?>">
                        <div style="max-width:70%;background:<?php echo $mine
                            ? 'rgba(67,97,238,.25)' : 'rgba(255,255,255,.07)'; ?>;
                            border-radius:12px;padding:.5rem .85rem;">
                            <div class="small fw-semibold mb-1">
                                <?php echo htmlspecialchars($msg['fk_sender']); ?>
                                <span class="text-muted ms-1" style="font-size:.7rem;">
                                    <?php echo htmlspecialchars($msg['sent_at']); ?>
                                </span>
                            </div>
                            <div><?php echo nl2br(htmlspecialchars($msg['body'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                    <p class="text-muted text-center my-4">
                        <?php echo ($lang === 'de') ? 'Noch keine Nachrichten.' : 'No messages yet.'; ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Send form -->
        <section class="glass-card">
            <form method="post" class="d-flex gap-2">
                <input type="text" name="body" class="form-control"
                       placeholder="<?php echo ($lang === 'de') ? 'Nachricht eingeben…' : 'Type a message…'; ?>"
                       required autocomplete="off">
                <button type="submit" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Senden' : 'Send'; ?>
                </button>
            </form>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const thread = document.getElementById('messageThread');
thread.scrollTop = thread.scrollHeight;

let lastId = <?php echo (int)$lastId; ?>;
const partner = <?php echo json_encode($with); ?>;
const me = <?php echo json_encode($username); ?>;

function pollMessages() {
    fetch('../includes/poll_messages.php?with=' + encodeURIComponent(partner) + '&after=' + lastId)
        .then(r => r.json())
        .then(msgs => {
            msgs.forEach(msg => {
                lastId = msg.pk_message_id;
                const mine = msg.fk_sender === me;
                const wrap = document.createElement('div');
                wrap.className = 'd-flex mb-2 ' + (mine ? 'justify-content-end' : 'justify-content-start');
                wrap.innerHTML = `<div style="max-width:70%;background:${mine ? 'rgba(67,97,238,.25)' : 'rgba(255,255,255,.07)'};border-radius:12px;padding:.5rem .85rem;">
                    <div class="small fw-semibold mb-1">${msg.fk_sender} <span class="text-muted" style="font-size:.7rem;">${msg.sent_at}</span></div>
                    <div>${msg.body.replace(/\n/g,'<br>')}</div></div>`;
                thread.appendChild(wrap);
            });
            if (msgs.length) thread.scrollTop = thread.scrollHeight;
        })
        .catch(() => {});
}

setInterval(pollMessages, 5000);
</script>
