<?php
require_once '../config/db.php';
session_start();

// QR Entry Logic (Kept your original logic)
if (isset($_GET['hotel_id']) && isset($_GET['table_id'])) {
    $hid = (int)$_GET['hotel_id'];
    $tid = (int)$_GET['table_id'];

    $stmt = $pdo->prepare("
        SELECT t.table_number, h.hotel_name, h.currency 
        FROM restaurant_tables t 
        JOIN hotels h ON t.hotel_id = h.id 
        WHERE h.id = ? AND t.id = ?
    ");
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
            <p>Please scan the QR code on your table.</p>
        </div>");
}

$hotel_id = $_SESSION['customer_hotel_id'];
$table_no = $_SESSION['customer_table_no'];
$currency = $_SESSION['hotel_currency'];

try {
    $menuStmt = $pdo->prepare("
        SELECT category, id, name, price, calories, protein, image_url, description 
        FROM menu_items 
        WHERE hotel_id = ? AND is_available = 1 
        ORDER BY category ASC
    ");
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
    <title><?php echo $_SESSION['hotel_name']; ?> | Menu</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #10b981;
            --dark: #0f172a;
            --slate-500: #64748b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --container-max: 1100px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            padding-bottom: 120px;
            color: var(--dark);
        }

        /* --- RESPONSIVE CONTAINER --- */
        .container {
            width: 100%;
            max-width: var(--container-max);
            margin: 0 auto;
            padding: 0 15px;
        }

        /* --- HEADER --- */
        .header {
            position: sticky;
            top: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            z-index: 1000;
        }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .header-top h2 { margin: 0; font-size: 1.25rem; font-weight: 700; }
        
        .table-pill {
            background: var(--dark);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .search-wrap { position: relative; width: 100%; }
        .search-wrap input {
            width: 100%;
            padding: 12px 12px 12px 45px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .search-wrap input:focus { outline: none; border-color: var(--primary); background: white; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        .search-wrap i { position: absolute; left: 16px; top: 15px; color: var(--slate-500); }

        /* --- CATEGORY NAV --- */
        .category-nav {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            margin-top: 15px;
            padding: 5px 0;
            scrollbar-width: none;
        }
        .category-nav::-webkit-scrollbar { display: none; }
        
        .cat-chip {
            padding: 10px 18px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            cursor: pointer;
            white-space: nowrap;
            font-size: 0.85rem;
            font-weight: 600;
            transition: 0.2s;
        }
        .cat-chip.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* --- MENU GRID --- */
        .items-grid {
            display: grid;
            grid-template-columns: 1fr; /* Mobile default */
            gap: 15px;
            margin-top: 10px;
        }

        /* Responsive Breakpoints */
        @media (min-width: 768px) {
            .items-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .items-grid { grid-template-columns: repeat(3, 1fr); }
        }

        .menu-item {
            display: flex;
            background: white;
            padding: 12px;
            border-radius: 18px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
            transition: transform 0.2s;
            border: 1px solid transparent;
        }
        .menu-item:hover { border-color: #e2e8f0; transform: translateY(-2px); }

        .menu-item img {
            width: 100px;
            height: 100px;
            border-radius: 14px;
            object-fit: cover;
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
            margin-left: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-info h4 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--dark); line-height: 1.3; }
        .item-price { font-weight: 700; color: var(--primary); margin: 4px 0; font-size: 1.1rem; }
        
        .nutrition-tags { display: flex; gap: 8px; font-size: 0.7rem; color: var(--slate-500); margin-bottom: 8px; }
        .nutrition-tags span i { color: #f97316; margin-right: 2px; }

        .btn-add {
            background: var(--primary);
            color: white;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            align-self: flex-end;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }

        /* --- CART FOOTER --- */
        .cart-footer {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 30px);
            max-width: 500px;
            background: var(--dark);
            color: white;
            padding: 16px 24px;
            border-radius: 24px;
            display: none;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);
            z-index: 2000;
        }

        .btn-checkout {
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>

<body>

<header class="header">
    <div class="container">
        <div class="header-top">
            <h2><?php echo $_SESSION['hotel_name']; ?></h2>
            <div class="table-pill">T-<?php echo $table_no; ?></div>
        </div>

        <div class="search-wrap">
            <i class="fa fa-search"></i>
            <input type="text" id="menuSearch" onkeyup="filterMenu()" placeholder="Search delicious food...">
        </div>

        <div class="category-nav">
            <div class="cat-chip active" onclick="filterCategory('all', this)">All</div>
            <?php foreach(array_keys($menu_items) as $cat): ?>
                <div class="cat-chip" onclick="filterCategory('<?php echo $cat; ?>', this)">
                    <?php echo $cat; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</header>

<main class="container" id="menu-container">
    <?php foreach ($menu_items as $cat => $items): ?>
        <section class="category-block" data-category="<?php echo $cat; ?>">
            <h3 style="margin: 25px 0 15px; font-size: 1.1rem; color: var(--slate-500); text-transform: uppercase; letter-spacing: 1px;">
                <?php echo $cat; ?>
            </h3>

            <div class="items-grid">
                <?php foreach ($items as $i): ?>
                    <div class="menu-item item-logic" data-name="<?php echo strtolower($i['name']); ?>">
                        <img src="../<?php echo $i['image_url'] ?: 'assets/img/placeholder.jpg'; ?>" alt="<?php echo $i['name']; ?>">

                        <div class="item-info">
                            <div>
                                <h4><?php echo $i['name']; ?></h4>
                                <div class="nutrition-tags">
                                    <span><i class="fa fa-fire"></i> <?php echo $i['calories']; ?> kcal</span>
                                    <span><i class="fa fa-leaf"></i> <?php echo $i['protein']; ?>g Prot.</span>
                                </div>
                                <p class="item-price"><?php echo $currency . number_format($i['price'], 2); ?></p>
                            </div>

                            <button class="btn-add"
                                onclick="addToCart(<?php echo $i['id']; ?>,'<?php echo addslashes($i['name']); ?>',<?php echo $i['price']; ?>,<?php echo $i['calories']; ?>,<?php echo $i['protein']; ?>)">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</main>

<div class="cart-footer" id="cartFooter">
    <div class="cart-details">
        <h5 id="cartCount" style="margin:0; font-size:0.8rem; opacity:0.7;">0 Items</h5>
        <p style="margin:0; font-size:1.2rem; font-weight:700;">
            <?php echo $currency; ?><span id="cartTotal">0.00</span>
        </p>
    </div>
    <a href="checkouts.php" class="btn-checkout">
        View Order <i class="fa fa-arrow-right"></i>
    </a>
</div>

<script>
    function filterMenu() {
        let input = document.getElementById('menuSearch').value.toLowerCase();
        document.querySelectorAll('.item-logic').forEach(item => {
            let name = item.getAttribute('data-name');
            item.style.display = name.includes(input) ? "flex" : "none";
        });
    }

    function filterCategory(cat, el) {
        document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        document.querySelectorAll('.category-block').forEach(block => {
            block.style.display = (cat === 'all' || block.getAttribute('data-category') === cat) ? 'block' : 'none';
        });
    }
</script>

<script src="../assets/js/cart.js"></script>

</body>
</html>