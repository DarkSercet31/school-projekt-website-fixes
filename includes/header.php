<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default values
$lang = 'en';
$isAdmin = false;

// Get language from session or GET parameter
if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['de', 'en'], true)) {
    $lang = $_SESSION['lang'];
}

// Get language from URL if provided
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'], true)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}

// Check if user is admin
if (isset($_SESSION['role'])) {
    $isAdmin = ($_SESSION['role'] === 'Admin' || $_SESSION['role'] === 'admin');
}

$_headerUnread   = 0;
$_headerCartQty  = 0;
if (!empty($_SESSION['loggedin']) && isset($pdo)) {
    $hUser = $_SESSION['pk_username'] ?? '';
    if ($hUser !== '') {
        $s = $pdo->prepare(
            "SELECT (SELECT COUNT(*) FROM message WHERE fk_receiver=? AND is_read=0) +
                    (SELECT COUNT(*) FROM support_reply sr
                     JOIN support_ticket st ON sr.fk_ticket_id=st.pk_ticket_id
                     WHERE st.fk_username=? AND sr.is_read=0) AS total"
        );
        $s->execute([$hUser, $hUser]);
        $_headerUnread = (int)($s->fetchColumn() ?: 0);

        $c = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart WHERE fk_username=?");
        $c->execute([$hUser]);
        $_headerCartQty = (int)($c->fetchColumn() ?: 0);
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>" data-theme="dark">
<head>
    <meta charset="utf-8">
    <title>Portable Indoor Feedback</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap & Layout -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/sidebars.css">
<style>
    /* Improved navbar styling - Cleaner Version */
    .app-navbar {
        background: #1a1a2e;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0.5rem 0;
        height: 64px;
    }

    .navbar-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    .navbar-brand-badge {
        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 1.2rem;
        margin-right: 12px;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .navbar-brand span:last-child {
        color: #ffffff;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.3px;
    }

    /* Navigation Center - Clean & Spaced */
    .navbar-nav.mx-auto {
        gap: 0.5rem;
    }

    .nav-link {
        color: #cbd5e1 !important;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 0.5rem 1rem !important;
        border-radius: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
        min-width: 80px;
        text-align: center;
    }

    .nav-link:hover {
        color: #ffffff !important;
        background: rgba(67, 97, 238, 0.15);
    }

    .nav-link.active {
        color: #ffffff !important;
        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.25);
        font-weight: 600;
    }

    /* Profile section - Clean */
    .profile-toggle {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 50px;
        padding: 6px 15px 6px 6px !important;
        transition: all 0.2s ease;
        min-width: 140px;
    }

    .profile-toggle:hover {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: rgba(67, 97, 238, 0.3) !important;
    }

    .profile-initials {
        background: linear-gradient(135deg, #7209b7 0%, #560bad 100%);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.9rem;
        margin-right: 10px;
        flex-shrink: 0;
    }

    .profile-name {
        color: #f8f9fa;
        font-weight: 500;
        font-size: 0.85rem;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80px;
    }

    /* Dropdown - Clean */
    .dropdown-menu {
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        padding: 0.5rem;
        min-width: 220px;
    }

    .dropdown-item {
        color: #e2e8f0;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        transition: all 0.15s ease;
    }

    .dropdown-item:hover {
        background: rgba(67, 97, 238, 0.15);
        color: #ffffff;
    }

    /* Language buttons - Clean */
    .btn-group-sm {
        border-radius: 6px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-outline-light {
        color: #94a3b8;
        border: none;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        background: rgba(255, 255, 255, 0.05);
    }

    .btn-outline-light.active {
        background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
        color: white;
    }

    /* Theme toggle - Clean */
    .theme-toggle {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        font-size: 0.85rem;
        padding: 0.25rem 0;
    }

    .theme-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* Mobile responsiveness */
    @media (max-width: 991.98px) {
        .navbar-container {
            padding: 0 1rem;
        }
        
        .navbar-collapse {
            background: #1a1a2e;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .navbar-nav.mx-auto {
            gap: 0.25rem;
            margin-bottom: 1rem;
        }
        
        .nav-link {
            text-align: left;
            padding: 0.75rem 1rem !important;
            margin-bottom: 2px;
        }
        
        .profile-toggle {
            width: 100%;
            justify-content: center;
        }
    }

    /* Fix for overlapping text */
    .navbar-nav {
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none; /* Firefox */
    }
    
    .navbar-nav::-webkit-scrollbar {
        display: none; /* Chrome/Safari */
    }

    /* Ensure proper spacing */
    .navbar-brand {
        margin-right: 2rem;
    }
    
    .navbar-nav > .nav-item {
        margin: 0 2px;
    }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg app-navbar fixed-top">
    <div class="container-fluid">
        <!-- LINKS: Logo -->
        <a class="navbar-brand d-flex align-items-center" href="../user/dashboard.php">
            <span class="navbar-brand-badge">PI</span>
            <span class="fw-medium" style="font-size:.9rem;">Portable Indoor Feedback</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <!-- MITTE: Navigation -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="../user/dashboard.php"
                       class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' active' : ''; ?>">
                        <?php echo ($lang === 'de') ? 'Home' : 'Home'; ?>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a href="../admin/index.php"
                           class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? ' active' : ''; ?>">
                            Admin
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="../user/user_stations.php"
                       class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'user_stations.php' ? ' active' : ''; ?>">
                        <?php echo ($lang === 'de') ? 'Meine Stationen' : 'My stations'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../user/user_friends.php"
                       class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'user_friends.php' ? ' active' : ''; ?>">
                        <?php echo ($lang === 'de') ? 'Freunde' : 'Friends'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../user/user_collections.php"
                       class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'user_collections.php' ? ' active' : ''; ?>">
                        <?php echo ($lang === 'de') ? 'Sammlungen' : 'Collections'; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../user/user_measurements.php"
                       class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'user_measurements.php' ? ' active' : ''; ?>">
                        <?php echo ($lang === 'de') ? 'Messwerte' : 'Measurements'; ?>
                    </a>
                </li>
            </ul>

            <!-- RECHTS: Profil-Emblem + Dropdown -->
            <?php
            $displayName = $_SESSION['pk_username'] ?? 'User';
            $initials    = strtoupper(substr($displayName, 0, 1));
            ?>
            <div class="d-flex align-items-center gap-2">

                <?php if (!empty($_SESSION['loggedin'])): ?>
                <!-- Notification bell -->
                <a href="../user/notifications.php"
                   class="btn btn-sm position-relative"
                   style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:.35rem .55rem;"
                   title="<?php echo ($lang === 'de') ? 'Benachrichtigungen' : 'Notifications'; ?>">
                    <i class="bi bi-bell"></i>
                    <?php if ($_headerUnread > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                              style="font-size:.65rem;">
                            <?php echo min($_headerUnread, 99); ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Cart icon -->
                <a href="../user/cart.php"
                   class="btn btn-sm position-relative"
                   style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:.35rem .55rem;"
                   title="<?php echo ($lang === 'de') ? 'Warenkorb' : 'Cart'; ?>">
                    <i class="bi bi-cart3"></i>
                    <?php if ($_headerCartQty > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                              style="font-size:.65rem;">
                            <?php echo min($_headerCartQty, 99); ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn profile-toggle d-flex align-items-center"
                            type="button" id="profileDropdown" data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <span class="profile-initials">
                            <?php echo htmlspecialchars($initials); ?>
                        </span>
                        <span class="profile-name">
                            <?php echo htmlspecialchars($displayName); ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li class="dropdown-item-text fw-semibold">
                            <?php echo htmlspecialchars($displayName); ?>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../auth/account.php">
                                <?php echo ($lang === 'de') ? 'Profil bearbeiten' : 'Edit profile'; ?>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li class="dropdown-item-text small">
                            <?php echo ($lang === 'de') ? 'Sprache' : 'Language'; ?>
                        </li>
                        <li class="px-3 pb-2">
                            <div class="btn-group btn-group-sm w-100" role="group" aria-label="Language">
                                <a href="?lang=en"
                                   class="btn btn-outline-light<?php echo $lang === 'en' ? ' active' : ''; ?>">EN</a>
                                <a href="?lang=de"
                                   class="btn btn-outline-light<?php echo $lang === 'de' ? ' active' : ''; ?>">DE</a>
                            </div>
                        </li>

                        <li class="dropdown-item-text small">
                            Theme
                        </li>
                        <li class="px-3 pb-2">
                            <button type="button"
                                    class="btn theme-toggle btn-sm w-100"
                                    id="themeToggle">
                                Light
                            </button>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <form method="post" action="../includes/logout.inc.php" class="px-3 mb-2">
                                <button class="btn btn-sm btn-outline-danger w-100" type="submit">
                                    Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div><!-- /dropdown -->
            </div><!-- /d-flex -->

        </div>
    </div>
</nav>

<!-- Bootstrap + Theme-Toggle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('themeToggle');
    const html = document.documentElement;

    function updateToggleLabel() {
        const mode = html.getAttribute('data-theme') || 'dark';
        if (toggleBtn) {
            toggleBtn.textContent = mode === 'dark' ? 'Light' : 'Dark';
        }
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const current = html.getAttribute('data-theme') || 'dark';
            html.setAttribute('data-theme', current === 'dark' ? 'light' : 'dark');
            updateToggleLabel();
        });
        updateToggleLabel();
    }
</script>