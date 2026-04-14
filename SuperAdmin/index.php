<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['super_admin']);

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hotels");
    $total_hotels = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM hotels WHERE status = 'pending'");
    $pending_hotels = $stmt->fetch()['pending'];

    $stmt = $pdo->query("SELECT COUNT(*) as active FROM hotels WHERE status = 'active'");
    $active_hotels = $stmt->fetch()['active'];

    $stmt = $pdo->query("SELECT hotel_name, email, created_at, status FROM hotels ORDER BY created_at DESC LIMIT 5");
    $recent_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super-Admin | Platform Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-soft: #eef2ff;
            --dark: #0f172a;
            --slate: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
            overflow-x: hidden;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: var(--dark);
            color: white;
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .logo h2 {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 2rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo h2::before {
            content: "\f0e8";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: var(--primary);
        }

        .sidebar ul { list-style: none; }

        .sidebar li { margin-bottom: 0.5rem; }

        .sidebar a {
            text-decoration: none;
            color: #94a3b8;
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: var(--radius);
            transition: 0.3s;
            font-weight: 500;
        }

        .sidebar li.active a, .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .sidebar i { width: 20px; font-size: 1.1rem; }

        .badge-red {
            background: var(--danger);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: auto;
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 2.5rem;
        }

        header { margin-bottom: 2rem; }
        header h1 { font-size: 1.8rem; font-weight: 700; color: var(--dark); }
        header p { color: var(--slate); margin-top: 4px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
            transition: transform 0.3s;
        }

        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.success { border-left-color: var(--success); }

        .stat-card h3 { font-size: 0.9rem; color: var(--slate); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .number { font-size: 2rem; font-weight: 700; margin-top: 10px; color: var(--dark); }

        .table-section {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            overflow: hidden;
        }

        .table-section h2 { font-size: 1.1rem; margin-bottom: 1.5rem; color: var(--dark); }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: var(--bg);
            padding: 1rem;
            color: var(--slate);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.95rem;
            color: #475569;
        }

        tr:last-child td { border-bottom: none; }

        .btn-small {
            text-decoration: none;
            background: var(--primary-soft);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-small:hover {
            background: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar { width: 70px; padding: 1rem 0.5rem; }
            .sidebar span, .logo h2, .badge-red { display: none; }
            .main-content { margin-left: 70px; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>Platform OS</h2>
            </div>
            <ul>
                <li class="active">
                    <a href="index.php"><i class="fa-solid fa-house-chimney-window"></i> <span>Dashboard</span></a>
                </li>
                <li>
                    <a href="manage-hotels.php"><i class="fa-solid fa-building-circle-check"></i> <span>Manage Hotels</span></a>
                </li>
                <li>
                    <a href="hotel-registrations.php">
                        <i class="fa-solid fa-envelope-open-text"></i> <span>Requests</span> 
                        <?php if($pending_hotels > 0): ?>
                            <span class="badge badge-red"><?php echo $pending_hotels; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li style="margin-top: auto;">
                    <a href="../admin/logout.php" style="color: var(--danger);">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Platform Statistics</h1>
                <p>Welcome back, Super Admin. Here is the system pulse.</p>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Hotels</h3>
                    <p class="number"><?php echo number_format($total_hotels); ?></p>
                </div>
                <div class="stat-card warning">
                    <h3>Pending Approvals</h3>
                    <p class="number"><?php echo number_format($pending_hotels); ?></p>
                </div>
                <div class="stat-card success">
                    <h3>Active Entities</h3>
                    <p class="number"><?php echo number_format($active_hotels); ?></p>
                </div>
            </div>

            <section class="table-section">
                <h2>Recent Hotel Registrations</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Hotel Name</th>
                            <th>Email Address</th>
                            <th>Date Joined</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_requests as $hotel): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--dark);">
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($hotel['email']); ?></td>
                            <td>
                                <i class="fa-regular fa-calendar" style="margin-right: 5px; opacity: 0.5;"></i>
                                <?php echo date('M d, Y', strtotime($hotel['created_at'])); ?>
                            </td>
                            <td>
                                <a href="manage-hotels.php" class="btn-small">Review</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recent_requests)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 2rem;">No recent registrations found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>