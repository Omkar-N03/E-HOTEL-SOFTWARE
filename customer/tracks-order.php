<?php
require_once '../config/db.php';
session_start();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : ($_SESSION['last_order_id'] ?? null);

if (!$order_id) {
    header("Location: menu.php");
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT o.*, h.hotel_name, h.logo 
        FROM orders o 
        JOIN hotels h ON o.hotel_id = h.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die("Order not found.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$status_map = [
    'pending'   => 25,
    'preparing' => 65,
    'served'    => 100
];

$progress = $status_map[$order['status']] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="15">
<title>Track Order</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: #333;
}
.header {
    text-align: center;
    color: white;
    padding: 20px;
}
.header img {
    width: 60px;
    border-radius: 50%;
}
.container {
    max-width: 500px;
    margin: -30px auto 20px;
    background: white;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.progress-bar {
    background: #eee;
    border-radius: 20px;
    height: 10px;
    overflow: hidden;
    margin: 20px 0;
}
.progress-fill {
    height: 100%;
    background: #2ecc71;
    transition: width 0.5s ease-in-out;
}
.steps {
    display: flex;
    justify-content: space-between;
    text-align: center;
}
.step {
    flex: 1;
    font-size: 12px;
    color: #aaa;
}
.step.active {
    color: #2ecc71;
    font-weight: bold;
}
.status-box {
    text-align: center;
    margin: 20px 0;
}
.status-box i {
    font-size: 40px;
    margin-bottom: 10px;
}
.summary {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 10px;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}
.total {
    margin-top: 10px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
}
.btn {
    display: block;
    text-align: center;
    padding: 12px;
    margin-top: 15px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
}
.btn-order {
    background: #3498db;
    color: white;
}
@media(max-width: 500px) {
    .container {
        margin: -20px 10px;
    }
}
</style>
</head>
<body>
<div class="header">
    <img src="../assets/img/logos/<?php echo $order['logo'] ?: 'default.png'; ?>">
    <h2><?php echo $order['hotel_name']; ?></h2>
    <p>Order #<?php echo $order_id; ?> • Table <?php echo $order['table_number']; ?></p>
</div>
<div class="container">
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
    </div>
    <div class="steps">
        <div class="step <?php echo ($progress >= 25) ? 'active' : ''; ?>">Received</div>
        <div class="step <?php echo ($progress >= 65) ? 'active' : ''; ?>">Cooking</div>
        <div class="step <?php echo ($progress >= 100) ? 'active' : ''; ?>">Served</div>
    </div>
    <div class="status-box">
        <?php if($order['status'] == 'pending'): ?>
            <i class="fa fa-hourglass-half"></i>
            <h3>Order Received</h3>
            <p>Waiting for kitchen...</p>
        <?php elseif($order['status'] == 'preparing'): ?>
            <i class="fa fa-spinner fa-spin"></i>
            <h3>Cooking Your Food</h3>
            <p>Chef is preparing your meal</p>
        <?php else: ?>
            <i class="fa fa-check-circle" style="color:green;"></i>
            <h3>Order Served</h3>
            <p>Enjoy your meal 🍽️</p>
        <?php endif; ?>
    </div>
    <div class="summary">
        <h4>Order Summary</h4>
        <?php
        $items = explode(',', $order['items_summary']);
        foreach($items as $item): ?>
            <div class="summary-row">
                <span><?php echo htmlspecialchars(trim($item)); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="total">
            <span>Total:</span>
            <span>₹<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>
    <?php if($order['status'] == 'served'): ?>
        <a href="menu.php" class="btn btn-order">Order More 🍽️</a>
    <?php endif; ?>
</div>
<?php if($order['status'] == 'served' && !isset($_SESSION['alerted_'.$order_id])): ?>
    <audio autoplay>
        <source src="../assets/audio/notification.mp3" type="audio/mpeg">
    </audio>
    <?php $_SESSION['alerted_'.$order_id] = true; ?>
<?php endif; ?>
</body>
</html>
