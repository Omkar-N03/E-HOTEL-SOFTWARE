<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];

$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$table_filter = isset($_GET['table']) ? (int)$_GET['table'] : '';

$query = "SELECT * FROM orders WHERE hotel_id = ? AND status = 'served'";
$params = [$hotel_id];

if ($date_filter) {
    $query .= " AND DATE(order_time) = ?";
    $params[] = $date_filter;
}
if ($table_filter) {
    $query .= " AND table_number = ?";
    $params[] = $table_filter;
}
$query .= " ORDER BY order_time DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $total_revenue = 0;
    foreach($orders as $o) { $total_revenue += $o['total_price']; }
} catch (PDOException $e) {
    die("History Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History | <?php echo $_SESSION['hotel_name']; ?></title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="admin-container">
    <nav class="sidebar">
        <div class="hotel-profile"><h3>Admin History</h3></div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li><a href="../admin/manage-menu.php"><i class="fa fa-utensils"></i> Manage Menu</a></li>
            <li class="active"><a href="order-history.php"><i class="fa fa-history"></i> Order History</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header class="section-header">
            <h1>Sales & Order History</h1>
            <div class="total-badge">
                Filtered Revenue: <strong>₹<?php echo number_format($total_revenue, 2); ?></strong>
            </div>
        </header>

        <section class="filter-bar card">
            <form method="GET" action="order-history.php" class="filter-form">
                <div class="filter-group">
                    <label>Select Date</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="filter-group">
                    <label>Table No.</label>
                    <input type="number" name="table" placeholder="All Tables" value="<?php echo $table_filter; ?>">
                </div>
                <div class="filter-group align-end">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="order-history.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </section>

        <section class="table-section card">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Table</th>
                        <th>Items Ordered</th>
                        <th>Total Price</th>
                        <th>Completion Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="6" class="text-center">No orders found for this criteria.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><span class="table-tag">T-<?php echo $order['table_number']; ?></span></td>
                        <td class="items-cell"><?php echo htmlspecialchars($order['items_summary']); ?></td>
                        <td><strong>₹<?php echo number_format($order['total_price'], 2); ?></strong></td>
                        <td><?php echo date('d M, H:i', strtotime($order['order_time'])); ?></td>
                        <td>
                            <button class="btn-icon" onclick="window.print()" title="Print Receipt">
                                <i class="fa fa-print"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

</body>
</html>
