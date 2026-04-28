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

$lang = $_SESSION['lang'] ?? 'en';

// Load all tickets with reply count and unread count
$tickets = $pdo->query(
    "SELECT st.pk_ticket_id, st.fk_username, st.subject, st.created_at,
            COUNT(sr.pk_reply_id) AS reply_count,
            SUM(CASE WHEN sr.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
     FROM support_ticket st
     LEFT JOIN support_reply sr ON sr.fk_ticket_id = st.pk_ticket_id
     GROUP BY st.pk_ticket_id
     ORDER BY st.created_at DESC"
)->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3 py-4">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Support-Tickets' : 'Support tickets'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Alle eingehenden Nutzeranfragen.'
                            : 'All incoming user requests.'; ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="glass-card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th><?php echo ($lang === 'de') ? 'Benutzer' : 'User'; ?></th>
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
                            <td><?php echo htmlspecialchars($t['fk_username']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($t['subject']); ?>
                                <?php if ($t['unread_count'] > 0): ?>
                                    <span class="badge bg-danger ms-1">new</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($t['created_at']); ?></td>
                            <td class="text-center"><?php echo (int)$t['reply_count']; ?></td>
                            <td class="text-end">
                                <a href="admin_support_ticket.php?id=<?php echo (int)$t['pk_ticket_id']; ?>"
                                   class="btn btn-primary-soft btn-sm">
                                    <?php echo ($lang === 'de') ? 'Ansehen' : 'View'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="6" class="text-muted text-center">
                                <?php echo ($lang === 'de') ? 'Keine Tickets.' : 'No tickets.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
