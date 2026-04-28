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

// Load collections created by the user
$stmt = mysqli_prepare($link,
    "SELECT c.pk_collection, c.name, c.description,
            COUNT(ct.pkfk_measurement) AS measurement_count
     FROM collection c
     LEFT JOIN contains ct ON ct.pkfk_collection = c.pk_collection
     WHERE c.fk_user_creates = ?
     GROUP BY c.pk_collection
     ORDER BY c.pk_collection DESC"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ownCollections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $ownCollections[] = $row;
}
mysqli_stmt_close($stmt);

// Load collections shared with the user via hasaccess
$stmt = mysqli_prepare($link,
    "SELECT c.pk_collection, c.name, c.description, c.fk_user_creates,
            COUNT(ct.pkfk_measurement) AS measurement_count
     FROM hasaccess ha
     JOIN collection c ON ha.pkfk_collection = c.pk_collection
     LEFT JOIN contains ct ON ct.pkfk_collection = c.pk_collection
     WHERE ha.pkfk_user = ?
     GROUP BY c.pk_collection
     ORDER BY c.pk_collection DESC"
);
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$sharedCollections = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sharedCollections[] = $row;
}
mysqli_stmt_close($stmt);

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <!-- Own collections -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Meine Sammlungen' : 'My Collections'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Von dir erstellte Sammlungen.'
                            : 'Collections you created.'; ?>
                    </p>
                </div>
            </div>

            <?php if (empty($ownCollections)): ?>
                <p class="text-muted mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Du hast noch keine Sammlungen. Bitte wende dich an einen Administrator.'
                        : 'You have no collections yet. Please contact an administrator.'; ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Beschreibung' : 'Description'; ?></th>
                                <th><?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ownCollections as $c): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td><?php echo htmlspecialchars($c['description'] ?? ''); ?></td>
                                    <td><?php echo (int)$c['measurement_count']; ?></td>
                                    <td>
                                        <a href="collection_view.php?id=<?php echo (int)$c['pk_collection']; ?>"
                                           class="btn btn-chip btn-sm">
                                            <?php echo ($lang === 'de') ? 'Anzeigen' : 'View'; ?>
                                        </a>
                                        <a href="user_sharing.php?id=<?php echo (int)$c['pk_collection']; ?>"
                                           class="btn btn-chip btn-sm">
                                            <?php echo ($lang === 'de') ? 'Teilen' : 'Share'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Shared with me -->
        <?php if (!empty($sharedCollections)): ?>
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h2 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Mit mir geteilt' : 'Shared with me'; ?>
                    </h2>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table-glass w-100">
                    <thead>
                        <tr>
                            <th><?php echo ($lang === 'de') ? 'Name' : 'Name'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Erstellt von' : 'Created by'; ?></th>
                            <th><?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sharedCollections as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['name']); ?></td>
                                <td><?php echo htmlspecialchars($c['fk_user_creates']); ?></td>
                                <td><?php echo (int)$c['measurement_count']; ?></td>
                                <td>
                                    <a href="collection_view.php?id=<?php echo (int)$c['pk_collection']; ?>"
                                       class="btn btn-chip btn-sm">
                                        <?php echo ($lang === 'de') ? 'Anzeigen' : 'View'; ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
