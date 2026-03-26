<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config/db.php';
echo "<h2>Database Check-Up</h2>";
try {
    echo "✅ Database Connection: SUCCESS<br>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'hotels'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'hotels': FOUND<br>";
    } else {
        die("❌ Table 'hotels': NOT FOUND. Please re-import database.sql");
    }
    $columns = $pdo->query("SHOW COLUMNS FROM hotels LIKE 'status'")->fetch();
    if ($columns) {
        echo "✅ Column 'status': FOUND<br>";
    } else {
        echo "❌ Column 'status': MISSING<br>";
        echo "<i>Fix: Run ALTER TABLE hotels ADD status ENUM('active','suspended') DEFAULT 'active'; in phpMyAdmin</i><br>";
    }
    $user = $pdo->query("SELECT email FROM hotels WHERE email = 'admin@test.com'")->fetch();
    if ($user) {
        echo "✅ Test User 'admin@test.com': FOUND<br>";
    } else {
        echo "❌ Test User 'admin@test.com': NOT FOUND<br>";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
