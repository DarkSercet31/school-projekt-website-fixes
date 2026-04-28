<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([]);
    exit;
}

$username = $_SESSION['pk_username'] ?? '';
$with     = trim($_GET['with'] ?? '');
$after    = (int)($_GET['after'] ?? 0);

if ($with === '' || $with === $username) {
    echo json_encode([]);
    exit;
}

// Mark incoming messages as read while polling
$pdo->prepare(
    "UPDATE message SET is_read = 1 WHERE fk_sender = ? AND fk_receiver = ?"
)->execute([$with, $username]);

$stmt = $pdo->prepare(
    "SELECT pk_message_id, fk_sender, body, sent_at
     FROM message
     WHERE pk_message_id > ?
       AND ((fk_sender = ? AND fk_receiver = ?)
         OR (fk_sender = ? AND fk_receiver = ?))
     ORDER BY pk_message_id ASC
     LIMIT 50"
);
$stmt->execute([$after, $username, $with, $with, $username]);
$rows = $stmt->fetchAll();

echo json_encode($rows);
