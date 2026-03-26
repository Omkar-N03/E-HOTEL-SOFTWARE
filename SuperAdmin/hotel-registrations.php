<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['super_admin']);

try {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE status = 'pending' ORDER BY created_at DESC");
    $stmt->execute();
    $pending_hotels = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Requests | Platform Admin</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="logo"><h2>Platform OS</h2></div>
        <ul>
            <li><a href="index.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li><a href="manage-hotels.php"><i class="fa fa-hotel"></i> Manage Hotels</a></li>
            <li class="active">
                <a href="hotel-registrations.php">
                    <i class="fa fa-id-card"></i> Requests 
                    <span class="badge"><?php echo count($pending_hotels); ?></span>
                </a>
            </li>
            <li><a href="../admin/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <header>
            <h1>Pending Registrations</h1>
            <p>Review and verify new restaurant partners before granting access.</p>
        </header>
        <section class="request-list">
            <?php if (empty($pending_hotels)): ?>
                <div class="empty-state">
                    <i class="fa fa-coffee" style="font-size: 3rem; color: #ccc;"></i>
                    <p>No new requests at the moment. You're all caught up!</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($pending_hotels as $hotel): ?>
                    <div class="request-card">
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                            <span class="date"><?php echo date('M d, H:i', strtotime($hotel['created_at'])); ?></span>
                        </div>
                        <div class="card-body">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($hotel['email']); ?></p>
                            <p><strong>System ID:</strong> #<?php echo $hotel['id']; ?></p>
                        </div>
                        <div class="card-footer">
                            <a href="manage-hotels.php?action=approve&id=<?php echo $hotel['id']; ?>" class="btn btn-approve">
                                <i class="fa fa-check"></i> Approve Partner
                            </a>
                            <a href="manage-hotels.php?action=delete&id=<?php echo $hotel['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this application?')">
                                <i class="fa fa-times"></i> Reject
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>
</body>
</html>
