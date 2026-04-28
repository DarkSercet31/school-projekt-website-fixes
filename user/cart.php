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
if (!empty($_SESSION['cart_flash'])) {
    $flash = $_SESSION['cart_flash'];
    unset($_SESSION['cart_flash']);
}

// Load cart items
$stmt = $pdo->prepare(
    "SELECT c.pk_cart_id, c.quantity, p.pk_product_id, p.name, p.price, p.stock
     FROM cart c
     JOIN product p ON c.fk_product_id = p.pk_product_id
     WHERE c.fk_username = ?
     ORDER BY c.pk_cart_id"
);
$stmt->execute([$username]);
$items = $stmt->fetchAll();

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Warenkorb' : 'Shopping cart'; ?>
                    </h1>
                </div>
                <a href="shop.php" class="btn btn-chip btn-sm">
                    ← <?php echo ($lang === 'de') ? 'Weiter einkaufen' : 'Continue shopping'; ?>
                </a>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="alert alert-success mb-3"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="glass-card text-center p-5">
                <p class="text-muted mb-3">
                    <?php echo ($lang === 'de') ? 'Dein Warenkorb ist leer.' : 'Your cart is empty.'; ?>
                </p>
                <a href="shop.php" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Zum Shop' : 'Go to shop'; ?>
                </a>
            </div>
        <?php else: ?>

            <section class="glass-card mb-3">
                <div class="table-responsive">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Produkt' : 'Product'; ?></th>
                                <th class="text-center"><?php echo ($lang === 'de') ? 'Menge' : 'Qty'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'de') ? 'Preis' : 'Price'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'de') ? 'Summe' : 'Subtotal'; ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                        <form method="post" action="../includes/cart.inc.php" class="d-inline">
                                            <input type="hidden" name="action" value="decrease">
                                            <input type="hidden" name="product_id"
                                                   value="<?php echo (int)$item['pk_product_id']; ?>">
                                            <button type="submit" class="btn btn-chip btn-sm px-2">−</button>
                                        </form>
                                        <span class="fw-semibold"><?php echo (int)$item['quantity']; ?></span>
                                        <form method="post" action="../includes/cart.inc.php" class="d-inline">
                                            <input type="hidden" name="action" value="increase">
                                            <input type="hidden" name="product_id"
                                                   value="<?php echo (int)$item['pk_product_id']; ?>">
                                            <button type="submit" class="btn btn-chip btn-sm px-2"
                                                    <?php echo $item['quantity'] >= $item['stock'] ? 'disabled' : ''; ?>>+</button>
                                        </form>
                                    </div>
                                </td>
                                <td class="text-end">€<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end fw-semibold">
                                    €<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </td>
                                <td class="text-end">
                                    <form method="post" action="../includes/cart.inc.php">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id"
                                               value="<?php echo (int)$item['pk_product_id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">✕</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="glass-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="glass-card-sub"><?php echo ($lang === 'de') ? 'Gesamtbetrag' : 'Total'; ?></span>
                        <h2 class="glass-card-title mb-0">€<?php echo number_format($total, 2); ?></h2>
                    </div>
                    <form method="post" action="../includes/checkout.inc.php">
                        <button type="submit" class="btn btn-primary-soft">
                            <?php echo ($lang === 'de') ? 'Jetzt kaufen' : 'Checkout'; ?>
                        </button>
                    </form>
                </div>
            </section>

        <?php endif; ?>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
