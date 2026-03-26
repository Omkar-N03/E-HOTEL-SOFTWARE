<?php
require_once 'config/config.php';
require_once 'config/db.php';

$msg = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hotel_name = trim($_POST['hotel_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    try {
        $stmt = $pdo->prepare("INSERT INTO hotels (hotel_name, email, password, status) VALUES (?, ?, ?, 'active')");
        if ($stmt->execute([$hotel_name, $email, $password])) {
            $msg = "Registration successful! <a href='login.php'>Click here to Login</a>";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "This email is already registered.";
        } else {
            $error = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Hotel | <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/auth-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f4f7f6; font-family:sans-serif; margin:0;">

    <div style="background:white; padding:40px; border-radius:10px; box-shadow:0 4px 15px rgba(0,0,0,0.1); width:350px;">
        <h2 style="text-align:center; margin-bottom:10px;">Register Hotel</h2>
        
        <?php if($msg): ?>
            <div style="background:#d1fae5; color:#065f46; padding:10px; border-radius:5px; margin-bottom:15px; text-align:center;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div style="background:#fee2e2; color:#b91c1c; padding:10px; border-radius:5px; margin-bottom:15px; text-align:center;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Hotel Name</label>
            <input type="text" name="hotel_name" required style="width:100%; padding:10px; margin:8px 0 15px 0; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;">
            
            <label>Email Address</label>
            <input type="email" name="email" required style="width:100%; padding:10px; margin:8px 0 15px 0; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;">
            
            <label>Password</label>
            <input type="password" name="password" required style="width:100%; padding:10px; margin:8px 0 20px 0; border:1px solid #ddd; border-radius:5px; box-sizing:border-box;">
            
            <button type="submit" style="width:100%; padding:12px; background:#2ecc71; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px; font-weight:bold;">Register Now</button>
        </form>
        
        <p style="text-align:center; margin-top:20px; font-size:14px;">
            <a href="login.php" style="color:#3498db; text-decoration:none;">Already have an account? Login</a>
        </p>
    </div>

</body>
</html>