<?php

header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

$hotel_id = $_SESSION['hotel_id'] ?? null;

if (!$hotel_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.table_number,
            t.seating_capacity,
            o.id AS active_order_id,
            o.status AS order_status,
            o.order_time,
            o.items_summary,
            TIMESTAMPDIFF(MINUTE, o.order_time, NOW()) AS minutes_waiting
        FROM tables t
        LEFT JOIN orders o ON t.table_number = o.table_number 
            AND t.hotel_id = o.hotel_id 
            AND o.status IN ('pending', 'preparing')
        WHERE t.hotel_id = ?
        ORDER BY t.table_number ASC
    ");
    
    $stmt->execute([$hotel_id]);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $table_data = [];
    foreach ($tables as $row) {
        $status = 'available'; 
        
        if ($row['active_order_id']) {
            $status = ($row['order_status'] === 'pending') ? 'waiting' : 'occupied';
        }

        $table_data[] = [
            'number' => $row['table_number'],
            'capacity' => $row['seating_capacity'],
            'status' => $status, 
            'order_id' => $row['active_order_id'],
            'wait_time' => $row['minutes_waiting'] ?? 0,
            'summary' => $row['items_summary'] ?: 'No active orders'
        ];
    }

    echo json_encode([
        'success' => true,
        'hotel_id' => $hotel_id,
        'table_count' => count($table_data),
        'tables' => $table_data
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query Error: ' . $e->getMessage()
    ]);
}