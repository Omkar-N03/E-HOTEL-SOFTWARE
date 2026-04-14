<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page();

$hotel_id   = $_SESSION['hotel_id'];
$hotel_name = $_SESSION['hotel_name'] ?? 'Our Restaurant';

$specific_table_id = isset($_GET['table_id']) ? (int)$_GET['table_id'] : null;

try {
    if ($specific_table_id) {
        $stmt = $pdo->prepare("SELECT * FROM restaurant_tables WHERE hotel_id = ? AND id = ?");
        $stmt->execute([$hotel_id, $specific_table_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM restaurant_tables WHERE hotel_id = ? ORDER BY table_number ASC");
        $stmt->execute([$hotel_id]);
    }
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching tables: " . $e->getMessage());
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_path = $_SERVER['SCRIPT_NAME'];
$project_root = str_replace('/admin/generate-qr.php', '', $script_path);
$base_url = $protocol . $host . $project_root . "/customer/menu.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print QR Codes | <?php echo htmlspecialchars($hotel_name); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --bg-main: #f8fafc;
            --dark: #0f172a;
            --slate-500: #64748b;
            --radius: 20px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
        }

        .no-print-header {
            background: white;
            padding: 1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .logo-sidebar {
            width: 50px;
            height: 50px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
            border: none;
            cursor: pointer;
        }

        .btn-back { background: var(--bg-main); color: var(--slate-500); }
        .btn-print { background: var(--primary); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }

        .qr-container {
            padding: 2.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .qr-card {
            background: white;
            border-radius: var(--radius);
            padding: 2.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .qr-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: var(--primary);
        }

        .hotel-title { font-size: 1.4rem; font-weight: 700; color: var(--dark); margin-bottom: 0.5rem; }
        .table-label { color: var(--slate-500); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; font-weight: 600; }
        .table-no { font-size: 4rem; font-weight: 800; color: var(--primary); margin: 0.5rem 0; line-height: 1; }

        .qr-image-wrap {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 16px;
            display: inline-block;
            margin: 1.5rem 0;
        }

        .qr-image-wrap img { display: block; mix-blend-mode: multiply; }

        .scan-text { font-weight: 600; color: var(--dark); margin-bottom: 4px; }
        .url-text { color: var(--slate-500); font-size: 0.7rem; word-break: break-all; max-width: 200px; margin: 0 auto; }

        @media (max-width: 640px) {
            .no-print-header { padding: 1rem; }
            .qr-container { padding: 1rem; }
            .qr-grid { grid-template-columns: 1fr; }
        }

        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .qr-container { padding: 0; max-width: none; }
            .qr-grid { display: block; }
            .qr-card {
                box-shadow: none;
                border: 2px solid #eee;
                page-break-inside: avoid;
                margin-bottom: 30px;
                width: 100%;
                max-width: 350px;
                margin-left: auto;
                margin-right: auto;
            }
        }
    </style>
</head>
<body>

<div class="no-print-header no-print">
    <div class="header-left">
        <div class="logo-sidebar">
            <i class="fa fa-utensils"></i>
        </div>
        <a href="manage-tables.php" class="btn btn-back">
            <i class="fa fa-arrow-left"></i> <span>Back to Tables</span>
        </a>
    </div>
    <div style="display: flex; align-items: center; gap: 20px;">
        <span style="color: var(--slate-500); font-size: 0.9rem;" class="no-print">
            <i class="fa fa-link"></i> <?php echo $host; ?>
        </span>
        <button onclick="window.print()" class="btn btn-print">
            <i class="fa fa-print"></i> Print QR Set
        </button>
    </div>
</div>

<div class="qr-container">
    <div class="qr-grid">
        <?php foreach ($tables as $t):
            $target_url = $base_url . "?hotel_id=" . $hotel_id . "&table_id=" . $t['id'];            // Using a cleaner QR API with better styling
            $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($target_url);
        ?>
        <div class="qr-card">
            <div class="table-label">Table Number</div>
            <div class="table-no"><?php echo htmlspecialchars($t['table_number']); ?></div>

            <div class="qr-image-wrap">
                <img src="<?php echo $qr_url; ?>" width="180" height="180" alt="QR Code">
            </div>

            <h2 class="hotel-title"><?php echo htmlspecialchars($hotel_name); ?></h2>
            <p class="scan-text">Scan to view menu & order</p>
            <p class="url-text"><?php echo str_replace($protocol, '', $target_url); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>