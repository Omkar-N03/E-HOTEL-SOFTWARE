<?php
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

if (function_exists('destroy_session')) {
    destroy_session();
} else {
    session_destroy();
}

header("Location: ../index.php?msg=logged_out");
exit();