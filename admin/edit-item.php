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
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

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
    <title>Edit Item | <?php echo htmlspecialchars($item['name']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <nav class="sidebar">
        <div class="hotel-profile"><h3>Admin Panel</h3></div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li class="active"><a href="manage-menu.php"><i class="fa fa-utensils"></i> Manage Menu</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <div class="form-container-narrow" style="max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <header class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><i class="fa fa-edit"></i> Edit Menu Item</h1>
                <a href="manage-menu.php" class="btn-back" style="text-decoration: none; color: #666;"><i class="fa fa-arrow-left"></i> Cancel</a>
            </header>
            <?php if($message): ?>
                <div class="alert error" style="background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #f87171;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="edit-form card">
                <input type="hidden" name="update_item" value="1">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Dish Name</label>
                    <input type="text" name="item_name" value="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 5px;">Dish Image</label>
                    <?php if($item['image_url']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="../<?php echo $item['image_url']; ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                            <p style="font-size: 0.8rem; color: #666;">Current image shown above</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="dish_image" accept="image/*" style="width: 100%;">
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Price (₹)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $item['price']; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Category</label>
                        <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php 
                            $categories = ['Starters', 'Main Course', 'Desserts', 'Beverages'];
                            foreach($categories as $cat) {
                                $selected = ($item['category'] == $cat) ? 'selected' : '';
                                echo "<option value='$cat' $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Calories (kcal)</label>
                        <input type="number" name="calories" value="<?php echo $item['calories']; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                    <div class="form-group">
                        <label style="display: block; font-weight: bold; margin-bottom: 5px;">Protein (grams)</label>
                        <input type="number" name="protein" value="<?php echo $item['protein']; ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                </div>
                <div class="form-group inline-checkbox" style="margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_available" id="available" <?php echo $item['is_available'] ? 'checked' : ''; ?>>
                    <label for="available">Item is currently available in stock</label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; background: #3498db; color: #fff; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
                        Update Dish Details
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
