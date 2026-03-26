<?php
header('Content-Type: application/json');
require_once '../config/db.php';
session_start();
$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;

if (!$item_id) {
    echo json_encode([
        'success' => false, 
        'message' => 'Item ID is required.'
    ]);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            name, 
            calories, 
            protein, 
            carbs, 
            fats, 
            description,
            image_url
        FROM menu_items 
        WHERE id = ? AND is_available = 1
    ");
    
    $stmt->execute([$item_id]);
    $nutrition = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($nutrition) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $nutrition['id'],
                'name' => $nutrition['name'],
                'calories' => (int)$nutrition['calories'],
                'protein' => (int)$nutrition['protein'],
                'carbs' => (int)$nutrition['carbs'],
                'fats' => (int)$nutrition['fats'],
                'summary' => $nutrition['description']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nutrition data not found or item unavailable.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}