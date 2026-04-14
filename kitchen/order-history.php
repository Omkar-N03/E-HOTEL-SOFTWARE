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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History | <?php echo $_SESSION['hotel_name']; ?></title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media (max-width: 768px) {
            .filter-form { flex-direction: column; gap: 1rem; }
            .filter-group { width: 100%; }
            .align-end { align-items: stretch; }
            .align-end button, .align-end a { width: 100%; }
            table { font-size: 0.9rem; }
            .items-cell { max-width: 150px; overflow: hidden; text-overflow: ellipsis; }
        }
    </style>
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
                    <label for="filter-date">Select Date</label>
                    <input type="date" id="filter-date" name="date" value="<?php echo $date_filter; ?>">
                </div>
                <div class="filter-group">
                    <label for="filter-table">Table No.</label>
                    <input type="number" id="filter-table" name="table" placeholder="All Tables" value="<?php echo $table_filter; ?>">
                </div>
                <div class="filter-group align-end">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                    <a href="order-history.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </section>

        <section class="table-section card">
            <div class="table-responsive">
                <table role="table">
                    <thead>
                        <tr>
                            <th scope="col">Order ID</th>
                            <th scope="col">Table</th>
                            <th scope="col">Items Ordered</th>
                            <th scope="col">Total Price</th>
                            <th scope="col">Completion Time</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center">No orders found for this criteria.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order ID">#<?php echo $order['id']; ?></td>
                            <td data-label="Table"><span class="table-tag">T-<?php echo $order['table_number']; ?></span></td>
                            <td data-label="Items" class="items-cell"><?php echo htmlspecialchars($order['items_summary']); ?></td>
                            <td data-label="Total"><strong>₹<?php echo number_format($order['total_price'], 2); ?></strong></td>
                            <td data-label="Time"><?php echo date('d M, H:i', strtotime($order['order_time'])); ?></td>
                            <td data-label="Action">
                                <button class="btn-icon" onclick="window.print()" title="Print Receipt" aria-label="Print Receipt for Order #<?php echo $order['id']; ?>">
                                    <i class="fa fa-print" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

</body>
</html>
