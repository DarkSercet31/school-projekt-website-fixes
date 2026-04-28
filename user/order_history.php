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

// Load orders
$stmt = $pdo->prepare(
    "SELECT pk_order_id, ordered_at, total, status
     FROM orders
     WHERE fk_username = ?
     ORDER BY ordered_at DESC"
);
$stmt->execute([$username]);
$orders = $stmt->fetchAll();

// Load all order items for these orders in one query
$orderIds = array_column($orders, 'pk_order_id');
$itemsMap = [];
if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $stmt2 = $pdo->prepare(
        "SELECT fk_order_id, product_name, price, quantity
         FROM order_item
         WHERE fk_order_id IN ($placeholders)
         ORDER BY pk_item_id"
    );
    $stmt2->execute($orderIds);
    foreach ($stmt2->fetchAll() as $row) {
        $itemsMap[$row['fk_order_id']][] = $row;
    }
}

include '../includes/header.php';
?>

<main class="main-shell">
    <div class="container-xxl px-3">

        <section class="glass-card mb-3">
            <div class="glass-card-header">
                <div>
                    <h1 class="glass-card-title mb-0">
                        <?php echo ($lang === 'de') ? 'Bestellhistorie' : 'Order history'; ?>
                    </h1>
                    <p class="glass-card-sub mb-0">
                        <?php echo ($lang === 'de') ? 'Alle deine bisherigen Bestellungen.' : 'All your past orders.'; ?>
                    </p>
                </div>
                <a href="shop.php" class="btn btn-chip btn-sm">
                    <?php echo ($lang === 'de') ? 'Zurück zum Shop' : 'Back to shop'; ?>
                </a>
            </div>
        </section>

        <?php if (empty($orders)): ?>
            <div class="glass-card text-center p-5">
                <p class="text-muted mb-3">
                    <?php echo ($lang === 'de') ? 'Noch keine Bestellungen.' : 'No orders yet.'; ?>
                </p>
                <a href="shop.php" class="btn btn-primary-soft">
                    <?php echo ($lang === 'de') ? 'Zum Shop' : 'Go to shop'; ?>
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <section class="glass-card mb-3">
                <div class="glass-card-header">
                    <div>
                        <span class="glass-card-sub small">
                            #<?php echo (int)$order['pk_order_id']; ?>
                            &nbsp;·&nbsp;
                            <?php echo htmlspecialchars($order['ordered_at']); ?>
                        </span>
                        <h2 class="glass-card-title mb-0">
                            €<?php echo number_format($order['total'], 2); ?>
                        </h2>
                    </div>
                    <span class="badge bg-success"><?php echo htmlspecialchars($order['status']); ?></span>
                </div>
                <?php $lineItems = $itemsMap[$order['pk_order_id']] ?? []; ?>
                <?php if (!empty($lineItems)): ?>
                <div class="table-responsive mt-2">
                    <table class="table-glass w-100">
                        <thead>
                            <tr>
                                <th><?php echo ($lang === 'de') ? 'Produkt' : 'Product'; ?></th>
                                <th class="text-center"><?php echo ($lang === 'de') ? 'Menge' : 'Qty'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'de') ? 'Stückpreis' : 'Unit price'; ?></th>
                                <th class="text-end"><?php echo ($lang === 'de') ? 'Summe' : 'Subtotal'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($lineItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="text-center"><?php echo (int)$item['quantity']; ?></td>
                                <td class="text-end">€<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-end">€<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
