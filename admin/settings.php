<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";
$messageType = "";

$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $hotel_name = strip_tags($_POST['hotel_name']);
    $tax_percent = (float)$_POST['tax_percent'];
    $currency = strip_tags($_POST['currency']);
    $logo_name = $hotel['logo_url'];

    if (!empty($_FILES['logo']['name'])) {
        $target_dir = "../assets/img/logos/";

        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "hotel_" . $hotel_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        $valid_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $valid_extensions)) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                $logo_name = $new_filename;
                $_SESSION['hotel_logo'] = $logo_name;
            }
        } else {
            $message = "Invalid image format. Use JPG, PNG or WEBP.";
            $messageType = "error";
        }
    }

    if ($messageType !== "error") {
        try {
            $update = $pdo->prepare("UPDATE hotels SET hotel_name = ?, tax_percent = ?, currency = ?, logo_url = ? WHERE id = ?");
            $update->execute([$hotel_name, $tax_percent, $currency, $logo_name, $hotel_id]);

            $_SESSION['hotel_name'] = $hotel_name;
            $message = "Settings updated successfully!";
            $messageType = "success";

            $hotel['hotel_name'] = $hotel_name;
            $hotel['tax_percent'] = $tax_percent;
            $hotel['currency'] = $currency;
            $hotel['logo_url'] = $logo_name;
        } catch (PDOException $e) {
            $message = "Update failed: " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Settings | <?php echo htmlspecialchars($hotel['hotel_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --success: #2ec4b6;
            --error: #e71d36;
            --dark: #1e1e2d;
            --light-bg: #f8f9fa;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: var(--light-bg); color: #333; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: var(--dark); color: white; transition: 0.3s; z-index: 1000; }
        .sidebar .logo-section { padding: 30px 20px; text-align: center; border-bottom: 1px solid #2d2d44; }
        .sidebar .logo-circle img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary); }
        .sidebar ul { list-style: none; padding: 20px 0; }
        .sidebar ul li a { display: block; padding: 12px 25px; color: #a2a3b7; text-decoration: none; transition: 0.3s; }
        .sidebar ul li a:hover, .sidebar ul li.active a { background: #242435; color: white; border-left: 4px solid var(--primary); }
        .main-content { flex: 1; padding: 30px; overflow-x: hidden; }
        .header-title { margin-bottom: 30px; }
        .header-title h1 { font-size: 1.8rem; color: var(--dark); }
        .settings-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .logo-preview-container { display: flex; align-items: center; gap: 20px; margin-top: 10px; }
        .logo-preview-container img { width: 100px; height: 100px; border-radius: 10px; object-fit: cover; border: 1px dashed #ccc; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 15px 30px; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-save:hover { opacity: 0.9; transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert.success { background: #d1f7f1; color: #0a5d56; border: 1px solid var(--success); }
        .alert.error { background: #ffe5e7; color: #842029; border: 1px solid var(--error); }
        @media (max-width: 768px) {
            .admin-wrapper { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .sidebar ul { display: flex; overflow-x: auto; padding: 10px; }
            .sidebar ul li a { padding: 10px 15px; white-space: nowrap; border-left: none; border-bottom: 2px solid transparent; }
            .sidebar ul li.active a { border-left: none; border-bottom: 2px solid var(--primary); }
            .main-content { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="admin-wrapper">
    <nav class="sidebar">
        <div class="logo-section">
            <div class="logo-circle">
                <img src="../assets/img/logos/<?php echo $hotel['logo_url'] ?: 'default-hotel.png'; ?>" alt="Logo">
            </div>
            <p style="margin-top:10px;"><?php echo htmlspecialchars($hotel['hotel_name']); ?></p>
        </div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Overview</a></li>
            <li><a href="manage-menu.php"><i class="fa fa-utensils"></i> Menu</a></li>
            <li><a href="manage-tables.php"><i class="fa fa-qrcode"></i> QR Codes</a></li>
            <li class="active"><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    <main class="main-content">
        <div class="header-title">
            <h1>Restaurant Configuration</h1>
            <p>Branding and financial settings</p>
        </div>
        <?php if($message): ?>
            <div class="alert <?php echo $messageType; ?>">
                <i class="fa <?php echo ($messageType == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <div class="settings-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_settings" value="1">
                <div class="settings-grid">
                    <div class="section">
                        <h3 style="margin-bottom:20px;"><i class="fa fa-palette"></i> Brand Identity</h3>
                        <div class="form-group">
                            <label>Restaurant Name</label>
                            <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Business Logo</label>
                            <div class="logo-preview-container">
                                <img src="../assets/img/logos/<?php echo $hotel['logo_url'] ?: 'default-hotel.png'; ?>" id="preview">
                                <div>
                                    <input type="file" name="logo" id="logoInput" accept="image/*">
                                    <p style="font-size: 0.8rem; color: #777; margin-top: 5px;">Recommended: Square PNG/JPG</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="section">
                        <h3 style="margin-bottom:20px;"><i class="fa fa-receipt"></i> Billing & Tax</h3>
                        <div class="form-group">
                            <label>Currency Symbol</label>
                            <input type="text" name="currency" value="<?php echo htmlspecialchars($hotel['currency']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Tax Percentage (%)</label>
                            <input type="number" name="tax_percent" step="0.01" value="<?php echo $hotel['tax_percent']; ?>" required>
                        </div>
                        <div style="background:#fff9db; padding:15px; border-radius:8px; font-size:0.9rem;">
                            <i class="fa fa-info-circle"></i> Changes reflect instantly on digital menus.
                        </div>
                    </div>
                </div>
                <div style="margin-top:30px;">
                    <button type="submit" class="btn-save">Update Configuration</button>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
document.getElementById('logoInput').onchange = evt => {
    const [file] = evt.target.files;
    if (file) {
        document.getElementById('preview').src = URL.createObjectURL(file);
    }
}
</script>
</body>
</html>
