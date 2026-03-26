<?php
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hotel_id = $_POST['hotel_id'] ?? null;
    $table_id = $_POST['table_id'] ?? null;
    $cart_json = $_POST['cart_data'] ?? '[]';
    $cart = json_decode($cart_json, true);

    $table_no = $_SESSION['customer_table_no'] ?? 'N/A';

    if (empty($cart) || !$hotel_id) {
        header("Location: ../customer/menu.php?error=empty_cart");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $total_price = 0;
        $items_array = [];

        foreach ($cart as $item) {
            $total_price += ($item['price'] * $item['qty']);
            $items_array[] = $item['qty'] . "x " . $item['name'];
        }

        $items_summary = implode(", ", $items_array);
        $stmt = $pdo->prepare("INSERT INTO orders 
            (hotel_id, table_number, items_summary, total_amount, status, order_time, table_id) 
            VALUES (?, ?, ?, ?, 'Pending', NOW(), ?)");

        $stmt->execute([
            $hotel_id,
            $table_no,
            $items_summary,
            $total_price,
            $table_id
        ]);

        $order_id = $pdo->lastInsertId();
        try {
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");

            foreach ($cart as $item) {
                $itemStmt->execute([
                    $order_id,
                    $item['id'],
                    $item['qty'],
                    $item['price']
                ]);
            }

        } catch (Exception $e) {
        }
        $pdo->commit();
        header("Location: ../customer/order-success.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Order Error: " . $e->getMessage());
    }
}