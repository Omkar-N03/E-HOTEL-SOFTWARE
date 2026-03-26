<?php
require_once 'config/db.php';
require_once 'config/sessions.php';
require_once 'config/constants.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'super_admin') {
        header("Location: super-admin/index.php");
        exit();
    } elseif ($_SESSION['role'] === 'hotel_admin') {
        header("Location: admin/dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'kitchen_staff') {
        header("Location: kitchen/live-orders.php");
        exit();
    }
}

if (isset($_GET['hotel_id']) && isset($_GET['table'])) {
    $_SESSION['customer_hotel_id'] = $_GET['hotel_id'];
    $_SESSION['customer_table_no'] = $_GET['table'];
    header("Location: customer/menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | Restaurant Management System</title>
    <link rel="stylesheet" href="assets/css/auth-style.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .landing-container { text-align: center; background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-width: 500px; }
        .logo { width: 120px; margin-bottom: 1.5rem; }
        h1 { color: #2d3436; font-size: 1.8rem; margin-bottom: 1rem; }
        p { color: #636e72; line-height: 1.6; margin-bottom: 2rem; }
        .btn-group { display: flex; flex-direction: column; gap: 10px; }
        .btn { padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.3s; }
        .btn-primary { background-color: #0984e3; color: white; }
        .btn-primary:hover { background-color: #74b9ff; }
        .btn-secondary { background-color: #636e72; color: white; }
        .footer-note { margin-top: 2rem; font-size: 0.85rem; color: #b2bec3; }
    </style>
</head>
<body>
    <div class="landing-container">
        <img src="assets/img/logos/main-logo.png" alt="System Logo" class="logo" onerror="this.src='https://via.placeholder.com/120x120?text=RMS'">
        <h1>Smart Dining Solutions</h1>
        <p>A unified platform for QR-based ordering, nutritional tracking, and kitchen management.</p>
        <div class="btn-group">
            <a href="admin/login.php" class="btn btn-primary">Hotel Management Login</a>
            <a href="admin/register.php" class="btn btn-secondary">Register Your Restaurant</a>
        </div>
        <div class="footer-note">
            <p>Are you a diner? Scan the QR code on your table to view the menu.</p>
        </div>
    </div>
</body>
</html>
