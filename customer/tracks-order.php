<?php
require_once '../config/db.php';
session_start();

// 1. Logic check: Priority to GET parameter, fallback to Session for persistence
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : ($_SESSION['last_order_id'] ?? null);

if (!$order_id) {
    header("Location: menu.php");
    exit();
}

try {
    // 2. Optimized Query: Grabbing order and hotel details in one join
    $stmt = $pdo->prepare("
        SELECT o.*, h.hotel_name, h.currency, h.logo_url 
        FROM orders o 
        JOIN hotels h ON o.hotel_id = h.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Order Not Found</h2><p>We couldn't locate Order #$order_id.</p></div>");
    }

    // 3. Safety Logic: Handle missing 'created_at' or 'logo_url' gracefully
    $order_time = isset($order['created_at']) ? date('h:i A', strtotime($order['created_at'])) : "Just now";
    
    $logo = (!empty($order['logo_url']) && file_exists("../assets/img/logos/" . $order['logo_url']))
        ? $order['logo_url']
        : 'default.png';

} catch (PDOException $e) {
    // Helpful for debugging during development at PCE
    die("Database Connection Error: " . $e->getMessage());
}

// 4. Status Mapping for UI
$status_steps = [
    'pending'   => 1,
    'preparing' => 2,
    'served'    => 3
];
$current_step = $status_steps[$order['status']] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if($order['status'] !== 'served'): ?>
    <meta http-equiv="refresh" content="20">
    <?php endif; ?>
    
    <title>Track Order #<?php echo $order_id; ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #10b981;
            --primary-bg: #ecfdf5;
            --dark: #0f172a;
            --slate-400: #94a3b8;
            --slate-100: #f1f5f9;
            --bg: #f8fafc;
            --radius: 28px;
        }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--dark);
        }

        .top-banner {
            background: var(--dark);
            height: 200px;
            padding: 40px 20px;
            text-align: center;
            color: white;
            border-bottom-left-radius: 45px;
            border-bottom-right-radius: 45px;
        }

        .hotel-logo {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.15);
            margin-bottom: 12px;
            object-fit: cover;
            background: white;
        }

        .main-card {
            max-width: 480px;
            margin: -70px auto 40px;
            background: white;
            border-radius: var(--radius);
            padding: 35px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
        }

        /* Vertical Roadmap Styles */
        .roadmap { margin: 30px 0; }
        .roadmap-item {
            display: flex;
            gap: 20px;
            margin-bottom: 35px;
            position: relative;
        }

        .roadmap-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 21px;
            top: 45px;
            bottom: -25px;
            width: 2px;
            background: var(--slate-100);
        }

        .roadmap-item.active:not(:last-child)::after { background: var(--primary); }

        .icon-circle {
            width: 44px;
            height: 44px;
            background: var(--slate-100);
            color: var(--slate-400);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            font-size: 18px;
            transition: 0.4s;
        }

        .active .icon-circle {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 0 8px var(--primary-bg);
        }

        .step-content h4 { margin: 0; font-size: 1.05rem; font-weight: 700; }
        .step-content p { margin: 5px 0 0; font-size: 0.85rem; color: var(--slate-400); line-height: 1.4; }

        /* Summary Section */
        .summary-box {
            background: var(--slate-100);
            border-radius: 20px;
            padding: 20px;
            margin-top: 25px;
        }

        .summary-header {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--slate-400);
            margin-bottom: 15px;
            display: block;
            font-weight: 700;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .total-row {
            border-top: 2px dashed #e2e8f0;
            margin-top: 15px;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--dark);
        }

        .btn-action {
            display: block;
            text-align: center;
            background: var(--dark);
            color: white;
            text-decoration: none;
            padding: 18px;
            border-radius: 20px;
            margin-top: 30px;
            font-weight: 700;
            transition: 0.3s ease;
        }

        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

        .pulse { animation: statusPulse 2s infinite; }
        @keyframes statusPulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.15); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="top-banner">
    <img class="hotel-logo" src="../assets/img/logos/<?php echo $logo; ?>" alt="hotel logo">
    <h2 style="margin: 0;"><?php echo htmlspecialchars($order['hotel_name']); ?></h2>
    <p style="opacity: 0.8; font-size: 0.9rem; margin-top: 8px;">
        Order #<?php echo $order_id; ?> • Table <?php echo htmlspecialchars($order['table_number'] ?? 'N/A'); ?>
    </p>
</div>

<div class="main-card">
    <div class="roadmap">
        <div class="roadmap-item <?php echo ($current_step >= 1) ? 'active' : ''; ?>">
            <div class="icon-circle">
                <i class="fa fa-receipt <?php echo ($current_step == 1) ? 'pulse' : ''; ?>"></i>
            </div>
            <div class="step-content">
                <h4>Order Received</h4>
                <p><?php echo ($current_step == 1) ? 'The kitchen is acknowledging your request...' : 'Accepted at ' . $order_time; ?></p>
            </div>
        </div>

        <div class="roadmap-item <?php echo ($current_step >= 2) ? 'active' : ''; ?>">
            <div class="icon-circle">
                <i class="fa fa-fire-burner <?php echo ($current_step == 2) ? 'pulse' : ''; ?>"></i>
            </div>
            <div class="step-content">
                <h4>Chef is Cooking</h4>
                <p><?php echo ($current_step == 2) ? 'Your delicious meal is on the stove.' : (($current_step > 2) ? 'Cooking finished.' : 'Waiting for an available chef...'); ?></p>
            </div>
        </div>

        <div class="roadmap-item <?php echo ($current_step >= 3) ? 'active' : ''; ?>">
            <div class="icon-circle">
                <i class="fa fa-utensils <?php echo ($current_step == 3) ? 'pulse' : ''; ?>"></i>
            </div>
            <div class="step-content">
                <h4>Served & Ready</h4>
                <p><?php echo ($current_step == 3) ? 'Bon appétit! Enjoy your food. 🍽️' : 'Our team will bring it to your table shortly.'; ?></p>
            </div>
        </div>
    </div>

    <div class="summary-box">
        <span class="summary-header">Order Summary</span>
        <?php
        $items = explode(',', $order['items_summary']);
        foreach($items as $item): ?>
            <div class="item-row">
                <span><?php echo htmlspecialchars(trim($item)); ?></span>
            </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Total</span>
            <span><?php echo $order['currency'] ?? '₹'; ?><?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>

    <?php if($order['status'] == 'served'): ?>
        <a href="menu.php" class="btn-action">
            Order Something Else <i class="fa fa-arrow-right" style="margin-left: 10px;"></i>
        </a>
    <?php endif; ?>
</div>

<?php 
// Play notification sound only once when status changes to served
if($order['status'] == 'served' && !isset($_SESSION['alerted_'.$order_id])): ?>
    <audio autoplay>
        <source src="../assets/audio/notification.mp3" type="audio/mpeg">
    </audio>
    <?php $_SESSION['alerted_'.$order_id] = true; ?>
<?php endif; ?>

</body>
</html>