<?php
require_once '../config/db.php';
session_start();

// Session and Security Logic
if (isset($_GET['hotel_id']) && isset($_GET['table_id'])) {
    $hid = (int)$_GET['hotel_id'];
    $tid = (int)$_GET['table_id'];
    $stmt = $pdo->prepare("SELECT t.table_number, h.hotel_name, h.currency FROM restaurant_tables t JOIN hotels h ON t.hotel_id = h.id WHERE h.id = ? AND t.id = ?");
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
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>Access Denied</h2><p>Please scan the QR code on your table.</p></div>");
}

$hotel_id = $_SESSION['customer_hotel_id'];
$table_no = $_SESSION['customer_table_no'];
$currency = $_SESSION['hotel_currency'];

try {
    $menuStmt = $pdo->prepare("SELECT category, id, name, price, calories, protein, image_url, description FROM menu_items WHERE hotel_id = ? AND is_available = 1 ORDER BY category ASC");
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --dark: #0f172a;
            --slate-500: #64748b;
            --bg: #f8fafc;
            --surface: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.05);
            --radius: 18px;
        }

        body { 
            background: var(--bg); 
            color: var(--dark);
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            padding-bottom: 100px;
        }

        /* Responsive Wrapper */
        .container { max-width: 1200px; margin: 0 auto; }

        /* Frosted Header */
        .header {
            position: sticky; top: 0; 
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            z-index: 1000;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .table-pill { background: var(--dark); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }

        /* Search & Navigation */
        .search-wrap { margin-bottom: 15px; position: relative; max-width: 600px; margin-inline: auto; }
        #menuSearch { width: 100%; padding: 12px 15px 12px 45px; border-radius: 14px; border: 1px solid #e2e8f0; background: #f1f5f9; outline: none; box-sizing: border-box; }
        .search-wrap i { position: absolute; left: 16px; top: 15px; color: var(--slate-500); }

        .category-nav { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; }
        .cat-chip { white-space: nowrap; padding: 8px 16px; background: #eee; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: var(--slate-500); text-decoration: none; }
        .cat-chip.active { background: var(--primary); color: white; }

        /* Nutrition Bar - Responsive */
        .nutrition-bar {
            background: var(--dark); color: white; margin: 15px; 
            padding: 18px; border-radius: var(--radius); display: flex; 
            justify-content: space-around; max-width: 500px; margin-inline: auto;
        }

        /* Menu Grid */
        #menu-container { padding: 10px; }
        
        @media (min-width: 768px) {
            .category-block {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
                margin-bottom: 40px;
            }
            .category-title { grid-column: 1 / -1; }
        }

        .menu-item {
            background: var(--surface); border-radius: var(--radius);
            display: flex; padding: 14px; gap: 14px; box-shadow: var(--shadow);
            margin-bottom: 15px; transition: 0.3s;
        }
        .menu-item:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }

        .img-container { width: 100px; height: 100px; border-radius: 14px; overflow: hidden; flex-shrink: 0; }
        .img-container img { width: 100%; height: 100%; object-fit: cover; }

        .btn-add {
            background: var(--primary); color: white; border: none;
            width: 36px; height: 36px; border-radius: 12px; cursor: pointer;
        }

        /* Floating Cart - Responsive */
        .cart-float {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 500px;
            background: var(--dark); color: white; padding: 16px 24px;
            border-radius: 24px; display: none; justify-content: space-between;
            align-items: center; z-index: 2000;
        }

        /* Success Toast */
        .success-toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
            background: var(--primary); color: white; padding: 12px 24px;
            border-radius: 50px; font-weight: bold; z-index: 3000;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }
        @keyframes slideDown { from { top: -50px; opacity: 0; } to { top: 20px; opacity: 1; } }
    </style>
</head>
<body>

    <header class="header">
        <div class="container">
            <div class="header-top">
                <h2><?php echo htmlspecialchars($_SESSION['hotel_name']); ?></h2>
                <div class="table-pill">T-<?php echo $table_no; ?></div>
            </div>
            
            <div class="search-wrap">
                <i class="fa fa-search"></i>
                <input type="text" id="menuSearch" onkeyup="filterMenu()" placeholder="Search delicious food...">
            </div>

            <div class="category-nav">
                <a href="#" class="cat-chip active" onclick="filterCategory('all', this)">All</a>
                <?php foreach(array_keys($menu_items) as $cat): ?>
                    <a href="#cat-<?php echo md5($cat); ?>" class="cat-chip" onclick="filterCategory('<?php echo htmlspecialchars($cat); ?>', this)">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="nutrition-bar">
            <div class="stat-item">
                <small>Calories</small><span id="totalCalories">0</span>
            </div>
            <div class="stat-item">
                <small>Protein</small><span id="totalProtein">0g</span>
            </div>
        </div>

        <div id="menu-container">
            <?php foreach ($menu_items as $cat => $items): ?>
                <div class="category-block" data-category="<?php echo htmlspecialchars($cat); ?>" id="cat-<?php echo md5($cat); ?>">
                    <h3 class="category-title" style="margin: 25px 10px 10px; font-size: 0.85rem; color: var(--slate-500); text-transform: uppercase;"><?php echo htmlspecialchars($cat); ?></h3>
                    
                    <?php foreach ($items as $i): ?>
                        <div class="menu-item item-logic" data-name="<?php echo strtolower(htmlspecialchars($i['name'])); ?>">
                            <div class="img-container">
                                <img src="../<?php echo $i['image_url'] ?: 'assets/img/placeholder.jpg'; ?>" alt="dish">
                            </div>
                            <div class="item-details" style="flex:1;">
                                <h4 style="margin:0; font-size:1rem;"><?php echo htmlspecialchars($i['name']); ?></h4>
                                <div style="display:flex; gap:5px; margin:5px 0;">
                                    <span style="font-size:0.7rem; background:#f1f5f9; padding:2px 6px; border-radius:4px;"><?php echo $i['calories']; ?> kcal</span>
                                </div>
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <strong style="font-size:1.1rem;"><?php echo $currency; ?><?php echo number_format($i['price'], 2); ?></strong>
                                    <button class="btn-add" onclick="addToCart(<?php echo $i['id']; ?>, '<?php echo addslashes($i['name']); ?>', <?php echo $i['price']; ?>, <?php echo $i['calories']; ?>, <?php echo $i['protein']; ?>)">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cart-float" id="cartFooter">
        <div class="cart-info">
            <h5 id="cartTotal" style="margin:0;">0.00</h5>
            <span id="cartCount" style="font-size:0.75rem; opacity:0.8;">0 items added</span>
        </div>
        <a href="checkouts.php" class="btn-checkout" style="background:var(--primary); color:white; text-decoration:none; padding:10px 20px; border-radius:12px; font-weight:700;">View Cart</a>
    </div>

    <script>
        // Check for Order Success
        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('status') === 'success') {
                localStorage.removeItem('restaurant_cart');
                // Optional: trigger your cart.js update function here
                if(typeof updateCartUI === "function") updateCartUI();

                const toast = document.createElement('div');
                toast.className = 'success-toast';
                toast.innerHTML = '<i class="fa fa-check-circle"></i> Order Confirmed! Sent to kitchen.';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                    window.history.replaceState({}, document.title, window.location.pathname);
                    location.reload(); // Refresh to ensure UI is zeroed
                }, 2500);
            }
        };

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