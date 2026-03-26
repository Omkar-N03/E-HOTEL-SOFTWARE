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
    $salesStmt = $pdo->prepare("
        SELECT 
            DATE(order_time) as date, 
            SUM(total_price) as daily_total,
            COUNT(id) as order_count
        FROM orders 
        WHERE hotel_id = ? AND status = 'served'
        AND order_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(order_time)
        ORDER BY DATE(order_time) ASC
    ");
    $salesStmt->execute([$hotel_id]);
    $salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
    $labels = [];
    $totals = [];
    $totalEarnings = 0;
    $totalOrders = 0;

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
    <title>Sales Reports | <?php echo htmlspecialchars($hotel['hotel_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary: #2ecc71; --dark: #2c3e50; --light: #f4f7f6; --accent: #3498db; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        
        /* Sidebar */
        .sidebar { width: 250px; background: var(--dark); height: 100vh; color: white; padding: 20px; position: fixed; }
        .sidebar ul { list-style: none; padding: 0; margin-top: 30px; }
        .sidebar li { margin-bottom: 15px; }
        .sidebar a { color: #ecf0f1; text-decoration: none; display: flex; align-items: center; padding: 12px; border-radius: 5px; transition: 0.3s; }
        .sidebar a i { margin-right: 10px; width: 20px; }
        .sidebar a:hover, .sidebar .active { background: var(--primary); }
        .logo-circle img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--primary); margin-bottom: 10px; }

        /* Main Content */
        .main-content { margin-left: 270px; padding: 40px; width: calc(100% - 270px); box-sizing: border-box; }
        
        /* Stats Cards */
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; }
        .stat-card h2 { margin: 0; color: var(--dark); font-size: 1.8rem; }
        .stat-card p { margin: 5px 0 0; color: #7f8c8d; font-weight: 600; }
        .stat-card i { font-size: 2rem; color: var(--accent); margin-bottom: 10px; }

        /* Graph Card */
        .graph-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { color: var(--dark); margin-bottom: 30px; }
    </style>
</head>
<body>

<nav class="sidebar">
    <div class="hotel-profile" style="text-align:center;">
        <div class="logo-circle">
            <img src="../assets/img/logos/<?php echo $hotel['logo_url'] ?: 'default-logo.png'; ?>" alt="Logo">
        </div>
        <h3><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
        <li class="active"><a href="reports.php"><i class="fa fa-chart-line"></i> Sales Reports</a></li>
        <li><a href="manage-menu.php"><i class="fa fa-utensils"></i> Manage Menu</a></li>
        <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
        <li><a href="../logout.php" style="color:#ff7675;"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<main class="main-content">
    <h1>Sales Overview (Last 7 Days)</h1>

    <div class="stats-container">
        <div class="stat-card">
            <i class="fa fa-wallet"></i>
            <h2><?php echo $hotel['currency'] . number_format($totalEarnings, 2); ?></h2>
            <p>Total Revenue</p>
        </div>
        <div class="stat-card">
            <i class="fa fa-shopping-bag"></i>
            <h2><?php echo $totalOrders; ?></h2>
            <p>Total Orders</p>
        </div>
        <div class="stat-card">
            <i class="fa fa-calendar-day"></i>
            <h2><?php echo count($salesData); ?></h2>
            <p>Days Active</p>
        </div>
    </div>

    <div class="graph-card">
        <canvas id="salesChart" height="100"></canvas>
    </div>
</main>

<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Revenue (<?php echo $hotel['currency']; ?>)',
                data: <?php echo json_encode($totals); ?>,
                borderColor: '#2ecc71',
                backgroundColor: 'rgba(46, 204, 113, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#2ecc71'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>

</body>
</html>