<?php
require_once '../config/db.php';
require_once '../config/sessions.php';
protect_page();

$hotel_id = $_SESSION['hotel_id'];
$hotel_name = $_SESSION['hotel_name'];

// Data Fetching
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
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #10b981;
            --bg-body: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --sidebar-width: 280px;
            --sidebar-mini: 85px;
            --radius: 16px;
            --shadow: 0 10px 15px -3px rgba(0,0,0,0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); display: flex; min-height: 100vh; }

        /* --- SIDEBAR REDESIGN --- */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            height: 100vh;
            position: fixed;
            padding: 2rem 1.25rem;
            display: flex;
            flex-direction: column;
            color: white;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 3rem;
            padding: 0 0.5rem;
        }

        .brand-icon {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 10px;
            border-radius: 12px;
            font-size: 1.2rem;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .brand-name {
            font-weight: 700;
            font-size: 1.2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-list { list-style: none; flex: 1; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 0.85rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            animation: slideIn 0.4s ease forwards;
            opacity: 0;
        }

        /* Staggered Entrance Animation */
        @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
        .nav-item:nth-child(1) .nav-link { animation-delay: 0.1s; }
        .nav-item:nth-child(2) .nav-link { animation-delay: 0.2s; }
        .nav-item:nth-child(3) .nav-link { animation-delay: 0.3s; }
        .nav-item:nth-child(4) .nav-link { animation-delay: 0.4s; }
        .nav-item:nth-child(5) .nav-link { animation-delay: 0.5s; }

        .nav-link:hover, .nav-item.active .nav-link {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active .nav-link { background: rgba(255, 255, 255, 0.25); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }

        .nav-link i { font-size: 1.2rem; width: 24px; text-align: center; }

        .logout-section { padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .logout-link:hover { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 2.5rem; transition: all 0.4s; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-5px); }

        .icon-circle {
            width: 54px; height: 54px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
        }

        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        .card { background: white; border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow); }

        .modern-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .modern-table th { text-align: left; padding: 12px; color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        .modern-table td { padding: 16px 12px; border-bottom: 1px solid #f8fafc; font-size: 0.9rem; }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-served { background: #dcfce7; color: #15803d; }
        .status-preparing { background: #fef3c7; color: #92400e; }
        .status-pending { background: #fee2e2; color: #b91c1c; }

        /* --- RESPONSIVENESS --- */
        @media (max-width: 1024px) {
            .sidebar { width: var(--sidebar-mini); padding: 2rem 0.75rem; }
            .brand-name, .nav-link span, .logout-section span { display: none; }
            .brand { justify-content: center; }
            .main-content { margin-left: var(--sidebar-mini); }
            .nav-link { justify-content: center; padding: 1rem; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar {
                width: 100%; height: 70px; flex-direction: row; 
                position: fixed; bottom: 0; top: auto; padding: 0 1rem;
                justify-content: space-between; align-items: center;
            }
            .brand, .logout-section { display: none; }
            .nav-list { display: flex; width: 100%; justify-content: space-around; margin: 0; }
            .nav-item { margin-bottom: 0; }
            .main-content { margin-left: 0; margin-bottom: 80px; padding: 1.5rem; }
            .dashboard-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="fa fa-utensils"></i></div>
            <span class="brand-name"><?php echo htmlspecialchars($hotel_name); ?></span>
        </div>

        <ul class="nav-list">
            <li class="nav-item active">
                <a href="dashboard.php" class="nav-link">
                    <i class="fa-solid fa-chart-pie"></i> <span>Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-menu.php" class="nav-link">
                    <i class="fa-solid fa-hamburger"></i> <span>Menu Items</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-tables.php" class="nav-link">
                    <i class="fa-solid fa-qrcode"></i> <span>QR Tables</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../kitchen/live-orders.php" target="_blank" class="nav-link">
                    <i class="fa-solid fa-fire"></i> <span>Live Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar"></i> <span>Revenue</span>
                </a>
            </li>
        </ul>

        <div class="logout-section">
            <a href="logout.php" class="nav-link logout-link">
                <i class="fa-solid fa-power-off"></i> <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text-main);">Business Hub</h1>
                <p style="color: var(--text-muted);">Manage your restaurant operations at a glance.</p>
            </div>
            <button onclick="location.reload()" style="padding: 10px 20px; border-radius: 10px; border: 1px solid #e2e8f0; background: white; cursor: pointer; font-weight: 600;">
                <i class="fa fa-sync"></i> Refresh
            </button>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon-circle" style="background: #dcfce7; color: #166534;"><i class="fa fa-indian-rupee-sign"></i></div>
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Today's Revenue</p>
                    <h2 style="font-size: 1.5rem;">₹<?php echo number_format($today_revenue, 2); ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #e0e7ff; color: #3730a3;"><i class="fa fa-bowl-food"></i></div>
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Active Orders</p>
                    <h2 style="font-size: 1.5rem;"><?php echo $active_orders; ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon-circle" style="background: #fef3c7; color: #92400e;"><i class="fa fa-utensils"></i></div>
                <div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; font-weight: 500;">Total Menu Items</p>
                    <h2 style="font-size: 1.5rem;"><?php echo $total_items; ?></h2>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <section class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="font-weight: 700;">Recent Order Stream</h3>
                    <a href="reports.php" style="color: var(--primary); font-size: 0.85rem; font-weight: 600; text-decoration: none;">View All</a>
                </div>
                <table class="modern-table">
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
                        <?php if (empty($recent_orders)): ?>
                            <tr><td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-muted);">Awaiting orders...</td></tr>
                        <?php else: ?>
                            <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td style="font-weight: 600;">#<?php echo $order['id']; ?></td>
                                <td>Table <?php echo $order['table_id']; ?></td>
                                <td style="font-weight: 600;">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td style="color: var(--text-muted);"><?php echo date('h:i A', strtotime($order['order_time'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="card">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">Service Health</h3>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 0.9rem;">
                        <i class="fa fa-circle" style="color: var(--success); font-size: 0.6rem;"></i> Database Responsive
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem;">
                        <i class="fa fa-circle" style="color: var(--success); font-size: 0.6rem;"></i> Kitchen API Online
                    </div>
                </div>
                <div class="card" style="background: var(--primary); color: white;">
                    <h3 style="margin-bottom: 10px;">Quick Action</h3>
                    <p style="font-size: 0.85rem; margin-bottom: 1.5rem; opacity: 0.9;">Ready to update your inventory or categories?</p>
                    <a href="manage-menu.php" style="display: block; width: 100%; padding: 12px; background: white; color: var(--primary); text-align: center; border-radius: 10px; font-weight: 700; text-decoration: none;">Go to Menu</a>
                </div>
            </div>
        </div>
    </main>

</body>
</html>