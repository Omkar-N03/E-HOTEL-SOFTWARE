<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";
$message_type = "";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') {
        $message = "Item removed successfully.";
        $message_type = "success";
    } elseif ($_GET['msg'] == 'updated') {
        $message = "Item updated successfully.";
        $message_type = "success";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT image_url FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);
    $item = $stmt->fetch();
    if($item && $item['image_url'] && file_exists("../" . $item['image_url'])) {
        unlink("../" . $item['image_url']);
    }
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);
    header("Location: manage-menu.php?msg=deleted");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $name = strip_tags($_POST['item_name']);
    $price = $_POST['price'];
    $calories = (int)$_POST['calories'];
    $protein = (int)$_POST['protein'];
    $category = $_POST['category'];
    $image_path = null;

    if (isset($_FILES['dish_image']) && $_FILES['dish_image']['error'] == 0) {
        $upload_dir = "../assets/images/menu/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_ext = pathinfo($_FILES["dish_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = time() . "_" . uniqid() . "." . $file_ext;
        $target_file = $upload_dir . $new_filename;
        if (move_uploaded_file($_FILES["dish_image"]["tmp_name"], $target_file)) {
            $image_path = "assets/images/menu/" . $new_filename;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, name, price, image_url, calories, protein, category, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$hotel_id, $name, $price, $image_path, $calories, $protein, $category]);
        $message = "Item added successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Database Error: " . $e->getMessage();
        $message_type = "error";
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE hotel_id = ? ORDER BY category, name");
    $stmt->execute([$hotel_id]);
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $menu_items = [];
    $message = "Error: " . $e->getMessage();
    $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Menu | <?php echo htmlspecialchars($_SESSION['hotel_name'] ?? 'Admin'); ?></title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="hotel-profile">
            <i class="fa fa-utensils fa-2x" style="color: #2ecc71; margin-bottom: 10px;"></i>
            <h3><?php echo htmlspecialchars($_SESSION['hotel_name'] ?? 'Hotel Admin'); ?></h3>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li class="active"><a href="manage-menu.php"><i class="fa fa-list"></i> Manage Menu</a></li>
            <li><a href="manage-tables.php"><i class="fa fa-qrcode"></i> Tables & QR</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <header class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1>Menu Management</h1>
            <button class="btn btn-primary" onclick="toggleModal()" style="background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                <i class="fa fa-plus"></i> Add New Dish
            </button>
        </header>
        <?php if($message): ?>
            <div class="alert" style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background: <?php echo $message_type == 'success' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $message_type == 'success' ? '#166534' : '#991b1b'; ?>; border: 1px solid <?php echo $message_type == 'success' ? '#166534' : '#991b1b'; ?>;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <section class="table-section" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #eee;">
                        <th style="padding: 12px;">Dish</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Nutrition</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($menu_items)): ?>
                        <tr><td colspan="6" style="padding: 30px; text-align: center; color: #888;">No items found. Click 'Add New Dish' to start.</td></tr>
                    <?php else: ?>
                        <?php foreach($menu_items as $item): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 12px; display: flex; align-items: center; gap: 12px;">
                                <?php if($item['image_url']): ?>
                                    <img src="../<?php echo $item['image_url']; ?>" style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                                <?php else: ?>
                                    <div style="width: 45px; height: 45px; background: #f9f9f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #ccc;"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                            </td>
                            <td><span style="background: #e8f4fd; color: #3498db; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($item['category'] ?? 'General'); ?></span></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td style="font-size: 0.85rem; color: #666;">
                                <?php echo $item['calories']; ?> kcal | <?php echo $item['protein']; ?>g Prot
                            </td>
                            <td>
                                <label class="switch">
                                    <input type="checkbox" <?php echo ($item['is_available'] == 1) ? 'checked' : ''; ?> 
                                           onchange="toggleStock(<?php echo $item['id']; ?>)">
                                    <span class="slider round"></span>
                                </label>
                            </td>
                            <td>
                                <a href="edit-item.php?id=<?php echo $item['id']; ?>" style="color: #3498db; margin-right: 15px;"><i class="fa fa-edit"></i></a>
                                <a href="manage-menu.php?delete=<?php echo $item['id']; ?>" style="color: #e74c3c;" onclick="return confirm('Are you sure you want to delete this dish?')"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<div id="itemModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6);">
    <div class="modal-content" style="background: white; margin: 5% auto; padding: 25px; width: 450px; border-radius: 10px; position: relative; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <span onclick="toggleModal()" style="position: absolute; right: 20px; top: 15px; cursor: pointer; font-size: 24px; color: #aaa;">&times;</span>
        <h2 style="margin-bottom: 20px; color: #2c3e50;">Add New Menu Item</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_item" value="1">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Dish Name</label>
                <input type="text" name="item_name" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required placeholder="e.g. Butter Chicken">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Dish Image</label>
                <input type="file" name="dish_image" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Price (₹)</label>
                    <input type="number" name="price" step="0.01" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Category</label>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="Starters">Starters</option>
                        <option value="Main Course">Main Course</option>
                        <option value="Desserts">Desserts</option>
                        <option value="Beverages">Beverages</option>
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Calories</label>
                    <input type="number" name="calories" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Protein (g)</label>
                    <input type="number" name="protein" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
            </div>
            <button type="submit" style="width: 100%; padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Add to Menu</button>
        </form>
    </div>
</div>
<script>
function toggleModal() {
    const modal = document.getElementById('itemModal');
    modal.style.display = (modal.style.display === 'block') ? 'none' : 'block';
}
function toggleStock(itemId) {
    fetch(`../api/toggle-availability.php?id=${itemId}`)
    .then(response => response.json())
    .then(data => {
        if(!data.success) {
            alert('Update failed: ' + (data.message || 'Unknown error'));
            location.reload(); 
        }
    })
    .catch(err => {
        console.error('API Error:', err);
        alert('Could not connect to the server.');
    });
}
</script>
</body>
</html>
