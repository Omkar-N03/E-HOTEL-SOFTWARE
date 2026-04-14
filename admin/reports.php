<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['hotel_id'])) {
    header("Location: ../index.php");
    exit();
}

$hotel_id = $_SESSION['hotel_id'];

try {
    $hotelStmt = $pdo->prepare("SELECT hotel_name, logo_url, currency FROM hotels WHERE id = ?");
    $hotelStmt->execute([$hotel_id]);
    $hotel = $hotelStmt->fetch();
    
    if (!$hotel) { die("Hotel not found"); }
    
    $salesStmt = $pdo->prepare("
        SELECT 
            DATE(o.order_time) as date, 
            SUM(oi.price * oi.quantity) as daily_total, 
            COUNT(DISTINCT o.id) as order_count 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.hotel_id = ? AND o.status = 'served' 
        AND o.order_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(o.order_time) 
        ORDER BY DATE(o.order_time) ASC
    ");
    $salesStmt->execute([$hotel_id]);
    $salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $labels = []; $totals = []; $totalEarnings = 0; $totalOrders = 0;
    foreach ($salesData as $row) {
        $labels[] = date('M d', strtotime($row['date']));
        $totals[] = (float)$row['daily_total'];
        $totalEarnings += $row['daily_total'];
        $totalOrders += $row['order_count'];
    }
} catch (PDOException $e) {
    die("Error fetching report: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports | <?php echo htmlspecialchars($hotel['hotel_name']); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #6366f1;
            --bg-main: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --success: #10b981;
            --border: #e2e8f0;
            --radius: 16px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
            --sidebar-width: 280px; /* Matching your reference */
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background-color: var(--bg-main); 
            display: flex; 
            min-height: 100vh; 
        }

        .main-content { 
            margin-left: var(--sidebar-width); 
            flex: 1; 
            padding: 2.5rem;
            min-width: 0; 
            animation: fadeIn 0.8s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: 0.3s;
        }

        .stat-card:hover { transform: translateY(-4px); }

        .stat-icon { 
            width: 48px; height: 48px; border-radius: 12px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.25rem; margin-bottom: 1rem;
        }
        .revenue { background: #dcfce7; color: #166534; }
        .orders { background: #e0e7ff; color: #3730a3; }
        .days { background: #fef3c7; color: #92400e; }

        .stat-value { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .stat-label { color: var(--text-muted); font-size: 0.9rem; }

        .chart-card {
            background: var(--surface);
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        @media (max-width: 1024px) {
            :root { --sidebar-width: 80px; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1 style="font-size: 1.8rem; font-weight: 700;">Sales Analytics</h1>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Overview for <?php echo htmlspecialchars($hotel['hotel_name']); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon revenue"><i class="fas fa-indian-rupee-sign"></i></div>
                <div class="stat-value">₹<?php echo number_format($totalEarnings, 2); ?></div>
                <div class="stat-label">7-Day Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orders"><i class="fas fa-utensils"></i></div>
                <div class="stat-value"><?php echo $totalOrders; ?></div>
                <div class="stat-label">Orders Served</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon days"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-value"><?php echo count($salesData); ?></div>
                <div class="stat-label">Active Days</div>
            </div>
        </div>

        <div class="chart-card">
            <h3 style="margin-bottom: 1.5rem;"><i class="fas fa-chart-line" style="color: var(--primary);"></i> Revenue Trend</h3>
            <canvas id="salesChart" height="100"></canvas>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode($totals); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>