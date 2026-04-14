<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";
$message_type = "info";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_table'])) {
    $table_no = (int)$_POST['table_number'];
    $capacity = (int)$_POST['capacity'];
    try {
        $check = $pdo->prepare("SELECT id FROM restaurant_tables WHERE hotel_id = ? AND table_number = ?");
        $check->execute([$hotel_id, $table_no]);
        if ($check->rowCount() > 0) {
            $message = "Table number already exists!";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO restaurant_tables (hotel_id, table_number, capacity) VALUES (?, ?, ?)");
            $stmt->execute([$hotel_id, $table_no, $capacity]);
            $message = "Table $table_no successfully registered.";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "System Error: " . $e->getMessage();
        $message_type = "error";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tables | <?php echo htmlspecialchars($_SESSION['hotel_name'] ?? 'Restaurant'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css"> 
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #eef2ff;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #0f172a;
            --slate-500: #64748b;
            --bg-main: #f8fafc;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
            --radius: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-main);
            color: var(--dark);
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 280px; /* Adjust this to match your sidebar width */
            flex: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        /* --- Rest of your existing CSS for Main Content stays here --- */
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .welcome-text h1 { font-size: 2rem; font-weight: 700; color: var(--dark); margin-bottom: 4px; }
        .welcome-text p { color: var(--slate-500); }
        .alert { padding: 1.2rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .alert.success { background: #dcfce7; color: #166534; border-left: 4px solid var(--success); }
        .alert.error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--danger); }
        .dashboard-layout { display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; }
        .content-card { background: #fff; border-radius: var(--radius); box-shadow: var(--card-shadow); padding: 1.5rem; }
        .card-header h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--slate-500); margin-bottom: 0.5rem; }
        .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; transition: 0.2s; }
        .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .btn-submit { display: block; width: 100%; padding: 0.75rem; background: var(--primary); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }
        .table-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; }
        .table-card { background: white; border-radius: var(--radius); padding: 1.5rem; text-align: center; border: 2px solid #f1f5f9; transition: 0.3s; }
        .table-card:hover { transform: translateY(-8px); border-color: var(--primary); box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.2); }
        .table-icon { width: 70px; height: 70px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 12px; margin: 0 auto 1rem; font-size: 2rem; }
        .table-actions { margin-top: 1rem; display: flex; justify-content: center; gap: 1rem; border-top: 1px solid #f1f5f9; padding-top: 1rem; }
        .action-btn { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; }
        .action-btn.qr { background: var(--primary-light); color: var(--primary); }
        .action-btn.delete { background: #fee2e2; color: var(--danger); }
        
        @media (max-width: 1024px) {
            .main-content { margin-left: 80px; }
            .dashboard-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <header class="header-section">
        <div class="welcome-text">
            <h1>Tables Management</h1>
            <p>Manage your floor plan and generate QR codes.</p>
        </div>
        <div class="header-actions">
            <a href="generate-qr.php" class="btn-submit" style="width: auto; padding: 0.6rem 1.2rem; text-decoration: none;">
                <i class="fas fa-print"></i> Batch Print QR
            </a>
        </div>
    </header>

    <?php if ($message): ?>
    <div class="alert <?php echo htmlspecialchars($message_type); ?>">
        <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-layout">
        <section class="content-card">
            <div class="card-header">
                <h2>Add New Table</h2>
            </div>
            <form method="POST">
                <input type="hidden" name="add_table" value="1">
                <div class="form-group">
                    <label for="table_number">Table Identity</label>
                    <input type="number" id="table_number" name="table_number" placeholder="Table No (e.g. 12)" required>
                </div>
                <div class="form-group">
                    <label for="capacity">Seating Capacity</label>
                    <input type="number" id="capacity" name="capacity" placeholder="Pax (e.g. 4)" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus"></i> Add to Floor Plan
                </button>
            </form>
        </section>

        <section>
            <div class="table-grid">
                <?php if (empty($tables)): ?>
                <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--slate-500);">
                    <i class="fas fa-chair" style="font-size: 4rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                    <p>No tables registered yet.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($tables as $t): ?>
                    <div class="table-card">
                        <div class="table-icon">
                            <i class="fas fa-couch"></i>
                        </div>
                        <div class="table-info">
                            <h4>Table <?php echo htmlspecialchars($t['table_number']); ?></h4>
                            <p><?php echo htmlspecialchars($t['capacity']); ?> Guests Capacity</p>
                        </div>
                        <div class="table-actions">
                            <a href="generate-qr.php?table=<?php echo $t['table_number']; ?>" class="action-btn qr" title="Download QR">
                                <i class="fas fa-qrcode"></i>
                            </a>
                            <a href="manage-tables.php?delete=<?php echo $t['id']; ?>" class="action-btn delete" onclick="return confirm('Archive this table?')" title="Remove Table">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

</body>
</html>