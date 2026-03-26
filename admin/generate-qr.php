<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['hotel_id'])) {
    header("Location: index.php");
    exit();
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Segoe UI', sans-serif; margin: 0; }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .qr-grid { display: block !important; }
            .qr-card { 
                page-break-inside: avoid; 
                margin: 20px auto; 
                width: 300px; 
                border: 1px solid #ddd;
            }
        }
        .print-header {
            background: #2c3e50;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .qr-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
            gap: 30px; 
            padding: 40px; 
        }
        .qr-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            text-align: center; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }
        .table-no { font-size: 3.5rem; font-weight: bold; color: #2ecc71; margin: 10px 0; }
        .btn { padding: 10px 20px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: 600; }
        .btn-print { background: #2ecc71; color: white; }
        .btn-back { background: rgba(255,255,255,0.2); color: white; }
    </style>
</head>
<body>
<div class="print-header no-print">
    <a href="manage-tables.php" class="btn btn-back"><i class="fa fa-arrow-left"></i> Back</a>
    <div>
        <span style="margin-right: 15px; opacity: 0.8;"><i class="fa fa-desktop"></i> IP: <?php echo $host; ?></span>
        <button onclick="window.print()" class="btn btn-print"><i class="fa fa-print"></i> Print All</button>
    </div>
</div>
<div class="qr-grid">
    <?php foreach ($tables as $t): 
        $target_url = $base_url . "?hotel_id=" . $hotel_id . "&table_id=" . $t['id'];
        $qr_url = "https://quickchart.io/qr?size=300&text=" . urlencode($target_url);
    ?>
    <div class="qr-card">
        <h2 style="margin:0; color:#2c3e50;"><?php echo htmlspecialchars($hotel_name); ?></h2>
        <div style="color: #95a5a6; text-transform: uppercase; font-size: 0.8rem; margin-top: 10px;">Table</div>
        <div class="table-no"><?php echo htmlspecialchars($t['table_number']); ?></div>
        <div class="qr-image-wrap">
            <img src="<?php echo $qr_url; ?>" width="200" alt="QR Code">
        </div>
        <p style="margin-bottom: 5px;"><strong>Scan to View Menu</strong></p>
        <small style="color: #bdc3c7; font-size: 10px; word-break: break-all;"><?php echo $target_url; ?></small>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
