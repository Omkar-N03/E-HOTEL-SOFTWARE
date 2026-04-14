<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin']);

$hotel_id = $_SESSION['hotel_id'];
$message = "";
$messageType = "";

// Fetch current data
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
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

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
            $message = "Invalid format. Please use JPG, PNG, or WEBP.";
            $messageType = "error";
        }
    }

    if ($messageType !== "error") {
        try {
            $update = $pdo->prepare("UPDATE hotels SET hotel_name = ?, tax_percent = ?, currency = ?, logo_url = ? WHERE id = ?");
            $update->execute([$hotel_name, $tax_percent, $currency, $logo_name, $hotel_id]);
            $_SESSION['hotel_name'] = $hotel_name;
            $message = "Configuration synced successfully.";
            $messageType = "success";
            
            // Refresh local object
            $hotel['hotel_name'] = $hotel_name;
            $hotel['tax_percent'] = $tax_percent;
            $hotel['currency'] = $currency;
            $hotel['logo_url'] = $logo_name;
        } catch (PDOException $e) {
            $message = "System error: " . $e->getMessage();
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
    <title>Settings | <?php echo htmlspecialchars($hotel['hotel_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-surface: #ffffff;
            --bg-body: #f1f5f9;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --radius-lg: 12px;
            --radius-md: 8px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); line-height: 1.5; }

        .main-content { margin-left: 280px; padding: 3rem; max-width: 1200px; }

        /* Header UI */
        .header-ui { margin-bottom: 2rem; }
        .header-ui h1 { font-size: 1.875rem; font-weight: 700; letter-spacing: -0.025em; }
        .header-ui p { color: var(--text-muted); margin-top: 0.25rem; }

        /* Card Layout */
        .settings-container {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .settings-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: 600px;
        }

        /* Tabs-like Sidebar inside Card */
        .settings-nav {
            background: #f8fafc;
            border-right: 1px solid var(--border-color);
            padding: 1.5rem;
        }

        .nav-group-title {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        /* Form Styling */
        .form-content { padding: 2.5rem; }
        .form-section { margin-bottom: 2.5rem; }
        .form-section-title { 
            font-size: 1.1rem; 
            font-weight: 600; 
            margin-bottom: 1.25rem; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 0.75rem;
        }

        .input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .field-label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; }
        
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Logo Component */
        .logo-uploader {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: var(--radius-md);
            border: 1px dashed var(--border-color);
        }

        .logo-uploader img {
            width: 80px; height: 80px;
            border-radius: var(--radius-md);
            object-fit: cover;
            background: white;
            box-shadow: var(--shadow-sm);
        }

        /* Action Footer */
        .form-footer {
            padding: 1.5rem 2.5rem;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-primary:hover { background: var(--primary-hover); }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        @media (max-width: 992px) {
            .settings-grid { grid-template-columns: 1fr; }
            .settings-nav { display: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main-content">
    <div class="header-ui">
        <h1>Settings</h1>
        <p>Global configuration for your restaurant operations.</p>
    </div>

    <?php if($message): ?>
    <div class="alert <?php echo $messageType; ?>">
        <i class="fa <?php echo ($messageType == 'success') ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
        <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <div class="settings-container">
        <form method="POST" enctype="multipart/form-data" class="settings-grid">
            <aside class="settings-nav">
                <div class="nav-group-title">Configuration</div>
                <div style="color: var(--primary); font-weight: 600; padding: 0.5rem 0; border-left: 3px solid var(--primary); padding-left: 1rem; background: #eff6ff; border-radius: 0 4px 4px 0;">
                    <i class="fa fa-sliders"></i> General
                </div>
                </aside>

            <div class="form-content">
                <input type="hidden" name="update_settings" value="1">
                
                <section class="form-section">
                    <h3 class="form-section-title"><i class="fa fa-shop"></i> Branding</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="field-label">Restaurant Display Name</label>
                        <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" required placeholder="Enter business name">
                    </div>
                    
                    <label class="field-label">Store Logo</label>
                    <div class="logo-uploader">
                        <img src="../assets/img/logos/<?php echo $hotel['logo_url'] ?: 'default-hotel.png'; ?>" id="preview" alt="Logo">
                        <div>
                            <input type="file" name="logo" id="logoInput" accept="image/*" style="font-size: 0.8rem; margin-bottom: 0.5rem;">
                            <p style="font-size: 0.75rem; color: var(--text-muted);">Recommended: 512x512px (PNG or WEBP)</p>
                        </div>
                    </div>
                </section>

                <section class="form-section">
                    <h3 class="form-section-title"><i class="fa fa-file-invoice-dollar"></i> Localization & Tax</h3>
                    <div class="input-grid">
                        <div>
                            <label class="field-label">Currency Symbol</label>
                            <input type="text" name="currency" value="<?php echo htmlspecialchars($hotel['currency']); ?>" required placeholder="e.g. $, ₹, £">
                        </div>
                        <div>
                            <label class="field-label">Tax Rate (%)</label>
                            <input type="number" name="tax_percent" step="0.01" value="<?php echo $hotel['tax_percent']; ?>" required>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #fffbeb; border-radius: var(--radius-md); border: 1px solid #fef3c7; color: #92400e; font-size: 0.85rem; display: flex; gap: 10px; align-items: center;">
                        <i class="fa fa-lightbulb"></i>
                        <span>Tax settings apply to all items in the digital menu and generated receipts.</span>
                    </div>
                </section>

                <div class="form-footer">
                    <button type="submit" class="btn-primary">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
    // Real-time image preview
    document.getElementById('logoInput').onchange = evt => {
        const [file] = evt.target.files;
        if (file) {
            document.getElementById('preview').src = URL.createObjectURL(file);
        }
    }
</script>
</body>
</html>