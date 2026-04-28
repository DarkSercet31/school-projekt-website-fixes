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

// Load stations owned by the current user
$stmt = mysqli_prepare($link,
    "SELECT pk_serialNumber, name, description, status
     FROM station
     WHERE fk_user_owns = ?
     ORDER BY name"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result   = mysqli_stmt_get_result($stmt);
$stations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $stations[] = $row;
}
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My Stations'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Deine registrierten Wetterstationen.'
                            : 'Your registered weather stations.'; ?>
                    </p>
                </div>
            </div>
        </section>

        <section class="glass-card">
            <?php if (empty($stations)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Du hast noch keine Stationen. Bitte wende dich an einen Administrator.'
                        : 'You have no stations yet. Please contact an administrator.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Seriennummer' : 'Serial number'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stations as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['pk_serialNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($s['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($s['description'] ?? ''); ?></td>
                                    <td>
                                        <span class="pill <?php echo $s['status'] === 'Active' ? 'text-success' : 'text-muted'; ?>">
                                            <span class="pill-dot"></span>
                                            <?php echo htmlspecialchars($s['status']); ?>
                                        </span>
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
