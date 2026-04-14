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
<title><?php echo htmlspecialchars($hotel_name); ?> | Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/css/sidebar.css">

<style>:root {
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
}

.main-content {
    margin-left: var(--sidebar-width);
    padding: 2.5rem;
    width: 100%;
}


.header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2.5rem;
}

.header h1 {
    font-size: 1.9rem;
    font-weight: 700;
}

.header p {
    color: var(--text-muted);
    margin-top: 5px;
}

.refresh-btn {
    padding: 10px 18px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: white;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
}

.refresh-btn:hover {
    background: #f1f5f9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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
    gap: 1rem;
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
}

.icon-box {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.stat-card p {
    font-size: 0.85rem;
    color: var(--text-muted);
    font-weight: 500;
}

.stat-card h2 {
    font-size: 1.5rem;
    margin-top: 4px;
}

.revenue { background:#dcfce7; color:#166534; }
.orders { background:#e0e7ff; color:#3730a3; }
.menu { background:#fef3c7; color:#92400e; }


.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
}


.card {
    background: white;
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
}


table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
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

tr:hover {
    background: #f9fafb;
}


.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.served { background:#dcfce7; color:#15803d; }

.health div {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 0.9rem;
}

.quick {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: white;
}

.quick h3 {
    margin-bottom: 10px;
}

.quick p {
    font-size: 0.85rem;
    opacity: 0.9;
}

.quick a {
    display: block;
    margin-top: 18px;
    background: white;
    color: var(--primary);
    text-align: center;
    padding: 12px;
    border-radius: 10px;
    font-weight: 700;
    text-decoration: none;
    transition: 0.3s;
}

.quick a:hover {
    background: #f1f5f9;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(25px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideRight {
    from {
        opacity: 0;
        transform: translateX(-25px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media(max-width:1024px){
    .main-content { margin-left: 80px; }
}
@media(max-width:768px){
    .main-content { margin-left:0; }
    .dashboard-grid { grid-template-columns:1fr; }
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
        <div class="icon-box revenue">
            <i class="fa fa-indian-rupee-sign"></i>
        </div>
        <div>
            <p>Today's Revenue</p>
            <h2>₹<?php echo number_format($today_revenue,2); ?></h2>
        </div>
    </div>

    <div class="stat-card">
        <div class="icon-box orders">
            <i class="fa fa-bowl-food"></i>
        </div>
        <div>
            <p>Active Orders</p>
            <h2><?php echo $active_orders; ?></h2>
        </div>
    </div>

    <div class="stat-card">
        <div class="icon-box menu">
            <i class="fa fa-utensils"></i>
        </div>
        <div>
            <p>Total Menu Items</p>
            <h2><?php echo $total_items; ?></h2>
        </div>
    </div>

</div>


<div class="dashboard-grid">

    <div class="card">
        <div style="display:flex;justify-content:space-between;">
            <h3>Recent Order Stream</h3>
            <a href="reports.php" style="color:var(--primary);font-size:0.85rem;">View All</a>
        </div>

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
                <?php foreach($recent_orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td>Table <?= $o['table_id'] ?></td>
                    <td>₹<?= number_format($o['total_amount'],2) ?></td>
                    <td><span class="badge served"><?= $o['status'] ?></span></td>
                    <td><?= date('h:i A', strtotime($o['order_time'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">

        <div class="card health">
            <h3>Service Health</h3>
            <div>🟢 Database Responsive</div>
            <div>🟢 Kitchen API Online</div>
        </div>

        <div class="card quick">
            <h3>Quick Action</h3>
            <p>Ready to update your inventory or categories?</p>
            <a href="manage-menu.php">Go to Menu</a>
        </div>

    </div>

</div>

</main>

</body>
</html>