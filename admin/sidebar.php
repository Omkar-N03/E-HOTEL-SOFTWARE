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

<style>
    .sidebar {
        width: 260px;
        background: #0f172a;
        height: 100vh;
        display: flex;
        flex-direction: column;
        padding: 1.5rem;
        position: fixed;
        left: 0;
        top: 0;
        color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 2rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 1.5rem;
    }

    .brand-img {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        object-fit: cover;
        background: #fff;
    }

    .brand-name {
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: -0.5px;
    }

    .nav-list {
        list-style: none;
        flex-grow: 1;
    }

    .nav-item {
        margin-bottom: 0.5rem;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0.8rem 1rem;
        text-decoration: none;
        color: #94a3b8;
        border-radius: 12px;
        font-weight: 500;
        transition: 0.3s;
    }

    .nav-item.active .nav-link, 
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .nav-link i {
        width: 20px;
        font-size: 1.1rem;
    }

    /* Logout Section */
    .logout-section {
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .logout-link {
        color: #fca5a5; /* Soft red */
    }

    .logout-link:hover {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #ef4444 !important;
    }
</style>

<aside class="sidebar">
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
        <a href="../logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to log out?')">
            <i class="fa-solid fa-power-off"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>