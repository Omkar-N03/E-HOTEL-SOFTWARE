<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['super_admin']);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $hotel_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $stmt = $pdo->prepare("UPDATE hotels SET status = 'active' WHERE id = ?");
        $stmt->execute([$hotel_id]);
    } elseif ($action == 'suspend') {
        $stmt = $pdo->prepare("UPDATE hotels SET status = 'suspended' WHERE id = ?");
        $stmt->execute([$hotel_id]);
    } elseif ($action == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
        $stmt->execute([$hotel_id]);
    }
    header("Location: manage-hotels.php?msg=success");
    exit();
}

$stmt = $pdo->query("SELECT * FROM hotels ORDER BY created_at DESC");
$hotels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Hotels | Platform Owner</title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="logo"><h2>Platform OS</h2></div>
        <ul>
            <li><a href="index.php"><i class="fa fa-chart-line"></i> Dashboard</a></li>
            <li class="active"><a href="manage-hotels.php"><i class="fa fa-hotel"></i> Manage Hotels</a></li>
            <li><a href="hotel-registrations.php"><i class="fa fa-id-card"></i> Requests</a></li>
            <li><a href="../admin/logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <header>
            <h1>Hotel Management</h1>
            <p>Approve new partners or manage existing restaurant access.</p>
        </header>
        <section class="table-section">
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert success">Operation completed successfully.</div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hotel Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hotels as $hotel): ?>
                    <tr>
                        <td>#<?php echo $hotel['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($hotel['hotel_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($hotel['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $hotel['status']; ?>">
                                <?php echo ucfirst($hotel['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($hotel['created_at'])); ?></td>
                        <td class="action-buttons">
                            <?php if($hotel['status'] !== 'active'): ?>
                                <a href="manage-hotels.php?action=approve&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-approve" title="Approve"><i class="fa fa-check"></i></a>
                            <?php endif; ?>
                            <?php if($hotel['status'] === 'active'): ?>
                                <a href="manage-hotels.php?action=suspend&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-suspend" title="Suspend"><i class="fa fa-ban"></i></a>
                            <?php endif; ?>
                            <a href="manage-hotels.php?action=delete&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Warning: Deleting this hotel will remove all their menu items and order history. Continue?')" title="Delete Permanent"><i class="fa fa-trash"></i></a>
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
