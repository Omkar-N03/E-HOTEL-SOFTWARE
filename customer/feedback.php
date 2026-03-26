<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['customer_hotel_id'])) {
    die("<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h2>Experience Not Found</h2>
            <p>Please scan the QR code on your table to leave feedback for your meal.</p>
         </div>");
}

$hotel_id = $_SESSION['customer_hotel_id'];
$hotel_name = $_SESSION['hotel_name'] ?? 'the restaurant';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : ($_SESSION['last_order_id'] ?? 0);
$message = "";
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = (int)$_POST['rating'];
    $comments = strip_tags($_POST['comments']);
    $taste_score = isset($_POST['taste']) ? 1 : 0;
    $service_score = isset($_POST['service']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO feedback (hotel_id, order_id, rating, comments, taste_rating, service_rating, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$hotel_id, $order_id, $rating, $comments, $taste_score, $service_score]);
        $submitted = true;
        unset($_SESSION['last_order_id']);
    } catch (PDOException $e) {
        $message = "Error saving feedback. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback | <?php echo htmlspecialchars($hotel_name); ?></title>
    <link rel="stylesheet" href="../assets/css/customer-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --brand-color: #2ecc71; --star-color: #fdcb6e; }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .feedback-container { padding: 20px; max-width: 500px; margin: 0 auto; text-align: center; }
        .feedback-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; margin: 25px 0; }
        .star-rating input { display: none; }
        .star-rating label { font-size: 2.8rem; color: #dfe6e9; cursor: pointer; transition: 0.2s; }
        .star-rating input:checked ~ label, 
        .star-rating label:hover, 
        .star-rating label:hover ~ label { color: var(--star-color); }
        .chip-group { display: flex; justify-content: center; gap: 10px; margin-bottom: 25px; }
        .chip-group input { display: none; }
        .chip { padding: 12px 20px; border: 1px solid #ddd; border-radius: 30px; cursor: pointer; font-size: 0.9rem; transition: 0.3s; display: inline-block; }
        .chip-group input:checked + .chip { background: var(--brand-color); color: white; border-color: var(--brand-color); transform: translateY(-2px); }
        .form-control { width: 100%; border-radius: 12px; border: 1px solid #ddd; padding: 15px; font-family: inherit; resize: none; box-sizing: border-box; }
        .btn-submit { width: 100%; background: var(--brand-color); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 1.1rem; font-weight: bold; cursor: pointer; margin-top: 20px; box-shadow: 0 4px 12px rgba(46, 204, 113, 0.2); }
        .feedback-success i { font-size: 5rem; color: var(--brand-color); margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="feedback-container">
        <?php if ($submitted): ?>
            <div class="feedback-card feedback-success">
                <i class="fa fa-check-circle"></i>
                <h2>Feedback Received!</h2>
                <p>Thank you for dining at <strong><?php echo htmlspecialchars($hotel_name); ?></strong>. Your input helps us serve you better!</p>
                <br>
                <a href="menu.php" style="text-decoration: none; color: var(--brand-color); font-weight: bold;">Return to Menu</a>
            </div>
        <?php else: ?>
            <header style="margin-bottom: 30px;">
                <h1 style="margin: 0; color: #2d3436;">Rate Your Meal</h1>
                <p style="color: #636e72;">Table <?php echo $_SESSION['customer_table_no']; ?> • Order #<?php echo $order_id; ?></p>
            </header>
            <form method="POST" class="feedback-card">
                <input type="hidden" name="submit_feedback" value="1">
                <p style="font-weight: 600;">How was your overall experience?</p>
                <div class="star-rating">
                    <input type="radio" id="5-stars" name="rating" value="5" required /><label for="5-stars"><i class="fa fa-star"></i></label>
                    <input type="radio" id="4-stars" name="rating" value="4" /><label for="4-stars"><i class="fa fa-star"></i></label>
                    <input type="radio" id="3-stars" name="rating" value="3" /><label for="3-stars"><i class="fa fa-star"></i></label>
                    <input type="radio" id="2-stars" name="rating" value="2" /><label for="2-stars"><i class="fa fa-star"></i></label>
                    <input type="radio" id="1-star" name="rating" value="1" /><label for="1-star"><i class="fa fa-star"></i></label>
                </div>
                <p style="font-weight: 600;">What stood out today?</p>
                <div class="chip-group">
                    <label>
                        <input type="checkbox" name="taste">
                        <span class="chip"><i class="fa fa-utensils"></i> Delicious Taste</span>
                    </label>
                    <label>
                        <input type="checkbox" name="service">
                        <span class="chip"><i class="fa fa-bolt"></i> Fast Service</span>
                    </label>
                </div>
                <div class="form-group">
                    <textarea name="comments" class="form-control" rows="4" placeholder="Would you like to tell us more?"></textarea>
                </div>
                <button type="submit" class="btn-submit">Submit Feedback</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
