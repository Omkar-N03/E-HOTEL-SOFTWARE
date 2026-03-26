<?php
session_start();

$order_id = $_GET['order_id'] ?? null;
$table_no = $_SESSION['customer_table_no'] ?? 'N/A';
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Placed</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f7f6;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.popup {
    background: white;
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    width: 320px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.success-icon {
    font-size: 50px;
    color: #2ecc71;
}

h2 {
    margin: 15px 0;
}

.btn {
    display: block;
    width: 100%;
    margin-top: 10px;
    padding: 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

.btn-order {
    background: #3498db;
    color: white;
}

.btn-track {
    background: #2ecc71;
    color: white;
}
</style>
</head>

<body>

<div class="popup">
    <div class="success-icon">✔</div>
    
    <h2>Order Placed!</h2>
    <p>Your order for <strong>Table <?php echo $table_no; ?></strong> has been sent to kitchen.</p>

    <a href="menu.php" class="btn btn-order">🍽️ Order More</a>
    
    <a href="tracks-order.php?order_id=<?php echo $order_id; ?>" class="btn btn-track">
        📦 Track Order
    </a>
</div>

</body>
</html>