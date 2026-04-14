<?php
require_once '../config/db.php';
require_once '../config/sessions.php';

protect_page(['super_admin']);

try {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE status = 'pending' ORDER BY created_at DESC");
    $stmt->execute();
    $pending_hotels = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Requests | Platform Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-soft: #eef2ff;
            --dark: #0f172a;
            --slate: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
            --success: #10b981;
            --danger: #ef4444;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg); color: var(--dark); }

        .admin-container { display: flex; min-height: 100vh; }

        .sidebar { width: 260px; background: var(--dark); color: white; padding: 1.5rem; position: fixed; height: 100vh; z-index: 100; }
        .logo h2 { font-size: 1.25rem; font-weight: 800; margin-bottom: 2rem; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar a { text-decoration: none; color: #94a3b8; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 12px; border-radius: var(--radius); transition: 0.3s; font-weight: 500; }
        .sidebar li.active a, .sidebar a:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .main-content { margin-left: 260px; flex: 1; padding: 2.5rem; }

        header { margin-bottom: 2rem; }
        header h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
        header p { color: var(--slate); }

        .status-overview {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            background: var(--white);
            padding: 1rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            align-items: center;
        }

        .badge-count {
            background: var(--primary-soft);
            color: var(--primary);
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .request-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid #f1f5f9;
            transition: 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .request-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1); }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(to right, #f8fafc, #ffffff);
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .card-header h3 { font-size: 1.1rem; color: var(--dark); font-weight: 700; }
        .card-header .date { font-size: 0.75rem; color: var(--slate); font-weight: 500; }

        .card-body { padding: 1.5rem; flex-grow: 1; }
        .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; color: var(--slate); font-size: 0.9rem; }
        .info-row i { color: var(--primary); width: 16px; font-size: 0.8rem; }
        .info-row strong { color: var(--dark); font-weight: 600; }

        .card-footer {
            padding: 1.25rem;
            background: #fafafa;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 10px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: 0.3s;
        }

        .btn-approve { background: var(--success); color: white; }
        .btn-approve:hover { background: #059669; }
        
        .btn-reject { background: #fee2e2; color: var(--danger); }
        .btn-reject:hover { background: var(--danger); color: white; }

        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--white);
            border-radius: var(--radius);
            color: var(--slate);
        }
        .empty-state i { font-size: 4rem; margin-bottom: 1.5rem; opacity: 0.2; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar span, .logo h2 { display: none; }
            .main-content { margin-left: 70px; padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="admin-container">
    <nav class="sidebar">
        <div class="logo"><h2>Platform OS</h2></div>
        <ul>
            <li><a href="index.php"><i class="fa-solid fa-house-chimney-window"></i> <span>Dashboard</span></a></li>
            <li><a href="manage-hotels.php"><i class="fa-solid fa-building-circle-check"></i> <span>Manage Hotels</span></a></li>
            <li class="active">
                <a href="hotel-registrations.php">
                    <i class="fa-solid fa-envelope-open-text"></i> 
                    <span>Requests</span>
                </a>
            </li>
            <li style="margin-top: auto;">
                <a href="../admin/logout.php" style="color: var(--danger);">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <main class="main-content">
        <header>
            <h1>Pending Registrations</h1>
            <p>Verify and approve partner restaurants for the platform.</p>
        </header>

        <div class="status-overview">
            <i class="fa-solid fa-filter" style="color: var(--slate)"></i>
            <span style="font-weight: 600; font-size: 0.9rem;">Total Pending:</span>
            <span class="badge-count"><?php echo count($pending_hotels); ?> Application(s)</span>
        </div>

        <section class="request-list">
            <?php if (empty($pending_hotels)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-mug-hot"></i>
                    <h3>All caught up!</h3>
                    <p>There are no new restaurant requests to review right now.</p>
                </div>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($pending_hotels as $hotel): ?>
                    <div class="request-card">
                        <div class="card-header">
                            <div>
                                <h3><?php echo htmlspecialchars($hotel['hotel_name']); ?></h3>
                                <div class="date">Submitted <?php echo date('M d, Y', strtotime($hotel['created_at'])); ?></div>
                            </div>
                            <span style="background: var(--primary-soft); color: var(--primary); font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; font-weight: 700;">NEW</span>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-row">
                                <i class="fa-solid fa-envelope"></i>
                                <span><strong>Email:</strong> <?php echo htmlspecialchars($hotel['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fa-solid fa-fingerprint"></i>
                                <span><strong>Reference ID:</strong> #REQ-<?php echo $hotel['id']; ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fa-solid fa-clock"></i>
                                <span><strong>Wait Time:</strong> <?php 
                                    $start = new DateTime($hotel['created_at']);
                                    $now = new DateTime();
                                    $diff = $now->diff($start);
                                    echo $diff->d > 0 ? $diff->d . " days ago" : "Today";
                                ?></span>
                            </div>
                        </div>

                        <div class="card-footer">
                            <a href="manage-hotels.php?action=approve&id=<?php echo $hotel['id']; ?>" class="btn btn-approve">
                                <i class="fa-solid fa-circle-check"></i> Approve
                            </a>
                            <a href="manage-hotels.php?action=delete&id=<?php echo $hotel['id']; ?>" class="btn btn-reject" onclick="return confirm('Reject this application permanently?')">
                                <i class="fa-solid fa-circle-xmark"></i> Reject
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

</body>
</html>