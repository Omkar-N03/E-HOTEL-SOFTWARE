<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_table'])) {
    $table_no = (int)$_POST['table_number'];
    $capacity = (int)$_POST['capacity'];
    try {
        $check = $pdo->prepare("SELECT id FROM restaurant_tables WHERE hotel_id = ? AND table_number = ?");
        $check->execute([$hotel_id, $table_no]);
        if ($check->rowCount() > 0) {
            $message = "Table number already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO restaurant_tables (hotel_id, table_number, capacity) VALUES (?, ?, ?)");
            $stmt->execute([$hotel_id, $table_no, $capacity]);
            $message = "Table $table_no added successfully.";
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM restaurant_tables WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);
    header("Location: manage-tables.php?msg=deleted");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM restaurant_tables WHERE hotel_id = ? ORDER BY table_number ASC");
$stmt->execute([$hotel_id]);
$tables = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Tables | <?php echo $_SESSION['hotel_name']; ?></title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="hotel-profile"><h3>Admin Panel</h3></div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li><a href="manage-menu.php"><i class="fa fa-utensils"></i> Manage Menu</a></li>
            <li class="active"><a href="manage-tables.php"><i class="fa fa-border-all"></i> Tables & QR</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <header class="section-header">
            <h1>Dining Tables & QR Codes</h1>
            <div class="header-actions">
                <a href="generate-qr.php" class="btn btn-secondary"><i class="fa fa-qrcode"></i> Print All QRs</a>
            </div>
        </header>
        <div class="dashboard-flex">
            <section class="form-section card">
                <h3>Add New Table</h3>
                <form method="POST">
                    <input type="hidden" name="add_table" value="1">
                    <div class="form-group">
                        <label>Table Number</label>
                        <input type="number" name="table_number" placeholder="e.g. 5" required>
                    </div>
                    <div class="form-group">
                        <label>Seating Capacity</label>
                        <input type="number" name="capacity" placeholder="e.g. 4" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register Table</button>
                </form>
            </section>
            <section class="table-list-section card">
                <h3>Active Tables</h3>
                <?php if($message): ?>
                    <div class="alert info"><?php echo $message; ?></div>
                <?php endif; ?>
                <div class="table-grid">
                    <?php foreach($tables as $t): ?>
                    <div class="table-card">
                        <div class="table-icon"><i class="fa fa-chair"></i></div>
                        <div class="table-info">
                            <h4>Table <?php echo $t['table_number']; ?></h4>
                            <p><?php echo $t['capacity']; ?> Seats</p>
                        </div>
                        <div class="table-actions">
                            <a href="generate-qr.php?table=<?php echo $t['table_number']; ?>" title="View QR"><i class="fa fa-qrcode"></i></a>
                            <a href="manage-tables.php?delete=<?php echo $t['id']; ?>" class="text-danger" onclick="return confirm('Delete table?')"><i class="fa fa-trash"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
