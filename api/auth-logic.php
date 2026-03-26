<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['register_hotel'])) {
    $hotel_name = trim($_POST['hotel_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($hotel_name) || empty($email) || empty($password)) {
        header("Location: ../register.php?error=empty_fields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../register.php?error=invalid_email");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=password_mismatch");
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $checkEmail = $pdo->prepare("SELECT id FROM hotels WHERE email = ?");
        $checkEmail->execute([$email]);

        if ($checkEmail->rowCount() > 0) {
            header("Location: ../register.php?error=email_exists");
            exit();
        }

        $stmt = $pdo->prepare("
            INSERT INTO hotels 
            (hotel_name, email, password, status, tax_percent, currency, created_at) 
            VALUES (?, ?, ?, 'active', 5.0, 'INR', NOW())
        ");

        $result = $stmt->execute([$hotel_name, $email, $hashed_password]);

        if ($result) {
            header("Location: ../login.php?success=account_created");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../register.php?error=server_error");
        exit();
    }
}

if (isset($_POST['login_user'])) {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: ../login.php?error=empty_fields");
        exit();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, hotel_name, password, status 
            FROM hotels 
            WHERE email = ?
        ");
        $stmt->execute([$email]);

        $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($hotel && password_verify($password, $hotel['password'])) {
            if ($hotel['status'] !== 'active') {
                header("Location: ../login.php?error=account_inactive");
                exit();
            }

            session_regenerate_id(true);

            $_SESSION['hotel_id'] = $hotel['id'];
            $_SESSION['hotel_name'] = $hotel['hotel_name'];
            $_SESSION['logged_in'] = true;

            header("Location: ../admin/dashboard.php");
            exit();
        } else {
            header("Location: ../login.php?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../login.php?error=server_error");
        exit();
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: ../login.php?message=logged_out");
    exit();
}
