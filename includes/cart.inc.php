<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/db_connection.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../auth/login.php');
    exit;
}

$username  = $_SESSION['pk_username'] ?? '';
$action    = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId > 0 && $username !== '') {
    // Verify product exists
    $check = $pdo->prepare("SELECT pk_product_id, stock FROM product WHERE pk_product_id = ?");
    $check->execute([$productId]);
    $product = $check->fetch();

    if ($product) {
        if ($action === 'add') {
            $pdo->prepare(
                "INSERT INTO cart (fk_username, fk_product_id, quantity)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)"
            )->execute([$username, $productId, $product['stock']]);

            $_SESSION['shop_flash'] = 'Item added to cart.';
            header('Location: ../user/shop.php');
            exit;
        }

        if ($action === 'increase') {
            $pdo->prepare(
                "UPDATE cart SET quantity = LEAST(quantity + 1, ?)
                 WHERE fk_username = ? AND fk_product_id = ?"
            )->execute([$product['stock'], $username, $productId]);
        }

        if ($action === 'decrease') {
            // Get current quantity first
            $qStmt = $pdo->prepare(
                "SELECT quantity FROM cart WHERE fk_username = ? AND fk_product_id = ?"
            );
            $qStmt->execute([$username, $productId]);
            $row = $qStmt->fetch();

            if ($row && $row['quantity'] <= 1) {
                $pdo->prepare(
                    "DELETE FROM cart WHERE fk_username = ? AND fk_product_id = ?"
                )->execute([$username, $productId]);
            } else {
                $pdo->prepare(
                    "UPDATE cart SET quantity = quantity - 1
                     WHERE fk_username = ? AND fk_product_id = ?"
                )->execute([$username, $productId]);
            }
        }

        if ($action === 'remove') {
            $pdo->prepare(
                "DELETE FROM cart WHERE fk_username = ? AND fk_product_id = ?"
            )->execute([$username, $productId]);
        }
    }
}

header('Location: ../user/cart.php');
exit;
