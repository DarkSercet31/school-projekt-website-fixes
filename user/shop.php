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

$flash = '';
if (!empty($_SESSION['shop_flash'])) {
    $flash = $_SESSION['shop_flash'];
    unset($_SESSION['shop_flash']);
}

// Load all products
$products = $pdo->query("SELECT * FROM product ORDER BY pk_product_id")->fetchAll();

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Shop' : 'Shop'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de')
                            ? 'Sensoren und Zubehör für deine Wetterstation.'
                            : 'Sensors and accessories for your weather station.'; ?>
                    </p>
                </div>
                <a href="cart.php" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Warenkorb' : 'Cart'; ?>
                </a>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($products as $p): ?>
            <div class="col-md-4 col-sm-6">
                <div class="glass-card h-100 d-flex flex-column">
                    <?php if ($p['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>"
                             class="rounded-top" style="height:180px;object-fit:cover;" alt="">
                    <?php else: ?>
                        <div class="rounded-top d-flex align-items-center justify-content-center"
                             style="height:120px;background:rgba(67,97,238,.12);">
                            <span style="font-size:2.5rem;">📦</span>
                        </div>
                    <?php endif; ?>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <h2 class="glass-card-title mb-1" style="font-size:1rem;">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </h2>
                        <p class="glass-card-sub small flex-grow-1">
                            <?php echo htmlspecialchars($p['description'] ?? ''); ?>
                        </p>
                        <div class="d-flex align-items-center justify-content-between mt-2">
                            <span class="fw-bold" style="color:var(--accent,#4361ee);">
                                €<?php echo number_format($p['price'], 2); ?>
                            </span>
                            <?php if ($p['stock'] > 0): ?>
                                <form method="post" action="../includes/cart.inc.php">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id"
                                           value="<?php echo (int)$p['pk_product_id']; ?>">
                                    <button type="submit" class="btn btn-primary-soft btn-sm">
                                        <?php echo ($lang === 'de') ? 'In den Warenkorb' : 'Add to cart'; ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <?php echo ($lang === 'de') ? 'Ausverkauft' : 'Out of stock'; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <p class="text-muted">
                        <?php echo ($lang === 'de') ? 'Keine Produkte vorhanden.' : 'No products available.'; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
