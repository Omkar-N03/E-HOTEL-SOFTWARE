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
                overflow-x: hidden;
            }

            .main-content {
                margin-left: 280px; 
                flex: 1;
                padding: 2rem;
                transition: margin-left 0.3s ease;
                width: 100%;
            }

            .header-section { 
                display: flex; 
                flex-wrap: wrap; 
                justify-content: space-between; 
                align-items: center; 
                gap: 1rem;
                margin-bottom: 2rem; 
            }

            .welcome-text h1 { font-size: 1.75rem; font-weight: 700; color: var(--dark); }
            
            .dashboard-layout { 
                display: grid; 
                grid-template-columns: 350px 1fr; 
                gap: 1.5rem; 
                align-items: start;
            }

            .content-card { background: #fff; border-radius: var(--radius); box-shadow: var(--card-shadow); padding: 1.5rem; }
            .card-header h2 { font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem; }

            /* --- Form Styling --- */
            .form-group { margin-bottom: 1.2rem; }
            .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--slate-500); margin-bottom: 0.5rem; }
            .form-group input { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
            
            .btn-submit { 
                display: inline-flex; 
                align-items: center; 
                justify-content: center; 
                gap: 8px;
                width: 100%; 
                padding: 0.75rem; 
                background: var(--primary); 
                color: white; 
                border: none; 
                border-radius: 10px; 
                font-weight: 600; 
                cursor: pointer; 
                text-decoration: none;
                transition: 0.3s; 
            }

            .table-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
                gap: 1.25rem; 
            }

            .table-card { 
                background: white; 
                border-radius: var(--radius); 
                padding: 1.5rem; 
                text-align: center; 
                border: 2px solid #f1f5f9; 
                transition: 0.3s; 
            }

            .alert { padding: 1rem; border-radius: var(--radius); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 12px; }

            @media (max-width: 1100px) {
                .dashboard-layout {
                    grid-template-columns: 1fr; 
                }
                .main-content {
                    margin-left: 250px; 
                }
            }

            @media (max-width: 768px) {
                .main-content {
                    margin-left: 0; 
                    padding: 1.25rem;
                }
                
                .header-section {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .header-actions, .header-actions .btn-submit {
                    width: 100%;
                }

                .welcome-text h1 {
                    font-size: 1.5rem;
                }

                .table-grid {
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 1rem;
                }

                .table-card {
                    padding: 1rem;
                }
            }

            @media (max-width: 480px) {
                .table-grid {
                    grid-template-columns: 1fr; 
                }
            }
        </style>
    </head>
    <body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header-section">
            <div class="welcome-text">
                <h1>Tables Management</h1>
                <p>Manage floor plan & QR codes.</p>
            </div>
            <div class="header-actions">
                <a href="generate-qr.php" class="btn-submit">
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
                        <input type="number" id="table_number" name="table_number" placeholder="e.g. 12" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Seating Capacity</label>
                        <input type="number" id="capacity" name="capacity" placeholder="e.g. 4" required>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-plus"></i> Add Table
                    </button>
                </form>
            </section>

            <section>
                <div class="table-grid">
                    <?php if (empty($tables)): ?>
                    <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 3rem; color: var(--slate-500);">
                        <i class="fas fa-chair" style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                        <p>No tables registered.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($tables as $t): ?>
                        <div class="table-card">
                            <div class="table-icon">
                                <i class="fas fa-couch" style="font-size: 1.5rem; color: var(--primary);"></i>
                            </div>
                            <div class="table-info">
                                <h4 style="margin-bottom: 5px;">T-<?php echo htmlspecialchars($t['table_number']); ?></h4>
                                <p style="font-size: 0.85rem; color: var(--slate-500);"><?php echo htmlspecialchars($t['capacity']); ?> Seats</p>
                            </div>
                            <div class="table-actions" style="margin-top: 15px; display: flex; justify-content: center; gap: 8px;">
                                <a href="generate-qr.php?table=<?php echo $t['table_number']; ?>" class="action-btn qr" style="padding: 8px; background: var(--primary-light); color: var(--primary); border-radius: 8px;">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <a href="manage-tables.php?delete=<?php echo $t['id']; ?>" class="action-btn delete" onclick="return confirm('Archive this table?')" style="padding: 8px; background: #fee2e2; color: var(--danger); border-radius: 8px;">
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
    </html> i