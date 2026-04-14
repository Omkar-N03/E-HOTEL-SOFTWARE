<?php
require_once '../config/db.php';
require_once '../config/sessions.php';
protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";
$message_type = "";

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_url FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);
    $item = $stmt->fetch();
    
    if ($item && $item['image_url'] && file_exists("../" . $item['image_url'])) {
        unlink("../" . $item['image_url']);
    }
    
    $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND hotel_id = ?")->execute([$id, $hotel_id]);
    header("Location: manage-menu.php?msg=deleted");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $name = trim(strip_tags($_POST['item_name']));
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $calories = filter_var($_POST['calories'], FILTER_VALIDATE_INT) ?: 0;
    $protein = filter_var($_POST['protein'], FILTER_VALIDATE_INT) ?: 0;
    $category = $_POST['category'] ?? 'General';
    $image_path = null;

    $errors = [];
    if (empty($name)) $errors[] = "Dish name is required.";
    if ($price <= 0) $errors[] = "Price must be a positive number.";

    if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] == 0) {
        $upload_dir = "../assets/images/menu/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES["dish_image"]["name"], PATHINFO_EXTENSION);
        $filename = uniqid('dish_', true) . "." . $ext;
        $image_path = "assets/images/menu/" . $filename;
        move_uploaded_file($_FILES["dish_image"]["tmp_name"], "../" . $image_path);
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, name, price, image_url, calories, protein, category, is_available) VALUES (?,?,?,?,?,?,?,1)");
            $stmt->execute([$hotel_id, $name, $price, $image_path, $calories, $protein, $category]);
            $message = "Delicious! New item added to your menu.";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Database error: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = implode(" ", $errors);
        $message_type = "danger";
    }
}

$items = $pdo->prepare("SELECT * FROM menu_items WHERE hotel_id = ? ORDER BY category, name");
$items->execute([$hotel_id]);
$menu_list = $items->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Menu | Rio Professional</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-bg: #f5f6ff;
            --text-dark: #0f172a;
            --text-gray: #64748b;
            --bg-body: #f8fafc;
            --success: #10b981;
            --danger: #ef4444;
            --sidebar-width: 250px; 
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background-color: var(--bg-body); 
            display: flex; 
            color: var(--text-dark); 
            min-height: 100vh;
        }

        .sidebar-wrapper {
            width: var(--sidebar-width);
            flex-shrink: 0;
        }

        .main-content { 
            flex: 1; 
            padding: 3rem; 
            animation: fadeIn 0.6s ease; 
            overflow-x: hidden; 
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; }
        .btn-add { background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-add:hover { background: #4f46e5; transform: translateY(-1px); }

        .table-container { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 18px 24px; background: #fcfdfe; color: var(--text-gray); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid #f1f5f9; }
        td { padding: 16px 24px; border-bottom: 1px solid #f8fafc; }

        .dish-cell { display: flex; align-items: center; gap: 16px; }
        .dish-img { width: 50px; height: 50px; border-radius: 10px; object-fit: cover; background: #eee; }
        .dish-name { font-weight: 600; }
        .category-pill { background: var(--primary-bg); color: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .price-text { font-weight: 700; color: var(--text-dark); }
        
        .modal { border: none; border-radius: 20px; padding: 0; width: 500px; max-width: 90vw; margin: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
        .modal::backdrop { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-content { padding: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 0.875rem; font-weight: 600; color: var(--text-gray); }
        .form-control { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 1rem; transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .toast { position: fixed; top: 20px; right: 20px; padding: 16px 24px; border-radius: 12px; color: white; font-weight: 500; z-index: 9999; animation: slideIn 0.3s ease; }
        .toast-success { background: var(--success); }
        .toast-danger { background: var(--danger); }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #e2e8f0; transition: 0.4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: 0.4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(20px); }
    </style>
</head>
<body>
    <div class="sidebar-wrapper">
        <?php include 'sidebar.php'; ?>
    </div>

    <?php if ($message): ?>
    <div class="toast toast-<?= $message_type ?>" id="notification">
        <?= $message ?>
    </div>
    <script>setTimeout(() => document.getElementById('notification').remove(), 4000);</script>
    <?php endif; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Menu Management</h1>
                <p style="color: var(--text-gray);">Curate and manage your hotel's culinary offerings.</p>
            </div>
            <button class="btn-add" onclick="openModal()"><i class="fa fa-plus"></i> Add New Dish</button>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Dish Details</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Nutrition</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menu_list as $item): ?>
                    <tr>
                        <td>
                            <div class="dish-cell">
                                <img src="../<?= $item['image_url'] ?: 'assets/images/placeholder.jpg' ?>" class="dish-img">
                                <span class="dish-name"><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                        </td>
                        <td><span class="category-pill"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td><span class="price-text">₹<?= number_format($item['price'], 2) ?></span></td>
                        <td>
                            <div style="font-size: 0.8rem; color: var(--text-gray);">
                                <span><i class="fa fa-fire" style="color:#f97316"></i> <?= $item['calories'] ?> kcal</span><br>
                                <span><i class="fa fa-leaf" style="color:#10b981"></i> <?= $item['protein'] ?>g Protein</span>
                            </div>
                        </td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" <?= $item['is_available'] ? 'checked' : '' ?> onchange="toggleStatus(<?= $item['id'] ?>)">
                                <span class="slider"></span>
                            </label>
                        </td>
                        <td style="text-align: right; color: var(--text-gray);">
                            <a href="edit-item.php?id=<?= $item['id'] ?>" style="color: inherit; margin-right: 15px;"><i class="fa fa-pen-to-square"></i></a>
                            <a href="?delete=<?= $item['id'] ?>" style="color: var(--danger);" onclick="return confirm('Archive this item?')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <dialog id="addModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-size: 1.5rem;">Add New Dish</h2>
                <button onclick="closeModal()" style="background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-gray);">&times;</button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_item" value="1">
                
                <div class="form-group">
                    <label>Dish Name</label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g. Grilled Mediterranean Salmon" required>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Price (₹)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option>Starters</option>
                            <option>Main Course</option>
                            <option>Desserts</option>
                            <option>Beverages</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Calories (kcal)</label>
                        <input type="number" name="calories" class="form-control" placeholder="0">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Protein (g)</label>
                        <input type="number" name="protein" class="form-control" placeholder="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Dish Image (High Quality)</label>
                    <input type="file" name="dish_image" class="form-control" accept="image/*">
                </div>

                <button type="submit" class="btn-add" style="width: 100%; justify-content: center; margin-top: 10px;">
                    <i class="fa fa-cloud-arrow-up"></i> Publish Item
                </button>
            </form>
        </div>
    </dialog>

    <script>
        const modal = document.getElementById('addModal');
        const openModal = () => modal.showModal();
        const closeModal = () => modal.close();

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        function toggleStatus(id) {
            fetch(`../api/toggle-availability.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if(!data.success) alert("Failed to update status.");
                })
                .catch(() => alert("Network error."));
        }
    </script>
</body>
</html>