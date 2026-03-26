<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h2>--- Diagnostic Report ---</h2>";
echo "<b>Current Script Path:</b> " . __FILE__ . "<br>";
echo "<b>Requested URI:</b> " . $_SERVER['REQUEST_URI'] . "<br>";
echo "<b>Document Root:</b> " . $_SERVER['DOCUMENT_ROOT'] . "<br><br>";
$admin_dir = __DIR__ . '/admin';
if (is_dir($admin_dir)) {
    echo "<span style='color:green;'>✅ Found 'admin' folder.</span><br>";
    if (file_exists($admin_dir . '/logout.php')) {
        echo "<span style='color:green;'>✅ Found 'admin/logout.php'.</span><br>";
        echo "<b>Correct URL to logout:</b> http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/admin/logout.php<br>";
    } else {
        echo "<span style='color:red;'>❌ Missing 'logout.php' inside the admin folder.</span><br>";
    }
} else {
    echo "<span style='color:red;'>❌ Could not find 'admin' folder in this directory.</span><br>";
}
$config_file = __DIR__ . '/config/sessions.php';
if (file_exists($config_file)) {
    echo "<span style='color:green;'>✅ Found 'config/sessions.php'.</span><br>";
    require_once $config_file;
    echo "<b>Defined BASE_URL:</b> " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "<br>";
} else {
    echo "<span style='color:red;'>❌ Missing 'config/sessions.php'.</span><br>";
}
echo "<hr>";
echo "<a href='index.php'>Go to Index</a> | <a href='admin/logout.php'>Try Logout Link</a>";
?>
