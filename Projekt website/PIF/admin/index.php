<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

// Require login + admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: auth/login.php');
    exit;
}
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('Location: user/dashboard.php');
    exit;
}

$username  = $_SESSION['pk_username'] ?? ($_SESSION['username'] ?? '');
$firstName = $_SESSION['firstName'] ?? '';
$lang      = $_SESSION['lang'] ?? 'en';

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

                </div>
            </div>
        </section>

    </div>
</main>