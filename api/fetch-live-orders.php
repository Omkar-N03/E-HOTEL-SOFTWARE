<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$hotel_id = $_SESSION['hotel_id'] ?? $_GET['hotel_id'] ?? null;

if (!$hotel_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized: Hotel ID missing.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            table_number, 
            items_summary, 
            total_price, 
            status, 
            order_time 
        FROM orders 
        WHERE hotel_id = ? 
        AND status IN ('pending', 'preparing') 
        ORDER BY 
            FIELD(status, 'pending', 'preparing') ASC, 
            order_time ASC
    ");
    
    $stmt->execute([$hotel_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($orders),
        'orders' => $orders
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred.'
    ]);
}
