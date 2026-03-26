<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(); 

$hotel_id = $_SESSION['hotel_id'];
$hotel_name = $_SESSION['hotel_name'];

$today_revenue = 0;
$active_orders = 0;
$total_items = 0;
$recent_orders = []; 

try {
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue FROM orders 
                           WHERE hotel_id = ? AND DATE(order_time) = CURDATE() 
                           AND status = 'served'");
    $stmt->execute([$hotel_id]);
    $res = $stmt->fetch();
    $today_revenue = $res['revenue'] ?? 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders 
                           WHERE hotel_id = ? AND status IN ('Pending', 'Preparing', 'pending', 'preparing')");
    $stmt->execute([$hotel_id]);
    $active_orders = $stmt->fetch()['count'] ?? 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE hotel_id = ?");
    $stmt->execute([$hotel_id]);
    $total_items = $stmt->fetch()['count'] ?? 0;
    $stmt = $pdo->prepare("SELECT id, table_id, total_amount, status, order_time 
                           FROM orders WHERE hotel_id = ? 
                           ORDER BY order_time DESC LIMIT 5");
    $stmt->execute([$hotel_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_msg = "Dashboard Data Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel_name); ?> | Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="admin-container">
    <nav class="sidebar">
        <div class="hotel-profile">
            <i class="fa fa-hotel" style="font-size: 2rem; color: #2ecc71;"></i>
            <h3><?php echo htmlspecialchars($hotel_name); ?></h3>
        </div>
        <ul>
            <li class="active"><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li><a href="manage-menu.php"><i class="fa fa-utensils"></i> Manage Menu</a></li>
            <li><a href="manage-tables.php"><i class="fa fa-border-all"></i> Tables & QR</a></li>
            <li><a href="../kitchen/live-orders.php" target="_blank"><i class="fa fa-tv"></i> Kitchen View</a></li>
            <li><a href="reports.php"><i class="fa fa-chart-line"></i> Sales Reports</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <hr style="border: 0.5px solid #3e4f5f; margin: 20px 0;">
            
            <li>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" style="color: #ff7675;">
                    <i class="fa fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <main class="main-content">
        <?php if (isset($error_msg)): ?>
            <div class="alert error" style="background: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fa fa-exclamation-triangle"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <header class="dashboard-header">
            <div class="welcome">
                <h1>Dashboard Overview</h1>
                <p>Hello <strong><?php echo htmlspecialchars($hotel_name); ?></strong>, here is what's happening today.</p>
            </div>
            <div class="quick-actions">
                <a href="manage-tables.php" class="btn btn-primary"><i class="fa fa-qrcode"></i> Generate QRs</a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon rev"><i class="fa fa-indian-rupee-sign"></i></div>
                <div class="stat-data">
                    <span class="label">Today's Sales</span>
                    <span class="value">₹<?php echo number_format($today_revenue, 2); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon ord"><i class="fa fa-clock"></i></div>
                <div class="stat-data">
                    <span class="label">Active Orders</span>
                    <span class="value"><?php echo $active_orders; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon menu"><i class="fa fa-book-open"></i></div>
                <div class="stat-data">
                    <span class="label">Total Dishes</span>
                    <span class="value"><?php echo $total_items; ?></span>
                </div>
            </div>
        </div>

        <div class="dashboard-flex" style="display: flex; gap: 25px; margin-top: 30px;">
            <section class="recent-orders-section" style="flex: 2; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="font-size: 1.2rem;">Recent Orders</h2>
                    <a href="reports.php" style="color: #2ecc71; text-decoration: none; font-size: 0.9rem;">View All Sales</a>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #f4f7f6;">
                            <th style="padding: 12px;">ID</th>
                            <th>Table</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color: #95a5a6;">No orders placed yet.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_orders as $order): ?>
                            <tr style="border-bottom: 1px solid #f4f7f6;">
                                <td style="padding: 12px; font-weight: bold;">#<?php echo $order['id']; ?></td>
                                <td>Table <?php echo htmlspecialchars($order['table_id'] ?? 'N/A'); ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-pill <?php echo $order['status']; ?>" style="padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; text-transform: capitalize;">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td style="color: #7f8c8d;"><?php echo date('h:i A', strtotime($order['order_time'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <aside class="quick-status" style="flex: 1; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); height: fit-content;">
                <h2 style="font-size: 1.1rem; margin-bottom: 20px;">System Status</h2>
                <div class="status-item" style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                    <span>Server Status</span>
                    <span style="color: #2ecc71; font-weight: bold;"><i class="fa fa-circle"></i> Online</span>
                </div>
                <div class="status-item" style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 0.95rem;">
                    <span>Orders Today</span>
                    <span style="font-weight: bold;"><?php echo count($recent_orders); ?></span>
                </div>
                <hr style="border: 0.5px solid #eee; margin: 15px 0;">
                <a href="manage-menu.php" class="btn-outline" style="display: block; text-align: center; padding: 10px; border: 1px solid #2ecc71; color: #2ecc71; text-decoration: none; border-radius: 6px; font-weight: bold;">Update Menu</a>
            </aside> 
        </div>
    </main>
</div>

</body>
</html>