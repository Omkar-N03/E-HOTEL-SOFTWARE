<?php
require_once '../config/db.php';
session_start();

if (isset($_GET['hotel_id']) && isset($_GET['table_id'])) {
    $hid = (int)$_GET['hotel_id'];
    $tid = (int)$_GET['table_id'];
    $stmt = $pdo->prepare("SELECT t.table_number, h.hotel_name, h.currency 
                           FROM restaurant_tables t 
                           JOIN hotels h ON t.hotel_id = h.id 
                           WHERE h.id = ? AND t.id = ?");
    $stmt->execute([$hid, $tid]);
    $data = $stmt->fetch();
    if ($data) {
        $_SESSION['customer_hotel_id'] = $hid;
        $_SESSION['customer_table_id'] = $tid;
        $_SESSION['customer_table_no'] = $data['table_number'];
        $_SESSION['hotel_currency'] = $data['currency'];
        $_SESSION['hotel_name'] = $data['hotel_name'];
    }
}

if (!isset($_SESSION['customer_hotel_id'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h2>Access Denied</h2>
            <p>Please scan the QR code located on your dining table.</p>
         </div>");
}

$hotel_id = $_SESSION['customer_hotel_id'];
$table_no = $_SESSION['customer_table_no'];

try {
    $menuStmt = $pdo->prepare("SELECT category, id, name, price, calories, protein, image_url 
                                FROM menu_items 
                                WHERE hotel_id = ? AND is_available = 1 
                                ORDER BY category ASC");
    $menuStmt->execute([$hotel_id]);
    $menu_items = $menuStmt->fetchAll(PDO::FETCH_GROUP); 
} catch (PDOException $e) {
    die("Error loading menu.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($_SESSION['hotel_name']); ?> | Menu</title>
    <link rel="stylesheet" href="../assets/css/customer-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --brand: #2ecc71; --dark: #2c3e50; }
        body { background: #f9f9f9; padding-bottom: 100px; font-family: 'Segoe UI', sans-serif; margin: 0; }
        .sticky-header { 
            position: sticky; top: 0; background: white; z-index: 1000;
            padding: 15px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .nutrition-summary {
            background: var(--dark); color: white; margin: 15px; 
            padding: 15px; border-radius: 15px; display: flex; justify-content: space-around;
        }
        .menu-item {
            background: white; margin: 10px 15px; border-radius: 15px;
            display: flex; padding: 12px; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            text-decoration: none; color: inherit;
        }
        .img-box { width: 85px; height: 85px; border-radius: 12px; overflow: hidden; background: #eee; }
        .img-box img { width: 100%; height: 100%; object-fit: cover; }
        .item-info { flex: 1; }
        .price-tag { color: var(--brand); font-weight: bold; font-size: 1.1rem; }
        .cart-bar {
            position: fixed; bottom: 20px; left: 15px; right: 15px;
            background: var(--dark); color: white; padding: 15px 20px;
            border-radius: 50px; display: none; justify-content: space-between;
            align-items: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); z-index: 2000;
        }
    </style>
</head>
<body>
    <header class="sticky-header">
        <h2 style="margin:0; font-size: 1.2rem;"><?php echo htmlspecialchars($_SESSION['hotel_name']); ?></h2>
        <span style="font-size: 0.8rem; background: #eee; padding: 3px 10px; border-radius: 10px;">
            <i class="fa fa-chair"></i> Table <?php echo $table_no; ?>
        </span>
    </header>
    <div class="nutrition-summary">
        <div style="text-align:center">
            <small style="opacity:0.7; font-size: 0.6rem; display:block">TOTAL CALORIES</small>
            <span id="totalCalories" style="font-weight:bold; color: var(--brand);">0</span> <small>kcal</small>
        </div>
        <div style="text-align:center">
            <small style="opacity:0.7; font-size: 0.6rem; display:block">TOTAL PROTEIN</small>
            <span id="totalProtein" style="font-weight:bold; color: var(--brand);">0</span> <small>g</small>
        </div>
    </div>
    <?php foreach ($menu_items as $cat => $items): ?>
        <h3 style="margin: 20px 20px 10px; font-size: 0.9rem; color: #888; text-transform: uppercase;"><?php echo htmlspecialchars($cat); ?></h3>
        <?php foreach ($items as $i): ?>
            <div class="menu-item" onclick="location.href='item-details.php?id=<?php echo $i['id']; ?>'">
                <div class="img-box">
                    <img src="../<?php echo $i['image_url'] ?: 'assets/img/placeholder-food.jpg'; ?>" alt="dish">
                </div>
                <div class="item-info">
                    <h4 style="margin:0 0 5px; font-size: 1rem;"><?php echo htmlspecialchars($i['name']); ?></h4>
                    <div style="font-size: 0.7rem; color: #999; margin-bottom: 8px;">
                        <?php echo $i['calories']; ?> kcal | <?php echo $i['protein']; ?>g Protein
                    </div>
                    <div class="price-tag"><?php echo $_SESSION['hotel_currency']; ?><?php echo number_format($i['price'], 2); ?></div>
                </div>
                <button onclick="event.stopPropagation(); addToCart(<?php echo $i['id']; ?>, '<?php echo addslashes($i['name']); ?>', <?php echo $i['price']; ?>, <?php echo $i['calories']; ?>, <?php echo $i['protein']; ?>)" 
                        style="border:none; background: var(--brand); color:white; width:35px; height:35px; border-radius:10px; align-self: center;">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="cart-bar" id="cartFooter">
        <div>
            <span id="cartCount">0</span> Items
            <div id="cartTotal" style="font-weight:bold; font-size: 1.1rem;">0.00</div>
        </div>
        <a href="checkouts.php" style="color: white; text-decoration: none; background: var(--brand); padding: 10px 20px; border-radius: 25px; font-weight: bold;">
            Review Order <i class="fa fa-chevron-right"></i>
        </a>
    </div>
    <script src="../assets/js/cart.js"></script>
</body>
</html>
