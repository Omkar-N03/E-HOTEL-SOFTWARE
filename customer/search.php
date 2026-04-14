<?php
require_once '../config/db.php';
session_start();

$q = $_GET['q'] ?? '';
$hotel_id = $_SESSION['customer_hotel_id'];
$currency = $_SESSION['hotel_currency'] ?? '$';

try {
    $sql = "SELECT * FROM menu_items WHERE hotel_id = ? AND is_available = 1 AND name LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hotel_id, "%$q%"]);
    $items = $stmt->fetchAll();

    if ($items) {
        foreach ($items as $i) {
            ?>
            <div class="menu-item" onclick="location.href='item-details.php?id=<?php echo $i['id']; ?>'">
                <div class="img-box"><img src="../<?php echo $i['image_url'] ?: 'assets/img/placeholder.jpg'; ?>"></div>
                <div style="flex:1">
                    <h4 style="margin:0;"><?php echo htmlspecialchars($i['name']); ?></h4>
                    <small><?php echo $i['calories']; ?>kcal | <?php echo $i['protein']; ?>g</small><br>
                    <span class="price-tag"><?php echo $currency . number_format($i['price'], 2); ?></span>
                </div>
                <button onclick="event.stopPropagation(); addToCart(<?php echo $i['id']; ?>, '<?php echo addslashes($i['name']); ?>', <?php echo $i['price']; ?>, <?php echo $i['calories']; ?>, <?php echo $i['protein']; ?>)" style="background:#2ecc71; border:none; color:white; border-radius:8px; width:35px; height:35px;"><i class="fa fa-plus"></i></button>
            </div>
            <?php
        }
    } else {
        echo "<p style='text-align:center; padding:20px;'>No items found for '$q'</p>";
    }
} catch (PDOException $e) {
    echo "Search error.";
}