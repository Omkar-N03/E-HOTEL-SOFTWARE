<?php
session_start();

$order_id = $_GET['order_id'] ?? null;
$table_no = $_SESSION['customer_table_no'] ?? 'N/A';
$hotel_name = $_SESSION['hotel_name'] ?? 'Restaurant';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | <?php echo htmlspecialchars($hotel_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #10b981;
            --primary-light: #d1fae5;
            --dark: #0f172a;
            --slate-500: #64748b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --radius: 24px;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .success-card {
            background: var(--surface);
            padding: 40px 30px;
            border-radius: var(--radius);
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            animation: cardAppear 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes cardAppear {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Success Animated Icon */
        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 32px;
            position: relative;
        }

        .icon-box::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid var(--primary);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.5; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        h2 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 10px;
        }

        p {
            color: var(--slate-500);
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .info-pill {
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            color: var(--dark);
            display: inline-block;
            margin-bottom: 20px;
        }

        /* Action Buttons */
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            padding: 16px;
            border-radius: 16px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-track {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
        }

        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-menu {
            background: var(--surface);
            color: var(--dark);
            border: 2px solid #e2e8f0;
        }

        .btn-menu:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }

        /* Responsive adjustments */
        @media (min-width: 768px) {
            .success-card {
                padding: 50px 40px;
            }
        }
    </style>
</head>

<body>

<div class="success-card">
    <div class="icon-box">
        <i class="fa fa-check"></i>
    </div>
    
    <h2>Order Successfully Placed!</h2>
    <p>We've received your order. Sit back and relax while our chefs prepare your meal.</p>

    <div class="info-pill">
        <i class="fa fa-chair" style="margin-right: 6px; color: var(--slate-500);"></i> 
        Table No: <?php echo htmlspecialchars($table_no); ?>
    </div>

    <div class="btn-group">
        <a href="tracks-order.php?order_id=<?php echo $order_id; ?>" class="btn btn-track">
            <i class="fa fa-truck-fast"></i> Track Order Status
        </a>
        
        <a href="menu.php" class="btn btn-menu">
            <i class="fa fa-plus"></i> Order Something More
        </a>
    </div>

    <div style="margin-top: 30px; font-size: 0.8rem; color: #94a3b8;">
        Order ID: #<?php echo $order_id ?: '0000'; ?>
    </div>
</div>

</body>
</html>