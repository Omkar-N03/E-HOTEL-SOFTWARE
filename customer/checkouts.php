<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['customer_hotel_id'])) {
    header("Location: ../index.php?error=session_expired");
    exit();
}

$hotel_id = $_SESSION['customer_hotel_id'];
$table_id = $_SESSION['customer_table_id'];
$table_no = $_SESSION['customer_table_no'] ?? 'N/A';
$currency = $_SESSION['hotel_currency'] ?? '₹';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Order | Table <?php echo $table_no; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .checkout-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 500px; margin: auto; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .total-section { margin-top: 20px; padding-top: 15px; border-top: 2px solid #f0f0f0; font-weight: bold; font-size: 1.2rem; }
        .btn-confirm { width: 100%; background: #2ecc71; color: white; border: none; padding: 15px; border-radius: 10px; font-size: 1rem; font-weight: bold; margin-top: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="checkout-card">
        <h2 style="margin-top:0;"><i class="fa fa-shopping-basket"></i> Your Order</h2>
        <p style="color: #666;">Table: <strong><?php echo htmlspecialchars($table_no); ?></strong></p>
        <div id="checkout-items-list"></div>
        <div class="total-section">
            <span>Total Amount:</span>
            <span id="checkout-total" style="float:right; color: #2ecc71;"></span>
        </div>
        <form action="../api/place-order.php" method="POST" id="orderForm">
            <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
            <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
            <input type="hidden" name="cart_data" id="cart_data_input">
            <button type="submit" class="btn-confirm" onclick="clearLocalCart()">Confirm & Send to Kitchen</button>
        </form>
    </div>
    <script>
        const cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
        const listContainer = document.getElementById('checkout-items-list');
        let total = 0;

        if(cart.length === 0) {
            listContainer.innerHTML = "<p>Your cart is empty!</p>";
            document.querySelector('.btn-confirm').disabled = true;
        } else {
            cart.forEach(item => {
                total += (item.price * item.qty);
                listContainer.innerHTML += `
                    <div class="order-item">
                        <span>${item.qty}x ${item.name}</span>
                        <span><?php echo $currency; ?>${(item.price * item.qty).toFixed(2)}</span>
                    </div>
                `;
            });
            document.getElementById('checkout-total').innerText = "<?php echo $currency; ?>" + total.toFixed(2);
            document.getElementById('cart_data_input').value = JSON.stringify(cart);
        }

        function clearLocalCart() {
            setTimeout(() => { localStorage.removeItem('restaurant_cart'); }, 500);
        }
    </script>
</body>
</html>
