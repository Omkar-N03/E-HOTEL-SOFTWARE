<?php

require_once '../config/db.php';
session_start();

if (!isset($_GET['id']) || !isset($_SESSION['customer_hotel_id'])) {
    header("Location: menu.php");
    exit();
}

$item_id = (int)$_GET['id'];
$hotel_id = $_SESSION['customer_hotel_id'];

try {
    $stmt = $pdo->prepare("
        SELECT m.*, h.currency 
        FROM menu_items m 
        JOIN hotels h ON m.hotel_id = h.id 
        WHERE m.id = ? AND m.hotel_id = ? AND m.is_available = 1
    ");
    $stmt->execute([$item_id, $hotel_id]);
    $item = $stmt->fetch();

    if (!$item) {
        die("<div style='text-align:center; padding:50px;'><h2>Item Not Found</h2><p>This dish is currently unavailable.</p><a href='menu.php'>Back to Menu</a></div>");
    }
} catch (PDOException $e) {
    die("Error fetching details: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($item['name']); ?> | Details</title>
    <link rel="stylesheet" href="../assets/css/customer-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --brand: #2ecc71; --dark: #2c3e50; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #fff; padding-bottom: 100px; }
        .item-hero { position: relative; width: 100%; height: 300px; background: #eee; }
        .item-hero img { width: 100%; height: 100%; object-fit: cover; }
        .back-circle { 
            position: absolute; top: 20px; left: 20px; 
            background: white; width: 40px; height: 40px; 
            border-radius: 50%; display: flex; align-items: center; 
            justify-content: center; color: var(--dark); 
            text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .details-content { padding: 25px; margin-top: -30px; background: white; border-radius: 30px 30px 0 0; position: relative; }
        .category-pill { background: #f0f2f5; color: #666; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; text-transform: uppercase; }
        .price-large { font-size: 1.8rem; font-weight: 800; color: var(--brand); margin: 10px 0; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 20px 0; }
        .stat-box { background: #f8f9fa; padding: 12px 5px; border-radius: 12px; text-align: center; }
        .stat-val { display: block; font-weight: bold; color: var(--dark); font-size: 1.1rem; }
        .stat-label { font-size: 0.7rem; color: #888; text-transform: uppercase; }
        .health-badge { 
            display: flex; align-items: center; gap: 10px; 
            background: #e8f8f0; color: #27ae60; 
            padding: 12px; border-radius: 12px; font-size: 0.9rem; margin-top: 15px;
        }
        .action-bar { 
            position: fixed; bottom: 0; left: 0; right: 0; 
            background: white; padding: 20px; display: flex; 
            gap: 15px; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); 
        }
        .qty-ctrl { 
            display: flex; align-items: center; background: #f0f2f5; 
            border-radius: 15px; padding: 5px;
        }
        .qty-ctrl button { border: none; background: none; padding: 10px 15px; font-size: 1.2rem; cursor: pointer; }
        .btn-add-main { 
            flex: 1; background: var(--brand); color: white; border: none; 
            border-radius: 15px; font-weight: bold; font-size: 1rem; cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="item-hero">
        <a href="menu.php" class="back-circle"><i class="fa fa-arrow-left"></i></a>
        <img src="../<?php echo $item['image_url'] ?: 'assets/img/placeholder-food.jpg'; ?>" alt="Dish">
    </div>
    <main class="details-content">
        <span class="category-pill"><?php echo htmlspecialchars($item['category']); ?></span>
        <h1 style="margin: 10px 0 5px 0;"><?php echo htmlspecialchars($item['name']); ?></h1>
        <div class="price-large"><?php echo $item['currency']; ?><?php echo number_format($item['price'], 2); ?></div>
        <section style="margin-top: 25px;">
            <h3 style="font-size: 1rem; margin-bottom: 15px;">Nutritional Breakdown</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-val"><?php echo $item['calories']; ?></span>
                    <span class="stat-label">Kcal</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?php echo $item['protein']; ?>g</span>
                    <span class="stat-label">Protein</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?php echo $item['carbs'] ?? '15'; ?>g</span>
                    <span class="stat-label">Carbs</span>
                </div>
                <div class="stat-box">
                    <span class="stat-val"><?php echo $item['fats'] ?? '10'; ?>g</span>
                    <span class="stat-label">Fats</span>
                </div>
            </div>
            <?php if($item['protein'] > 20): ?>
            <div class="health-badge">
                <i class="fa fa-fire-alt"></i>
                <strong>High Protein Choice:</strong> Great for muscle recovery!
            </div>
            <?php endif; ?>
        </section>
        <section style="margin-top: 25px;">
            <h3 style="font-size: 1rem;">Description</h3>
            <p style="color: #666; line-height: 1.6;">
                <?php echo htmlspecialchars($item['description'] ?: 'A delicious prepared dish using the finest ingredients available in the house.'); ?>
            </p>
        </section>
    </main>
    <div class="action-bar">
        <div class="qty-ctrl">
            <button onclick="updateQty(-1)"><i class="fa fa-minus"></i></button>
            <span id="qty-num" style="width: 30px; text-align: center; font-weight: bold;">1</span>
            <button onclick="updateQty(1)"><i class="fa fa-plus"></i></button>
        </div>
        <button class="btn-add-main" onclick="addWithQty()">
            Add • <span id="total-price"><?php echo $item['currency'] . number_format($item['price'], 2); ?></span>
        </button>
    </div>
    <script>
        let currentQty = 1;
        const basePrice = <?php echo $item['price']; ?>;
        const symbol = "<?php echo $item['currency']; ?>";

        function updateQty(val) {
            currentQty = Math.max(1, currentQty + val);
            document.getElementById('qty-num').innerText = currentQty;
            document.getElementById('total-price').innerText = symbol + (basePrice * currentQty).toFixed(2);
        }

        function addWithQty() {
            for(let i = 0; i < currentQty; i++) {
                if (typeof addToCart === "function") {
                    addToCart(
                        <?php echo $item['id']; ?>, 
                        '<?php echo addslashes($item['name']); ?>', 
                        <?php echo $item['price']; ?>, 
                        <?php echo $item['calories']; ?>, 
                        <?php echo $item['protein']; ?>
                    );
                }
            }
            window.location.href = 'menu.php?added=success';
        }
    </script>
    <script src="../assets/js/cart.js"></script>
</body>
</html>
