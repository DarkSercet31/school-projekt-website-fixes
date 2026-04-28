<?php
// dashboard.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/db_connection.php';
require 'config/lang.php';

$lang = $_SESSION['lang'] ?? 'en';

// Muss eingeloggt sein
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: auth/login.php");
    exit;
}

// optionale User-Daten
$displayName = $_SESSION['display_name'] ?? ($_SESSION['username'] ?? 'User');
$initials    = strtoupper(substr($displayName, 0, 1));
?>

<?php include 'includes/header.php'; ?>

<main class="main-shell d-flex justify-content-center">
    <div class="container-xxl px-3">

        <!-- Haupt-Willkommenskarte -->
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Willkommen!' : 'Welcome!'; ?>
                    </h1>
                    <p class="glass-card-sub">
                        <?php echo ($lang === 'de')
                            ? 'Dies ist dein Dashboard für das Wetterstationsprojekt.'
                            : 'This is your dashboard for the weather station project.'; ?>
                    </p>
                </div>

                <div class="text-end">
                    <div class="fw-semibold"><?php echo htmlspecialchars($initials); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($displayName); ?></div>
                    <a href="auth/account.php" class="btn btn-primary-soft btn-sm mt-2">
                        <?php echo ($lang === 'de') ? 'Account' : 'Account'; ?>
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['login_message'])): ?>
                <div class="alert alert-success mb-4">
                    <?php
                    echo htmlspecialchars($_SESSION['login_message']);
                    unset($_SESSION['login_message']);
                    ?>
                </div>
            <?php endif; ?>

            <p class="mb-1">
                <?php echo ($lang === 'de')
                    ? 'Hier kannst du deine Projektdaten verwalten und deine Wetterstationen steuern.'
                    : 'Here you can manage your project data and control your weather stations.'; ?>
            </p>
            <p class="mb-0">
                <?php echo ($lang === 'de')
                    ? 'Nutze diese Seite als Ausgangspunkt für alle weiteren Funktionen.'
                    : 'Use this page as a starting point for all other functionalities.'; ?>
            </p>

            <!-- Kacheln / Cards unter dem Dashboard -->
            <hr class="my-4">

            <div class="row g-3">
                <!-- Home / Übersicht -->
                <div class="col-md-3 col-sm-6">
                    <a href="user/dashboard.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">🏠</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Übersicht' : 'Overview'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Kurzer Einstieg in alle Bereiche deiner Anwendung.'
                                    : 'Quick entry point to all main areas.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- My stations -->
                <div class="col-md-3 col-sm-6">
                    <a href="user/user_stations.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">📡</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My stations'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Stationen registrieren, bearbeiten und verwalten.'
                                    : 'Register, edit and manage your stations.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Friends -->
                <div class="col-md-3 col-sm-6">
                    <a href="user/user_friends.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">🤝</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Freunde' : 'Friends'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Freundschaften verwalten und Daten teilen.'
                                    : 'Manage friends and share your data.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Collections -->
                <div class="col-md-3 col-sm-6">
                    <a href="user/user_collections.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">📁</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Sammlungen' : 'Collections'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Messreihen in logische Sammlungen gruppieren.'
                                    : 'Group measurements into logical collections.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <!-- Measurements -->
                <div class="col-md-3 col-sm-6">
                    <a href="user/user_measurements.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2">📊</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small text-muted">
                                <?php echo ($lang === 'de')
                                    ? 'Diagramme und Tabellen deiner Messdaten ansehen.'
                                    : 'View charts and tables of your measurements.'; ?>
                            </p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Schnellzugriffe -->
            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="user/user_stations.php" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? '+ Neue Station' : '+ New station'; ?>
                </a>
                <a href="user/user_collections.php" class="btn btn-chip">
                    <?php echo ($lang === 'de') ? '+ Neue Sammlung' : '+ New collection'; ?>
                </a>
                <a href="user/user_friends.php" class="btn btn-chip">
                    <?php echo ($lang === 'de') ? '+ Freund hinzufügen' : '+ Add friend'; ?>
                </a>
            </div>
        </section>

    </div>
</main>