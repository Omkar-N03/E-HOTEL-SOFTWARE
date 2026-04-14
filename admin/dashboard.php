<?php
require_once '../config/db.php';
require_once '../config/sessions.php';
protect_page();

$hotel_id = $_SESSION['hotel_id'];
$hotel_name = $_SESSION['hotel_name'];

try {
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as revenue FROM orders WHERE hotel_id = ? AND DATE(order_time) = CURDATE() AND status = 'served'");
    $stmt->execute([$hotel_id]);
    $today_revenue = $stmt->fetch()['revenue'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE hotel_id = ? AND status IN ('Pending', 'Preparing')");
    $stmt->execute([$hotel_id]);
    $active_orders = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM menu_items WHERE hotel_id = ?");
    $stmt->execute([$hotel_id]);
    $total_items = $stmt->fetch()['count'] ?? 0;

    $stmt = $pdo->prepare("SELECT id, table_id, total_amount, status, order_time FROM orders WHERE hotel_id = ? ORDER BY order_time DESC LIMIT 5");
    $stmt->execute([$hotel_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Data Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $hotel_name; ?> | Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/sidebar.css">

<style>
:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --success: #10b981;
    --bg-body: #f8fafc;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --sidebar-width: 280px;
    --radius: 16px;
    --shadow: 0 10px 25px rgba(0,0,0,0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

body {
    background: var(--bg-body);
    display: flex;
    min-height: 100vh;
    overflow-x: hidden;
}

.main-content {
    margin-left: var(--sidebar-width);
    padding: 2rem;
    width: 100%;
    transition: all 0.3s ease;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2.5rem;
    flex-wrap: wrap;
    gap: 20px;
}

.header h1 { font-size: clamp(1.5rem, 5vw, 1.9rem); font-weight: 700; color: var(--text-main); }
.header p { color: var(--text-muted); margin-top: 5px; font-size: 0.95rem; }

.refresh-btn {
    padding: 12px 20px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
    font-weight: 600;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}

.refresh-btn:hover { background: #f1f5f9; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: white;
    padding: 1.6rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1.2rem;
}

.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.revenue { background:#dcfce7; color:#166534; }
.orders { background:#e0e7ff; color:#3730a3; }
.menu { background:#fef3c7; color:#92400e; }

.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media(min-width: 1200px) {
    .dashboard-grid { grid-template-columns: 2fr 1fr; }
}

.card {
    background: white;
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    height: 100%;
}

.table-container {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-top: 1rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

th {
    text-align: left;
    font-size: 0.75rem;
    color: var(--text-muted);
    padding: 12px;
    text-transform: uppercase;
    border-bottom: 1px solid #f1f5f9;
}

td {
    padding: 16px 12px;
    border-bottom: 1px solid #f8fafc;
    font-size: 0.9rem;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}

.served { background:#dcfce7; color:#15803d; }

.health div {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    font-size: 0.9rem;
}

.quick { 
    background: linear-gradient(135deg, #6366f1, #4f46e5); 
    color: white; 
}
.quick h3 { color: white; }
.quick p { opacity: 0.9; font-size: 0.9rem; margin: 10px 0; }
.quick a {
    display: block;
    margin-top: 15px;
    background: white;
    color: var(--primary);
    text-align: center;
    padding: 12px;
    border-radius: 10px;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s;
}
.quick a:hover { transform: translateY(-2px); }

@media(max-width: 1024px) {
    .main-content { margin-left: 70px; padding: 1.5rem; }
}

@media(max-width: 768px) {
    .main-content { 
        margin-left: 0; 
        padding: 1rem;
        padding-top: 80px; 
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }

    .refresh-btn {
        width: 100%;
        justify-content: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 1.2rem;
    }
}
</style>
</head>

<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">

    <div class="header">
        <div>
            <h1>Business Hub</h1>
            <p>Manage your restaurant operations at a glance.</p>
        </div>
        <button onclick="location.reload()" class="refresh-btn">
            <i class="fa fa-sync"></i> Refresh
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon-box revenue"><i class="fa fa-indian-rupee-sign"></i></div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Today's Revenue</p>
                <h2 style="font-size: 1.5rem; margin-top: 4px;">₹<?php echo number_format($today_revenue,2); ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-box orders"><i class="fa fa-bowl-food"></i></div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Active Orders</p>
                <h2 style="font-size: 1.5rem; margin-top: 4px;"><?php echo $active_orders; ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="icon-box menu"><i class="fa fa-utensils"></i></div>
            <div>
                <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">Total Menu Items</p>
                <h2 style="font-size: 1.5rem; margin-top: 4px;"><?php echo $total_items; ?></h2>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
                <h3 style="font-size: 1.1rem;">Recent Order Stream</h3>
                <a href="reports.php" style="color:var(--primary);font-size:0.85rem;text-decoration:none;font-weight:600;">View All</a>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_orders)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 2.5rem; color: var(--text-muted);">No orders found today.</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_orders as $o): ?>
                            <tr>
                                <td style="font-weight: 600;">#<?= $o['id'] ?></td>
                                <td>Table <?= $o['table_id'] ?></td>
                                <td style="font-weight: 600;">₹<?= number_format($o['total_amount'],2) ?></td>
                                <td><span class="badge served"><?= $o['status'] ?></span></td>
                                <td style="color: var(--text-muted);"><?= date('h:i A', strtotime($o['order_time'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <div class="card health">
                <h3 style="margin-bottom:20px; font-size: 1.1rem;">Service Health</h3>
                <div><i class="fa fa-circle" style="color:var(--success); font-size: 10px;"></i> Database Responsive</div>
                <div><i class="fa fa-circle" style="color:var(--success); font-size: 10px;"></i> Kitchen API Online</div>
            </div>

            <div class="card quick">
                <h3 style="font-size: 1.1rem;">Quick Action</h3>
                <p>Ready to update your inventory or categories?</p>
                <a href="manage-menu.php">Go to Menu</a>
            </div>
        </div>
    </div>

</main>

</body>
</html>