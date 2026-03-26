<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['hotel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Login required.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['id'] ?? $_POST['id'] ?? null;
$new_status = $input['status'] ?? $_POST['status'] ?? null;
$hotel_id = $_SESSION['hotel_id'];

$allowed_statuses = ['pending', 'preparing', 'served', 'cancelled'];

if (!$order_id || !in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Order ID or Status.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET status = ?, 
            updated_at = NOW() 
        WHERE id = ? AND hotel_id = ?
    ");
    $result = $stmt->execute([$new_status, $order_id, $hotel_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => "Order #$order_id is now " . ucfirst($new_status),
            'current_status' => $new_status
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Order not found or no changes made.'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
