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
$hotel_name = $_SESSION['hotel_name'] ?? 'Restaurant';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Review Order | <?php echo htmlspecialchars($hotel_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #10b981;
            --dark: #0f172a;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --radius: 20px;
        }

        body { 
            background: var(--bg); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            margin: 0; 
            padding: 20px; 
            color: var(--dark);
        }

        .checkout-container { max-width: 550px; margin: 0 auto; }

        /* Stepper UI */
        .stepper { display: flex; justify-content: space-between; margin-bottom: 30px; padding: 0 20px; position: relative; }
        .stepper::before { content: ''; position: absolute; top: 15px; left: 10%; right: 10%; height: 2px; background: var(--border); z-index: 1; }
        .step { width: 30px; height: 30px; background: var(--surface); border: 2px solid var(--border); border-radius: 50%; z-index: 2; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; color: #94a3b8; }
        .step.active { border-color: var(--primary); background: var(--primary); color: white; }
        .step.completed { border-color: var(--primary); color: var(--primary); }

        .checkout-card { 
            background: var(--surface); 
            border-radius: var(--radius); 
            padding: 24px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
            border: 1px solid rgba(0,0,0,0.05);
        }

        .header-section { border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 15px; }
        .header-section h2 { margin: 0; font-size: 1.4rem; font-weight: 700; }
        .table-badge { display: inline-block; background: #f1f5f9; padding: 4px 12px; border-radius: 8px; font-size: 0.85rem; margin-top: 8px; font-weight: 600; }

        /* Item UI */
        .order-item { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            padding: 16px 0; 
            border-bottom: 1px solid #f1f5f9; 
        }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; font-size: 0.95rem; display: block; }
        .item-price-each { font-size: 0.8rem; color: #64748b; }

        .qty-controls { 
            display: flex; 
            align-items: center; 
            background: #f8fafc; 
            border-radius: 10px; 
            padding: 4px; 
            gap: 10px;
        }
        .btn-qty { border: none; background: white; width: 24px; height: 24px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); cursor: pointer; color: var(--primary); font-weight: bold; }
        
        /* Summary UI */
        .bill-summary { background: #f8fafc; border-radius: 15px; padding: 15px; margin-top: 20px; }
        .summary-line { display: flex; justify-content: space-between; margin: 8px 0; font-size: 0.9rem; color: #64748b; }
        .summary-total { margin-top: 10px; padding-top: 10px; border-top: 1px dashed var(--border); font-weight: 700; font-size: 1.1rem; color: var(--dark); }

        .btn-confirm { 
            width: 100%; 
            background: var(--primary); 
            color: white; 
            border: none; 
            padding: 18px; 
            border-radius: 16px; 
            font-size: 1rem; 
            font-weight: 700; 
            margin-top: 25px; 
            cursor: pointer; 
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-confirm:disabled { background: #cbd5e1; cursor: not-allowed; }
        .btn-confirm:active { transform: scale(0.98); }

        .empty-state { text-align: center; padding: 40px 0; }
        .empty-state i { font-size: 3rem; color: #e2e8f0; margin-bottom: 15px; }
    </style>
</head>
<body>

    <div class="checkout-container">
        <div class="stepper">
            <div class="step completed"><i class="fa fa-check"></i></div>
            <div class="step active">2</div>
            <div class="step">3</div>
        </div>

        <div class="checkout-card">
            <div class="header-section">
                <h2>Review Your Order</h2>
                <div class="table-badge"><i class="fa fa-chair"></i> Table <?php echo htmlspecialchars($table_no); ?></div>
            </div>

            <div id="checkout-items-list">
                </div>

            <div id="bill-container">
                <div class="bill-summary">
                    <div class="summary-line">
                        <span>Items Subtotal</span>
                        <span id="subtotal">0.00</span>
                    </div>
                    <div class="summary-line">
                        <span>Service Charge (0%)</span>
                        <span><?php echo $currency; ?>0.00</span>
                    </div>
                    <div class="summary-line summary-total">
                        <span>Grand Total</span>
                        <span id="checkout-total">0.00</span>
                    </div>
                </div>

                <form action="../api/place-order.php" method="POST" id="orderForm">
                    <input type="hidden" name="hotel_id" value="<?php echo $hotel_id; ?>">
                    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                    <input type="hidden" name="cart_data" id="cart_data_input">
                    
                    <button type="submit" class="btn-confirm" id="submitBtn">
                        Confirm & Send to Kitchen <i class="fa fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <p style="text-align: center; color: #94a3b8; font-size: 0.8rem; margin-top: 20px;">
            <i class="fa fa-shield-alt"></i> Secure checkout for <?php echo htmlspecialchars($hotel_name); ?>
        </p>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('restaurant_cart')) || [];
        const currency = "<?php echo $currency; ?>";

        function renderCart() {
            const listContainer = document.getElementById('checkout-items-list');
            const billContainer = document.getElementById('bill-container');
            
            if (cart.length === 0) {
                listContainer.innerHTML = `
                    <div class="empty-state">
                        <i class="fa fa-shopping-cart"></i>
                        <p>Your cart is empty!</p>
                        <a href="menu.php" style="color:var(--primary); text-decoration:none; font-weight:bold;">Browse Menu</a>
                    </div>`;
                billContainer.style.display = 'none';
                return;
            }

            let total = 0;
            listContainer.innerHTML = '';

            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                
                listContainer.innerHTML += `
                    <div class="order-item">
                        <div class="item-info">
                            <span class="item-name">${item.name}</span>
                            <span class="item-price-each">${currency}${item.price.toFixed(2)}</span>
                        </div>
                        <div class="qty-controls">
                            <button class="btn-qty" onclick="updateQty(${index}, -1)">-</button>
                            <span style="font-weight:600; font-size:0.9rem;">${item.qty}</span>
                            <button class="btn-qty" onclick="updateQty(${index}, 1)">+</button>
                        </div>
                        <div style="font-weight:600; min-width:60px; text-align:right;">
                            ${currency}${itemTotal.toFixed(2)}
                        </div>
                    </div>
                `;
            });

            document.getElementById('subtotal').innerText = currency + total.toFixed(2);
            document.getElementById('checkout-total').innerText = currency + total.toFixed(2);
            document.getElementById('cart_data_input').value = JSON.stringify(cart);
        }

        function updateQty(index, change) {
            cart[index].qty += change;
            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            }
            localStorage.setItem('restaurant_cart', JSON.stringify(cart));
            renderCart();
        }

        document.getElementById('orderForm').onsubmit = function() {
            // Visual feedback
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-circle-notch fa-spin"></i> Placing Order...';
            
            // Clear cart after a slight delay to ensure form submits
            setTimeout(() => { localStorage.removeItem('restaurant_cart'); }, 500);
            return true;
        };

        
        renderCart();
    </script>
</body>
</html>