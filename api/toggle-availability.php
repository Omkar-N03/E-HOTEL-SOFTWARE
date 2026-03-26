<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['hotel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$item_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$hotel_id = $_SESSION['hotel_id'];

if (!$item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid Item ID.']);
    exit();
}

try {
    $checkStmt = $pdo->prepare("SELECT is_available FROM menu_items WHERE id = ? AND hotel_id = ?");
    $checkStmt->execute([$item_id, $hotel_id]);
    $item = $checkStmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found.']);
        exit();
    }

    $new_status = ($item['is_available'] == 1) ? 0 : 1;
    $updateStmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ? AND hotel_id = ?");
    $result = $updateStmt->execute([$new_status, $item_id, $hotel_id]);

    if ($result) {
        echo json_encode([
            'success' => true, 
            'new_status' => $new_status,
            'message' => ($new_status == 1) ? 'Item is now Active' : 'Item marked as Sold Out'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
