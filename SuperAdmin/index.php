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

    $stmt = $pdo->query("SELECT hotel_name, email, created_at FROM hotels ORDER BY created_at DESC LIMIT 5");
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
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <nav class="sidebar">
            <div class="logo">
                <h2>Platform OS</h2>
            </div>
            <ul>
                <li class="active"><a href="index.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
                <li><a href="manage-hotels.php"><i class="fa fa-hotel"></i> Manage Hotels</a></li>
                <li><a href="hotel-registrations.php">
                    <i class="fa fa-id-card"></i> Requests 
                    <?php if($pending_hotels > 0): ?>
                        <span class="badge badge-red"><?php echo $pending_hotels; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="../admin/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <header>
                <h1>Platform Statistics</h1>
                <p>Overview of all restaurant tenants currently on the system.</p>
            </header>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Hotels</h3>
                    <p class="number"><?php echo $total_hotels; ?></p>
                </div>
                <div class="stat-card warning">
                    <h3>Pending Approvals</h3>
                    <p class="number"><?php echo $pending_hotels; ?></p>
                </div>
                <div class="stat-card success">
                    <h3>Active Entities</h3>
                    <p class="number"><?php echo $active_hotels; ?></p>
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
                            <td><?php echo htmlspecialchars($hotel['hotel_name']); ?></td>
                            <td><?php echo htmlspecialchars($hotel['email']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($hotel['created_at'])); ?></td>
                            <td>
                                <a href="manage-hotels.php" class="btn-small">Review</a>
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
