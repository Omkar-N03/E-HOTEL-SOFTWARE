<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['hotel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$hotel_id = $_SESSION['hotel_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $requested_status = isset($_POST['status']) ? (int)$_POST['status'] : null;

    if ($item_id > 0) {
        try {
            if ($requested_status !== null) {
                $stmt = $pdo->prepare("UPDATE menu_items SET is_available = ? WHERE id = ? AND hotel_id = ?");
                $stmt->execute([$requested_status, $item_id, $hotel_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id = ? AND hotel_id = ?");
                $stmt->execute([$item_id, $hotel_id]);
            }

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Stock status updated successfully', 'new_state' => $requested_status]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes made or item not found']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Item ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
