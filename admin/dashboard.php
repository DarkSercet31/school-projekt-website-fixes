<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

// Require login + admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../user/dashboard.php');
    exit;
}

$username  = $_SESSION['pk_username'] ?? ($_SESSION['username'] ?? '');
$firstName = $_SESSION['firstName'] ?? '';
$lang      = $_SESSION['lang'] ?? 'en';

// Stat counters
$stats = [
    'users'    => (int)$pdo->query("SELECT COUNT(*) FROM user")->fetchColumn(),
    'stations' => (int)$pdo->query("SELECT COUNT(*) FROM station")->fetchColumn(),
    'today'    => (int)$pdo->query(
        "SELECT COUNT(*) FROM measurement WHERE DATE(timestamp) = CURDATE()"
    )->fetchColumn(),
    'tickets'  => (int)$pdo->query("SELECT COUNT(*) FROM support_ticket")->fetchColumn(),
];

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3 py-4">

        <!-- Header-Karte -->
        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Admin-Dashboard' : 'Admin dashboard'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Verwalte Benutzer, Stationen, Sammlungen, Zugriffsrechte und Messdaten an einem Ort.'
                            : 'Manage users, stations, collections, access rights and measurements in one place.'; ?>
                    </p>
                </div>
            </div>

            <div class="glass-card-body">
                <p class="mb-0">
                    <?php echo ($lang === 'de')
                        ? 'Willkommen, ' . htmlspecialchars($firstName ?: $username) . '. Du hast Administratorrechte.'
                        : 'Welcome, ' . htmlspecialchars($firstName ?: $username) . '. You have administrator access.'; ?>
                </p>
            </div>
        </section>

        <!-- Stat cards -->
        <section class="mb-3">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3 text-center">
                        <div class="glass-card-sub small">
                            <?php echo ($lang === 'de') ? 'Benutzer' : 'Users'; ?>
                        </div>
                        <div class="glass-card-title" style="font-size:1.8rem;">
                            <?php echo $stats['users']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3 text-center">
                        <div class="glass-card-sub small">
                            <?php echo ($lang === 'de') ? 'Stationen' : 'Stations'; ?>
                        </div>
                        <div class="glass-card-title" style="font-size:1.8rem;">
                            <?php echo $stats['stations']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3 text-center">
                        <div class="glass-card-sub small">
                            <?php echo ($lang === 'de') ? 'Messungen heute' : 'Measurements today'; ?>
                        </div>
                        <div class="glass-card-title" style="font-size:1.8rem;">
                            <?php echo $stats['today']; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="glass-card p-3 text-center">
                        <div class="glass-card-sub small">
                            <?php echo ($lang === 'de') ? 'Support-Tickets' : 'Support tickets'; ?>
                        </div>
                        <div class="glass-card-title" style="font-size:1.8rem;">
                            <?php echo $stats['tickets']; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Admin-Kacheln -->
        <section class="glass-card">
            <div class="glass-card-body">
                <div class="row g-3">

                    <!-- Overview -->
                    <div class="col-md-6 col-xl-4">
                        <a href="index.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Overview' : 'Overview'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Schneller Einstieg in alle Admin-Bereiche.'
                                        : 'Quick entry point to all admin areas.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Users -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_users.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Users' : 'Users'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Benutzerkonten verwalten und Rollen vergeben.'
                                        : 'Manage user accounts and roles.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Stations -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_stations.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Stations' : 'Stations'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Stationen registrieren, bearbeiten und zuweisen.'
                                        : 'Register, edit and assign stations.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Collections -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_collections.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Collections' : 'Collections'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Messwert-Sammlungen verwalten und teilen.'
                                        : 'Manage and share measurement collections.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Measurements -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_measurements.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Measurements' : 'Measurements'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Messdaten ansehen, filtern und exportieren.'
                                        : 'View, filter and export measurement data.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Access rights -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_access_rights.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Access rights' : 'Access rights'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Zugriffsrechte für Benutzer und Stationen steuern.'
                                        : 'Control access rights for users and stations.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Products -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_products.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Produkte' : 'Products'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Shop-Produkte anlegen, bearbeiten und löschen.'
                                        : 'Create, edit and delete shop products.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                    <!-- Support tickets -->
                    <div class="col-md-6 col-xl-4">
                        <a href="admin_support.php" class="dashboard-card-link">
                            <div class="dashboard-card">
                                <h2 class="dashboard-card-title">
                                    <?php echo ($lang === 'de') ? 'Support' : 'Support'; ?>
                                </h2>
                                <p class="dashboard-card-text">
                                    <?php echo ($lang === 'de')
                                        ? 'Nutzeranfragen und Beschwerden bearbeiten.'
                                        : 'Handle user requests and complaints.'; ?>
                                </p>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
        </section>

    </div>
</main>