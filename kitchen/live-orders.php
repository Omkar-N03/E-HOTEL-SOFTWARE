<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['hotel_admin', 'kitchen_staff']);

$hotel_id = $_SESSION['hotel_id'];

if (isset($_GET['update_id']) && isset($_GET['new_status'])) {
    $order_id = (int)$_GET['update_id'];
    $new_status = $_GET['new_status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$new_status, $order_id, $hotel_id]);
    header("Location: live-orders.php?msg=status_updated");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders 
                           WHERE hotel_id = ? AND status IN ('pending', 'preparing') 
                           ORDER BY order_time ASC");
    $stmt->execute([$hotel_id]);
    $live_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Kitchen Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="20">
    <title>Kitchen Board | <?php echo htmlspecialchars($_SESSION['hotel_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #121212;
            --card-bg: #1e1e1e;
            --primary-text: #ffffff;
            --secondary-text: #b0b0b0;
            --accent-red: #ff5252;
            --accent-yellow: #ffd740;
            --accent-green: #69f0ae;
            --header-bg: #263238;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background: var(--dark-bg); 
            color: var(--primary-text); 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            line-height: 1.6;
        }
        .kitchen-header { 
            background: var(--header-bg); 
            padding: 1rem; 
            display: flex; 
            flex-wrap: wrap; 
            justify-content: space-between; 
            align-items: center; 
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 4px solid #3498db;
        }
        .brand-zone { display: flex; align-items: center; gap: 10px; }
        .brand-zone i { color: var(--accent-red); font-size: 1.5rem; }
        .brand-zone h1 { font-size: 1.2rem; text-transform: uppercase; letter-spacing: 1px; }
        #liveClock { 
            font-family: 'Courier New', monospace; 
            background: rgba(0,0,0,0.3); 
            padding: 5px 12px; 
            border-radius: 4px; 
            font-weight: bold;
            color: var(--accent-green);
        }
        .btn-dash {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .kitchen-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
            padding: 20px; 
        }
        .order-card { 
            background: var(--card-bg); 
            border-radius: 12px; 
            overflow: hidden; 
            display: flex; 
            flex-direction: column; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            border: 1px solid #333;
            transition: transform 0.2s;
        }
        .status-strip { height: 8px; width: 100%; }
        .pending .status-strip { background: var(--accent-red); }
        .preparing .status-strip { background: var(--accent-yellow); }
        .card-body { padding: 15px; flex-grow: 1; }
        .card-meta { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #333; 
            padding-bottom: 10px; 
            margin-bottom: 15px; 
        }
        .table-label { 
            font-size: 1.4rem; 
            font-weight: 800; 
            background: #333; 
            padding: 2px 10px; 
            border-radius: 4px; 
        }
        .timer { font-weight: bold; color: #ffa726; font-size: 0.95rem; }
        .items-area { margin-bottom: 20px; }
        .item-row { 
            display: flex; 
            padding: 8px 0; 
            border-bottom: 1px solid #2a2a2a; 
            font-size: 1.1rem;
        }
        .item-row i { color: #3498db; margin-right: 10px; margin-top: 5px; font-size: 0.8rem; }
        .card-actions { padding: 10px; background: rgba(0,0,0,0.2); display: flex; }
        .btn-action { 
            flex: 1; 
            padding: 15px; 
            border: none; 
            border-radius: 8px; 
            font-weight: bold; 
            text-transform: uppercase;
            cursor: pointer; 
            text-decoration: none; 
            text-align: center;
            font-size: 1rem;
        }
        .btn-start { background: #e67e22; color: white; }
        .btn-serve { background: #2ecc71; color: white; }
        @media (max-width: 600px) {
            .kitchen-header { flex-direction: column; text-align: center; }
            .kitchen-grid { grid-template-columns: 1fr; padding: 10px; }
            .brand-zone h1 { font-size: 1rem; }
        }
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 20px;
            color: #555;
        }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; color: #2ecc71; }
    </style>
</head>
<body class="kitchen-body">
    <header class="kitchen-header">
        <div class="brand-zone">
            <i class="fa fa-fire-burner"></i>
            <h1>Kitchen Board</h1>
        </div>
        <div id="liveClock">00:00:00</div>
        <div class="nav-zone">
            <a href="../admin/dashboard.php" class="btn-dash"><i class="fa fa-arrow-left"></i> Exit</a>
        </div>
    </header>
    <div class="kitchen-grid" id="orderGrid">
        <?php if (empty($live_orders)): ?>
            <div class="empty-state">
                <i class="fa fa-circle-check"></i>
                <h2>KITCHEN CLEAR</h2>
                <p>No active orders at the moment.</p>
            </div>
        <?php endif; ?>
        <?php foreach ($live_orders as $order): 
            $wait_time = round((time() - strtotime($order['order_time'])) / 60);
        ?>
            <div class="order-card <?php echo $order['status']; ?>">
                <div class="status-strip"></div>
                <div class="card-body">
                    <div class="card-meta">
                        <span class="table-label">T-<?php echo $order['table_number']; ?></span>
                        <span class="timer"><i class="fa fa-hourglass-half"></i> <?php echo $wait_time; ?>m</span>
                    </div>
                    <div class="items-area">
                        <?php 
                        $items = explode(',', $order['items_summary']); 
                        foreach($items as $item): 
                        ?>
                            <div class="item-row">
                                <i class="fa fa-circle"></i> 
                                <span><?php echo htmlspecialchars(trim($item)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-actions">
                    <?php if ($order['status'] == 'pending'): ?>
                        <a href="live-orders.php?update_id=<?php echo $order['id']; ?>&new_status=preparing" class="btn-action btn-start">
                            Start Preparation
                        </a>
                    <?php else: ?>
                        <a href="live-orders.php?update_id=<?php echo $order['id']; ?>&new_status=served" class="btn-action btn-serve">
                            Ready to Serve
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <audio id="notificationSound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3"></audio>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').innerText = now.toLocaleTimeString();
        }
        setInterval(updateClock, 1000);
        updateClock();
        const lastCount = localStorage.getItem('kitchen_order_count') || 0;
        const currentCount = <?php echo count($live_orders); ?>;
        if (currentCount > lastCount) {
            document.getElementById('notificationSound').play().catch(e => console.log("Sound blocked by browser"));
        }
        localStorage.setItem('kitchen_order_count', currentCount);
    </script>
</body>
</html>
