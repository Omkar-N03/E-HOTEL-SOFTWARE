<?php
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($hotel) && isset($_SESSION['hotel_id'])) {
    require_once '../config/db.php';
    $stmt = $pdo->prepare("SELECT hotel_name, logo_url FROM hotels WHERE id = ?");
    $stmt->execute([$_SESSION['hotel_id']]);
    $hotel = $stmt->fetch();
}

$displayName = !empty($hotel['hotel_name']) ? htmlspecialchars($hotel['hotel_name']) : 'Restaurant';
$displayLogo = !empty($hotel['logo_url']) ? '../assets/img/logos/' . $hotel['logo_url'] : '../assets/img/logos/default-hotel.png';
?>

<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <img src="<?= $displayLogo ?>" alt="Logo" class="brand-img">
        <span class="brand-name"><?= $displayName ?></span>
    </div>

    <ul class="nav-list">
        <li class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <a href="dashboard.php" class="nav-link">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Overview</span>
            </a>
        </li>

        <li class="nav-item <?= ($current_page == 'manage-menu.php') ? 'active' : '' ?>">
            <a href="manage-menu.php" class="nav-link">
                <i class="fa-solid fa-burger"></i>
                <span>Menu Items</span>
            </a>
        </li>

        <li class="nav-item <?= ($current_page == 'manage-tables.php') ? 'active' : '' ?>">
            <a href="manage-tables.php" class="nav-link">
                <i class="fa-solid fa-qrcode"></i>
                <span>QR Tables</span>
            </a>
        </li>

        <li class="nav-item <?= ($current_page == 'live-orders.php') ? 'active' : '' ?>">
            <a href="../kitchen/live-orders.php" class="nav-link">
                <i class="fa-solid fa-fire"></i>
                <span>Live Orders</span>
            </a>
        </li>

        <li class="nav-item <?= ($current_page == 'reports.php') ? 'active' : '' ?>">
            <a href="reports.php" class="nav-link">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Revenue</span>
            </a>
        </li>

        <li class="nav-item <?= ($current_page == 'settings.php') ? 'active' : '' ?>">
            <a href="settings.php" class="nav-link">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="logout-section">
        <a href="logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to log out?')">
            <i class="fa-solid fa-power-off"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleMenu() {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        btn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);

        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if(sidebar.classList.contains('open')) {
                    toggleMenu();
                }
            });
        });
    });
</script>