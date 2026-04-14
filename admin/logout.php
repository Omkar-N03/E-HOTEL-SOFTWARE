<?php
require_once __DIR__ . '/../config/db.php'; 
require_once __DIR__ . '/../config/sessions.php'; 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
echo "<script>
    localStorage.removeItem('restaurant_cart');
    window.location.href = '../index.php?msg=logged_out';
</script>";
exit();