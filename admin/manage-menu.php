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
    if ($item = $stmt->fetch() && $item['image_url'] && file_exists("../" . $item['image_url'])) {
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
    $category = $_POST['category'] ?? '';
    $image_path = null;

    if (!empty($name) && $price > 0) {
        if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] == 0) {
            $upload_dir = "../assets/images/menu/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $image_path = "assets/images/menu/" . time() . "_" . $_FILES["dish_image"]["name"];
            move_uploaded_file($_FILES["dish_image"]["tmp_name"], "../" . $image_path);
        }
        $pdo->prepare("INSERT INTO menu_items (hotel_id, name, price, image_url, calories, protein, category, is_available) VALUES (?,?,?,?,?,?,?,1)")
            ->execute([$hotel_id, $name, $price, $image_path, $calories, $protein, $category]);
        $message = "Item added successfully!";
        $message_type = "success";
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
    <title>Manage Menu | Rio</title>
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
        }

        @keyframes slideRight {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--bg-body);
            display: flex;
            color: var(--text-dark);
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%);
            height: 100vh;
            position: fixed;
            padding: 2rem 1.5rem;
            color: white;
            box-shadow: 2px 0 15px rgba(99, 102, 241, 0.2);
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 3rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .brand img {
            background: rgba(255, 255, 255, 0.25);
            padding: 8px;
            border-radius: 12px;
            width: 45px;
            height: 45px;
            object-fit: cover;
        }

        .nav-list {
            list-style: none;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.8rem;
            opacity: 0;
            animation: slideRight 0.5s ease forwards;
        }

        .nav-item:nth-child(1) { animation-delay: 0.1s; }
        .nav-item:nth-child(2) { animation-delay: 0.2s; }
        .nav-item:nth-child(3) { animation-delay: 0.3s; }
        .nav-item:nth-child(4) { animation-delay: 0.4s; }
        .nav-item:nth-child(5) { animation-delay: 0.5s; }
        .nav-item:nth-child(6) { animation-delay: 0.6s; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.9rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: 0.3s;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.2);
        }

        .logout-section {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 1rem;
        }

        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 3rem;
            animation: fadeIn 0.8s ease;
        }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; }
        .page-header h1 { font-size: 1.75rem; font-weight: 700; }
        .btn-add { background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .table-container { background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px 24px; background: #fcfdfe; color: var(--text-gray); font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
        td { padding: 16px 24px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
        .dish-cell { display: flex; align-items: center; gap: 16px; }
        .dish-img { width: 56px; height: 56px; border-radius: 12px; object-fit: cover; background: #f1f5f9; }
        .dish-name { font-weight: 600; color: var(--text-dark); }
        .category-pill { background: var(--primary-bg); color: var(--primary); padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .price-text { font-weight: 700; font-size: 1rem; }
        .nutrition-info { font-size: 0.8rem; color: var(--text-gray); line-height: 1.4; }
        .switch { position: relative; display: inline-block; width: 44px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; inset: 0; background-color: #e2e8f0; transition: 0.4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: 0.4s; border-radius: 50%; }
        input:checked + .slider { background-color: var(--success); }
        input:checked + .slider:before { transform: translateX(20px); }
        .action-btns a { color: var(--text-gray); font-size: 1.1rem; margin-left: 12px; transition: 0.2s; }
        .action-btns a:hover { color: var(--primary); }
        .modal { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 2rem; border-radius: 20px; width: 480px; }
        .form-group { margin-bottom: 1.25rem; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 10px; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 2rem 0.5rem; }
            .sidebar span, .brand > span { display: none; }
            .main-content { margin-left: 80px; }
        }
    </style>
</head>
<body>
    
    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Menu Management</h1>
                <p style="color: var(--text-gray);">Add, edit or remove dishes from your digital menu.</p>
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
                                <img src="../<?= $item['image_url'] ?: 'assets/images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="dish-img">
                                <span class="dish-name"><?= htmlspecialchars($item['name']) ?></span>
                            </div>
                        </td>
                        <td><span class="category-pill"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td><span class="price-text">₹<?= number_format($item['price'], 2) ?></span></td>
                        <td>
                            <div class="nutrition-info">
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
                        <td class="action-btns" style="text-align: right;">
                            <a href="edit-item.php?id=<?= $item['id'] ?>"><i class="fa fa-pen-to-square"></i></a>
                            <a href="?delete=<?= $item['id'] ?>" class="delete-btn" onclick="return confirm('Delete item?')"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <dialog id="addModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" style="margin-bottom: 1.5rem;">Add Menu Item</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_item" value="1">
                <div class="form-group">
                    <label for="item_name">Dish Name</label>
                    <input type="text" id="item_name" name="item_name" class="form-control" placeholder="e.g. Classic Sweet Lassi" required>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="price">Price (₹)</label>
                        <input type="number" id="price" step="0.01" name="price" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <option>Beverages</option>
                            <option>Main Course</option>
                            <option>Starters</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dish_image">Dish Image</label>
                    <input type="file" id="dish_image" name="dish_image" class="form-control">
                </div>
                <button type="submit" class="btn-add" style="width: 100%; justify-content: center; margin-top: 10px;">Save Item</button>
            </form>
        </div>
    </dialog>

    <script>
        const openModal = () => document.getElementById('addModal').showModal();
        const closeModal = (e) => document.getElementById('addModal').close();

        function toggleStatus(id) {
            fetch(`../api/toggle-availability.php?id=${id}`)
                .then(res => res.json())
                .catch(() => alert("Update failed"));
        }

        document.getElementById('addModal').addEventListener('click', (e) => {
            if (e.target.id === 'addModal') document.getElementById('addModal').close();
        });
    </script>
</body>
</html>