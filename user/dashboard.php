<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db_connection.php';
require '../config/lang.php';

// Require login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$lang        = $_SESSION['lang'] ?? 'en';
$firstName   = $_SESSION['firstName'] ?? $_SESSION['username'] ?? 'User';
$displayName = $_SESSION['pk_username'] ?? 'User';
$role        = $_SESSION['role'] ?? 'User';
$initials    = strtoupper(substr($firstName, 0, 1));

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <!-- Welcome card -->
        <section class="glass-card">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Willkommen, ' : 'Welcome, '; ?>
                        <?php echo htmlspecialchars($firstName); ?>!
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Hier ist deine Übersicht für das Wetterstationsprojekt.'
                            : 'Here is your overview for the weather station project.'; ?>
                    </p>
                </div>
                <div class="text-end">
                    <div class="profile-initials mx-auto mb-1">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($displayName); ?></div>
                    <a href="../auth/account.php" class="btn btn-primary-soft btn-sm mt-2">
                        <?php echo ($lang === 'de') ? 'Mein Konto' : 'My account'; ?>
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['login_message'])): ?>
                <div class="alert alert-success mb-3">
                    <?php echo htmlspecialchars($_SESSION['login_message']); unset($_SESSION['login_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Navigation cards -->
            <hr class="my-4">
            <div class="row g-3">

                <div class="col-md-3 col-sm-6">
                    <a href="user_stations.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">📡</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My stations'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Stationen anzeigen und verwalten.'
                                    : 'View and manage your stations.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="user_measurements.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">📊</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Temperatur, Luftfeuchtigkeit und mehr ansehen.'
                                    : 'View temperature, humidity, and more.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="user_friends.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">🤝</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Freunde' : 'Friends'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Freundschaften verwalten und Daten teilen.'
                                    : 'Manage friends and share data.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="user_collections.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">📁</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Sammlungen' : 'Collections'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Messreihen in Sammlungen gruppieren.'
                                    : 'Group measurements into collections.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="shop.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">🛒</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Shop' : 'Shop'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Sensoren und Zubehör kaufen.'
                                    : 'Buy sensors and accessories.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

                <div class="col-md-3 col-sm-6">
                    <a href="support.php" class="text-decoration-none">
                        <div class="glass-card p-3 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <span class="me-2" style="font-size:1.4rem;">🎫</span>
                                <span class="fw-semibold">
                                    <?php echo ($lang === 'de') ? 'Support' : 'Support'; ?>
                                </span>
                            </div>
                            <p class="mb-0 small glass-card-sub">
                                <?php echo ($lang === 'de')
                                    ? 'Anfragen und Beschwerden einreichen.'
                                    : 'Submit requests and complaints.'; ?>
                            </p>
                        </div>
                    </a>
                </div>

            </div>

            <!-- Quick-action buttons -->
            <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="user_stations.php" class="btn btn-primary-soft btn-sm">
                    <?php echo ($lang === 'de') ? '+ Neue Station' : '+ New station'; ?>
                </a>
                <a href="user_collections.php" class="btn btn-chip btn-sm">
                    <?php echo ($lang === 'de') ? '+ Neue Sammlung' : '+ New collection'; ?>
                </a>
                <a href="user_friends.php" class="btn btn-chip btn-sm">
                    <?php echo ($lang === 'de') ? '+ Freund hinzufügen' : '+ Add friend'; ?>
                </a>
            </div>
        </section>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
