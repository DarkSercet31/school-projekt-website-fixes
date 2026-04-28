<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/db_connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['pk_username'] ?? '';

// Load cart items
$stmt = $pdo->prepare(
    "SELECT c.quantity, p.pk_product_id, p.name, p.price, p.stock
     FROM cart c
     JOIN product p ON c.fk_product_id = p.pk_product_id
     WHERE c.fk_username = ?"
);
$stmt->execute([$username]);
$items = $stmt->fetchAll();

if (empty($items)) {
    header('Location: ../user/cart.php');
    exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

// Create order header
$pdo->prepare(
    "INSERT INTO orders (fk_username, total) VALUES (?, ?)"
)->execute([$username, $total]);

$orderId = (int)$pdo->lastInsertId();

// Insert line items (snapshot product name + price at purchase time)
$insertItem = $pdo->prepare(
    "INSERT INTO order_item (fk_order_id, product_name, price, quantity)
     VALUES (?, ?, ?, ?)"
);
foreach ($items as $item) {
    $insertItem->execute([
        $orderId,
        $item['name'],
        $item['price'],
        $item['quantity'],
    ]);
}

// Clear cart
$pdo->prepare("DELETE FROM cart WHERE fk_username = ?")->execute([$username]);

$_SESSION['cart_flash'] = 'Order placed successfully! Order #' . $orderId;
header('Location: ../user/order_history.php');
exit;
