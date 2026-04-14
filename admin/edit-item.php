<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = "";

try {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$item_id, $hotel_id]);
    $item = $stmt->fetch();

    if (!$item) {
        header("Location: manage-menu.php?error=not_found");
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_item'])) {
    $name     = strip_tags($_POST['item_name']);
    $price    = $_POST['price'];
    $calories = (int)$_POST['calories'];
    $protein  = (int)$_POST['protein'];
    $category = $_POST['category'];
    $status   = isset($_POST['is_available']) ? 1 : 0;
    $image_url = $item['image_url'];

    if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] == 0) {
        $upload_dir = "../assets/images/menu/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES["dish_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES["dish_image"]["tmp_name"], $target_file)) {
            if ($image_url && file_exists("../" . $image_url)) {
                unlink("../" . $image_url);
            }
            $image_url = "assets/images/menu/" . $new_filename;
        }
    }

    try {
        $updateStmt = $pdo->prepare("UPDATE menu_items SET name = ?, price = ?, calories = ?, protein = ?, category = ?, is_available = ?, image_url = ? WHERE id = ? AND hotel_id = ?");
        $updateStmt->execute([$name, $price, $calories, $protein, $category, $status, $image_url, $item_id, $hotel_id]);
        header("Location: manage-menu.php?msg=updated");
        exit();
    } catch (PDOException $e) {
        $message = "Update failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item | <?php echo htmlspecialchars($item['name']); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-main: #f8fafc;
            --sidebar-grad: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --danger: #ef4444;
            --border: #e2e8f0;
            --radius: 16px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg-main); color: var(--text-main); display: flex; min-height: 100vh; }

        .sidebar {
            width: 280px;
            background: var(--sidebar-grad);
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }

        .brand-box { display: flex; align-items: center; gap: 12px; margin-bottom: 3rem; color: white; }
        .brand-icon { background: rgba(255,255,255,0.2); padding: 10px; border-radius: 12px; font-size: 24px; }
        .brand-logo { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; }

        .nav-list { list-style: none; }
        .nav-link {
            display: flex; align-items: center; gap: 12px; padding: 0.9rem 1rem;
            text-decoration: none; color: rgba(255, 255, 255, 0.8);
            font-weight: 500; border-radius: 12px; transition: 0.3s;
        }

        .nav-item.active .nav-link {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left: 4px solid white;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }

        .main-content { margin-left: 280px; flex: 1; padding: 2.5rem; }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--surface);
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header-flex h1 { font-size: 1.5rem; font-weight: 700; }

        .grid-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }

        input[type="text"], input[type="number"], select {
            width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 10px;
            font-size: 1rem; transition: 0.3s;
        }

        input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .image-preview-wrapper {
            display: flex; align-items: center; gap: 20px;
            padding: 15px; background: var(--bg-main); border-radius: 12px; margin-bottom: 10px;
        }

        .current-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 2px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }

        .btn-submit {
            background: var(--primary); color: white; border: none; padding: 14px;
            width: 100%; border-radius: 12px; font-weight: 700; font-size: 1rem;
            cursor: pointer; transition: 0.3s; margin-top: 1rem;
        }
        .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }

        .toggle-group {
            display: flex; align-items: center; gap: 10px;
            padding: 15px; background: #f0fdf4; border-radius: 12px; border: 1px solid #dcfce7;
        }
        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 1rem;
        }
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; }
            .sidebar span, .brand-box h3 { display: none; }
            .main-content { margin-left: 80px; }
        }

        @media (max-width: 768px) {
            .grid-row { grid-template-columns: 1fr; }
            .main-content { padding: 1.5rem; }
            .form-container { padding: 1.5rem; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-box">
        <img src="../assets/images/logo.png" alt="DineFlow Logo" class="brand-logo">
        <h3>DineFlow</h3>
    </div>
    <ul class="nav-list">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fa fa-chart-pie"></i> <span>Overview</span></a></li>
        <li class="nav-item active"><a href="manage-menu.php" class="nav-link"><i class="fa fa-hamburger"></i> <span>Manage Menu</span></a></li>
        <li class="nav-item"><a href="manage-tables.php" class="nav-link"><i class="fa fa-qrcode"></i> <span>QR Tables</span></a></li>
        <li class="nav-item"><a href="../kitchen/live-orders.php" class="nav-link"><i class="fa fa-clock"></i> <span>Live Orders</span></a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fa fa-chart-line"></i> <span>Sales Reports</span></a></li>
        <li class="nav-item"><a href="settings.php" class="nav-link"><i class="fa fa-cog"></i> <span>Settings</span></a></li>
         <div class="logout-section">
            <li class="nav-item" style="list-style: none;">
                <a href="../logout.php" class="nav-link"><i class="fa fa-power-off"></i> <span>Logout</span></a>
            </li>
        </div>
    </ul>
</aside>

<main class="main-content">
    <div class="form-container">
        <header class="header-flex">
            <h1>Update Dish Details</h1>
            <a href="manage-menu.php" style="color: var(--text-muted); text-decoration: none; font-size: 0.9rem;"><i class="fa fa-times"></i> Close</a>
        </header>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_item" value="1">

            <div class="form-group">
                <label for="item_name">Dish Name</label>
                <input type="text" id="item_name" name="item_name" value="<?php echo htmlspecialchars($item['name']); ?>" required placeholder="e.g. Paneer Tikka">
            </div>

            <div class="form-group">
                <label for="dish_image">Dish Image</label>
                <div class="image-preview-wrapper">
                    <?php if ($item['image_url']): ?>
                        <img src="../<?php echo $item['image_url']; ?>" alt="Current dish photo" class="current-img">
                        <div>
                            <p style="font-size: 0.8rem; font-weight: 600;">Current Photo</p>
                            <p style="font-size: 0.75rem; color: var(--text-muted);">Upload new to replace</p>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="dish_image" name="dish_image" accept="image/*" style="border: none; padding: 0;">
            </div>

            <div class="grid-row">
                <div class="form-group">
                    <label for="price">Price (₹)</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo $item['price']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <?php
                        $categories = ['Starters', 'Main Course', 'Desserts', 'Beverages'];
                        foreach ($categories as $cat) {
                            $selected = ($item['category'] == $cat) ? 'selected' : '';
                            echo "<option value='$cat' $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="grid-row">
                <div class="form-group">
                    <label for="calories">Energy (kcal)</label>
                    <input type="number" id="calories" name="calories" value="<?php echo $item['calories']; ?>" required>
                </div>
                <div class="form-group">
                    <label for="protein">Protein (g)</label>
                    <input type="number" id="protein" name="protein" value="<?php echo $item['protein']; ?>" required>
                </div>
            </div>

            <div class="form-group toggle-group">
                <input type="checkbox" name="is_available" id="available" <?php echo $item['is_available'] ? 'checked' : ''; ?> style="width: 18px; height: 18px; accent-color: var(--primary);">
                <label for="available" style="margin-bottom: 0; color: #166534;">Dish is currently available for orders</label>
            </div>

            <button type="submit" class="btn-submit">Save Changes</button>
        </form>
    </div>
</main>

</body>
</html>