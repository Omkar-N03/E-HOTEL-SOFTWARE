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
    <title>Kitchen Board | <?php echo htmlspecialchars($_SESSION['hotel_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --dark-bg: #0f172a;
            --card-bg: #1e293b;
            --primary-text: #f1f5f9;
            --secondary-text: #cbd5e1;
            --accent-red: #ef4444;
            --accent-yellow: #eab308;
            --accent-green: #22c55e;
            --header-bg: #1a1f35;
            --border-color: #334155;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
        }

        body {
            background: linear-gradient(135deg, var(--dark-bg) 0%, #1a1f35 100%);
            color: var(--primary-text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
        }

        .kitchen-header {
            background: linear-gradient(90deg, var(--header-bg) 0%, #2d3748 100%);
            padding: 1.5rem 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 2px solid #0ea5e9;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .brand-zone {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-zone i {
            color: var(--accent-red);
            font-size: 1.8rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .brand-zone h1 {
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: 700;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-center {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        #liveClock {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(15, 23, 42, 0.8);
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 700;
            color: var(--accent-green);
            border: 2px solid var(--accent-green);
            font-size: 1.1rem;
        }

        .status-badge {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid #60a5fa;
        }

        .nav-zone {
            display: flex;
            gap: 10px;
        }

        .btn-dash {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-dash:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(6, 182, 212, 0.4);
        }

        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            padding: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .order-card {
            background: linear-gradient(135deg, var(--card-bg) 0%, #1e293b 100%);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            border-color: #0ea5e9;
        }

        .order-card.pending {
            border-left: 5px solid var(--accent-red);
        }

        .order-card.preparing {
            border-left: 5px solid var(--accent-yellow);
        }

        .status-strip {
            height: 6px;
            width: 100%;
        }

        .pending .status-strip {
            background: linear-gradient(90deg, var(--accent-red), #dc2626);
            animation: shine 2s infinite;
        }

        .preparing .status-strip {
            background: linear-gradient(90deg, var(--accent-yellow), #ca8a04);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .card-body {
            padding: 20px;
            flex-grow: 1;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .table-label {
            font-size: 1.6rem;
            font-weight: 900;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            padding: 6px 14px;
            border-radius: 8px;
            color: white;
        }

        .timer {
            font-weight: 700;
            color: var(--accent-yellow);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .timer i {
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }

        .items-area {
            margin-bottom: 20px;
        }

        .item-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #2d3748;
            font-size: 1rem;
            align-items: center;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-row i {
            color: #0ea5e9;
            margin-right: 12px;
            font-size: 0.6rem;
        }

        .card-actions {
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            gap: 10px;
        }

        .btn-action {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .btn-action:hover::before {
            left: 100%;
        }

        .btn-start {
            background: linear-gradient(135deg, #ea580c, #f97316);
            color: white;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(234, 88, 12, 0.4);
        }

        .btn-serve {
            background: linear-gradient(135deg, #16a34a, #22c55e);
            color: white;
        }

        .btn-serve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(34, 197, 94, 0.4);
        }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 120px 20px;
            color: var(--secondary-text);
        }

        .empty-state i {
            font-size: 5rem;
            margin-bottom: 20px;
            color: var(--accent-green);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .empty-state h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: #94a3b8;
        }

        @media (max-width: 768px) {
            .kitchen-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }

            .header-center {
                width: 100%;
                justify-content: center;
            }

            .kitchen-grid {
                grid-template-columns: 1fr;
                padding: 15px;
                gap: 15px;
            }

            .brand-zone h1 {
                font-size: 1.2rem;
            }

            .btn-dash {
                font-size: 0.85rem;
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body class="kitchen-body">
    <header class="kitchen-header">
        <div class="brand-zone">
            <i class="fas fa-fire"></i>
            <h1>Kitchen Board</h1>
        </div>
        <div class="header-center">
            <div id="liveClock">00:00:00</div>
            <div class="status-badge">
                <i class="fas fa-circle-dot"></i> LIVE
            </div>
        </div>
        <div class="nav-zone">
            <a href="../admin/dashboard.php" class="btn-dash">
                <i class="fas fa-arrow-left"></i> Exit
            </a>
        </div>
    </header>

    <div class="kitchen-grid" id="orderGrid">
        <?php if (empty($live_orders)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h2>Kitchen Clear</h2>
                <p>No active orders at the moment</p>
            </div>
        <?php endif; ?>

        <?php foreach ($live_orders as $order):
            $wait_time = round((time() - strtotime($order['order_time'])) / 60); ?>
            <div class="order-card <?php echo $order['status']; ?>">
                <div class="status-strip"></div>
                <div class="card-body">
                    <div class="card-meta">
                        <span class="table-label">T-<?php echo $order['table_number']; ?></span>
                        <span class="timer">
                            <i class="fas fa-hourglass-half"></i>
                            <?php echo $wait_time; ?>m
                        </span>
                    </div>
                    <div class="items-area">
                        <?php
                        $items = explode(',', $order['items_summary']);
                        foreach ($items as $item): ?>
                            <div class="item-row">
                                <i class="fas fa-circle"></i>
                                <span><?php echo htmlspecialchars(trim($item)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-actions">
                    <?php if ($order['status'] == 'pending'): ?>
                        <a href="live-orders.php?update_id=<?php echo $order['id']; ?>&new_status=preparing" class="btn-action btn-start">
                            Start Prep
                        </a>
                    <?php else: ?>
                        <a href="live-orders.php?update_id=<?php echo $order['id']; ?>&new_status=served" class="btn-action btn-serve">
                            Ready
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
            document.getElementById('notificationSound').play().catch(e => console.log("Sound blocked"));
        }
        localStorage.setItem('kitchen_order_count', currentCount);

        // Auto-refresh every 5 seconds
        setInterval(() => location.reload(), 5000);
    </script>
</body>
</html>
