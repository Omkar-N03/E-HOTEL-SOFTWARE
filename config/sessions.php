<?php
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 2));
    $basePath = rtrim($scriptName, '/') . '/';
    define('BASE_URL', $basePath);
}
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
function protect_page() {
    if (!isset($_SESSION['hotel_id'])) {
        header("Location: " . BASE_URL . "index.php?error=unauthorized");
        exit();
    }
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}
function destroy_session() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
function get_hotel_id() { return $_SESSION['hotel_id'] ?? null; }
function is_logged_in() { return isset($_SESSION['hotel_id']); }
