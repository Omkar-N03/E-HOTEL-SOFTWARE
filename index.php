<?php
require_once 'config/config.php';
require_once 'config/db.php';
require_once 'config/sessions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: admin/dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, hotel_name, password, status FROM hotels WHERE email = ?");
            $stmt->execute([$email]);
            $hotel = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($hotel && password_verify($password, $hotel['password'])) {
                if ($hotel['status'] === 'active') {
                    session_regenerate_id(true);
                    $_SESSION['hotel_id'] = $hotel['id'];
                    $_SESSION['hotel_name'] = $hotel['hotel_name'];
                    $_SESSION['logged_in'] = true;

                    header("Location: admin/dashboard.php");
                    exit();
                } else {
                    $error = "Account is currently " . htmlspecialchars($hotel['status']) . ".";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/auth-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-bg">

    <div class="main-wrapper anim-fade-in">
        
        <div class="image-section anim-slide-right" id="particles-js">
            <div class="overlay"></div>
            <div class="content-wrapper">
                <div class="brand anim-fade-delay-1">
                    <i class="fa fa-utensils"></i> Smart Menu SaaS
                </div>
                <div class="slogan anim-fade-delay-2">
                    <h1>Find your sweet flavor</h1>
                    <p>The professional standard for modern restaurant management.</p>
                </div>
            </div>
        </div>

        <div class="form-section anim-slide-left">
            <div class="form-content">
                <div class="welcome-text">
                    <h2>Welcome Back!</h2>
                    <p>Login with your Email and Password</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert anim-pop-in">
                        <i class="fa fa-exclamation-circle"></i> <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST">
                    <div class="form-group">
                        <label>Your Email</label>
                        <div class="input-wrapper">
                            <i class="fa fa-envelope prefix-icon"></i>
                            <input type="email" name="email" placeholder="admin@test.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <i class="fa fa-lock prefix-icon"></i>
                            <input type="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="form-footer">
                        <label class="remember-me">
                            <input type="checkbox" name="remember"> 
                            <span>Remember Me</span>
                        </label>
                        </div>

                    <button type="submit" class="btn-submit">
                        <span>Login to Dashboard</span>
                        <i class="fa fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer-note anim-fade-delay-3">
                    <i class="fa fa-shield-alt"></i> Authorized Personnel Only
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
          "particles": {
            "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
            "color": { "value": "#2ecc71" },
            "shape": { "type": "circle" },
            "opacity": { "value": 0.5 },
            "size": { "value": 3, "random": true },
            "line_linked": { "enable": true, "distance": 150, "color": "#2ecc71", "opacity": 0.4, "width": 1 },
            "move": { "enable": true, "speed": 2 }
          },
          "interactivity": {
            "detect_on": "canvas",
            "events": { "onhover": { "enable": true, "mode": "grab" } },
            "modes": { "grab": { "distance": 140 } }
          },
          "retina_detect": true
        });
    </script>
</body>
</html>