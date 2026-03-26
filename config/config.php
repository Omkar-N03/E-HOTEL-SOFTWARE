<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$project_folder = "/Restraunt System/"; 

define('BASE_URL', $protocol . $host . $project_folder);
define('APP_NAME', 'Smart Menu SaaS');
define('APP_VERSION', '1.0.0');
date_default_timezone_set('Asia/Kolkata'); 
define('DEFAULT_CURRENCY', '₹');
define('DEFAULT_TAX_RATE', 5.00); 

define('LOGO_UPLOAD_PATH', BASE_URL . 'assets/img/logos/');
define('MENU_UPLOAD_PATH', BASE_URL . 'assets/img/menu/');

error_reporting(E_ALL);
ini_set('display_errors', 1);

function formatPrice($amount) {
    return DEFAULT_CURRENCY . number_get_formatted($amount, 2);
}

function number_get_formatted($num, $decimals = 2) {
    return number_format((float)$num, $decimals, '.', ',');
}
?>