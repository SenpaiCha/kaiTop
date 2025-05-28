<?php
// purchase.php: Handles AJAX purchase requests
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['product_id'], $data['quantity'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    exit;
}
$product_id = (int)$data['product_id'];
$quantity = (int)$data['quantity'];
if ($quantity < 1) {
    echo json_encode(['success' => false, 'error' => 'Quantity must be at least 1.']);
    exit;
}
require_once 'db.php';
// Fetch product
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND suspended = 0');
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found or suspended.']);
    exit;
}
if ($product['quantity'] < $quantity) {
    echo json_encode(['success' => false, 'error' => 'Not enough stock.']);
    exit;
}
// Prevent vendor from buying own product
if ($product['vendor'] === $_SESSION['username']) {
    echo json_encode(['success' => false, 'error' => 'You cannot buy your own product.']);
    exit;
}
$total = $product['price'] * $quantity;
// Fetch buyer cash
$stmt = $pdo->prepare('SELECT cash FROM users WHERE userName = ?');
$stmt->execute([$_SESSION['username']]);
$buyer_cash = $stmt->fetchColumn();
if ($buyer_cash === false || $buyer_cash < $total) {
    echo json_encode(['success' => false, 'error' => 'Insufficient cash.']);
    exit;
}
// Transaction: subtract from buyer, add to vendor, insert purchase, log
try {
    $pdo->beginTransaction();
    // Subtract from buyer
    $stmt = $pdo->prepare('UPDATE users SET cash = cash - ? WHERE userName = ?');
    $stmt->execute([$total, $_SESSION['username']]);
    // Add to vendor
    $stmt = $pdo->prepare('UPDATE users SET cash = cash + ? WHERE userName = ?');
    $stmt->execute([$total, $product['vendor']]);
    // Update product quantity
    $stmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
    $stmt->execute([$quantity, $product_id]);
    // Insert purchase
    $stmt = $pdo->prepare('INSERT INTO purchases (product_id, buyer, quantity, total) VALUES (?, ?, ?, ?)');
    $stmt->execute([$product_id, $_SESSION['username'], $quantity, $total]);
    // Insert purchase log
    $stmt = $pdo->prepare('INSERT INTO purchase_logs (vendor, product_id, buyer, quantity, total) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$product['vendor'], $product_id, $_SESSION['username'], $quantity, $total]);
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Transaction failed.']);
}
