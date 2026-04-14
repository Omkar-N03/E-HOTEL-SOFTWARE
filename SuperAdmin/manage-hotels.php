<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['super_admin']);

$message = "";
$messageType = "";

// Handle Actions (Approve, Suspend, Delete)
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

// Handle New Hotel Creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_hotel'])) {
    $name = strip_tags($_POST['hotel_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $check = $pdo->prepare("SELECT id FROM hotels WHERE email = ?");
        $check->execute([$email]);
        if($check->rowCount() > 0) {
            $message = "Error: This email is already registered.";
            $messageType = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO hotels (hotel_name, email, password, status) VALUES (?, ?, ?, 'active')");
            if($stmt->execute([$name, $email, $password])) {
                $message = "New hotel account created successfully!";
                $messageType = "success";
            }
        }
    } catch (PDOException $e) {
        $message = "DB Error: " . $e->getMessage();
        $messageType = "error";
    }
}

$stmt = $pdo->query("SELECT * FROM hotels ORDER BY created_at DESC");
$hotels = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels | Platform Command Center</title>
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
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg); color: var(--dark); }

        .admin-container { display: flex; min-height: 100vh; }

        /* Sidebar - Consistent with Dashboard */
        .sidebar { width: 260px; background: var(--dark); color: white; padding: 1.5rem; position: fixed; height: 100vh; z-index: 100; }
        .logo h2 { font-size: 1.25rem; font-weight: 800; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar a { text-decoration: none; color: #94a3b8; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 12px; border-radius: var(--radius); transition: 0.3s; font-weight: 500; }
        .sidebar li.active a, .sidebar a:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .main-content { margin-left: 260px; flex: 1; padding: 2.5rem; }

        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        header h1 { font-size: 1.8rem; font-weight: 700; }

        /* Buttons */
        .btn-add { background: var(--primary); color: white; border: none; padding: 12px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.3s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4); }

        /* Table Area */
        .table-section { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th { background: var(--bg); padding: 1rem; color: var(--slate); font-size: 0.8rem; text-transform: uppercase; text-align: left; }
        td { padding: 1.2rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; }

        /* Badges */
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-badge.active { background: #dcfce7; color: #166534; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.suspended { background: #fee2e2; color: #991b1b; }

        /* Action Icons */
        .action-buttons { display: flex; gap: 8px; }
        .btn-icon { width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 8px; text-decoration: none; transition: 0.3s; }
        .btn-approve { background: #dcfce7; color: var(--success); }
        .btn-suspend { background: #fef3c7; color: var(--warning); }
        .btn-delete { background: #fee2e2; color: var(--danger); }
        .btn-icon:hover { transform: scale(1.1); }

        /* Modal Styling */
        .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 2rem; border-radius: 16px; width: 100%; max-width: 450px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; color: var(--slate); }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit; }
        
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 500; }
        .alert.success { background: #dcfce7; color: #166534; border: 1px solid var(--success); }
        .alert.error { background: #fee2e2; color: #b91c1c; border: 1px solid var(--danger); }
    </style>
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="logo"><h2>Platform OS</h2></div>
        <ul>
            <li><a href="index.php"><i class="fa-solid fa-house-chimney-window"></i> <span>Dashboard</span></a></li>
            <li class="active"><a href="manage-hotels.php"><i class="fa-solid fa-building-circle-check"></i> <span>Manage Hotels</span></a></li>
            <li><a href="hotel-registrations.php"><i class="fa-solid fa-envelope-open-text"></i> <span>Requests</span></a></li>
            <li style="margin-top: auto;"><a href="../admin/logout.php" style="color: var(--danger);"><i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <div>
                <h1>Hotel Management</h1>
                <p>Manage restaurant partners and credentials</p>
            </div>
            <button class="btn-add" onclick="toggleModal(true)">
                <i class="fa fa-plus"></i> Create New Hotel
            </button>
        </header>

        <?php if($message || isset($_GET['msg'])): ?>
            <div class="alert <?php echo $messageType ?: 'success'; ?>">
                <i class="fa fa-circle-info"></i> <?php echo $message ?: "Operation completed successfully."; ?>
            </div>
        <?php endif; ?>

        <section class="table-section">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Restaurant</th>
                        <th>Email Address</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($hotels as $hotel): ?>
                    <tr>
                        <td style="color: var(--slate); font-weight: 600;">#<?php echo $hotel['id']; ?></td>
                        <td><strong style="color: var(--dark);"><?php echo htmlspecialchars($hotel['hotel_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($hotel['email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $hotel['status']; ?>">
                                <?php echo ucfirst($hotel['status']); ?>
                            </span>
                        </td>
                        <td><span style="color: var(--slate); font-size: 0.85rem;"><?php echo date('d M Y', strtotime($hotel['created_at'])); ?></span></td>
                        <td class="action-buttons">
                            <?php if($hotel['status'] !== 'active'): ?>
                                <a href="manage-hotels.php?action=approve&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-approve" title="Activate"><i class="fa fa-check"></i></a>
                            <?php endif; ?>
                            <?php if($hotel['status'] === 'active'): ?>
                                <a href="manage-hotels.php?action=suspend&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-suspend" title="Suspend"><i class="fa fa-ban"></i></a>
                            <?php endif; ?>
                            <a href="manage-hotels.php?action=delete&id=<?php echo $hotel['id']; ?>" class="btn-icon btn-delete" onclick="return confirm('Permanent Delete: This will erase all hotel data. Continue?')" title="Delete"><i class="fa fa-trash-can"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<div class="modal" id="addHotelModal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.25rem;">Create Hotel Account</h2>
            <i class="fa fa-times" style="cursor: pointer; color: var(--slate);" onclick="toggleModal(false)"></i>
        </div>
        <form method="POST">
            <input type="hidden" name="create_hotel" value="1">
            <div class="form-group">
                <label>Restaurant Name</label>
                <input type="text" name="hotel_name" placeholder="e.g. Grand Rio Restaurant" required>
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="email" placeholder="admin@restaurant.com" required>
            </div>
            <div class="form-group">
                <label>Initial Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-add" style="width: 100%; justify-content: center; margin-top: 1rem;">
                Generate Credentials
            </button>
        </form>
    </div>
</div>

<script>
    function toggleModal(show) {
        document.getElementById('addHotelModal').style.display = show ? 'flex' : 'none';
    }
    
    // Close modal on outside click
    window.onclick = function(event) {
        let modal = document.getElementById('addHotelModal');
        if (event.target == modal) toggleModal(false);
    }
</script>
</body>
</html>