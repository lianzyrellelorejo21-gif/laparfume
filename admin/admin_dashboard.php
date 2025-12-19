<?php
session_start();
require_once '../config/database.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['user_name'];

// --- TIME HELPER FUNCTION (PHP 8.2+ COMPATIBLE & PH TIMEZONE) ---
function time_elapsed_string($datetime, $full = false) {
    // 1. Get Current Time in Manila
    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    
    // 2. Get DB Time (Default Server Time) and convert to Manila
    $ago = new DateTime($datetime); 
    $ago->setTimezone(new DateTimeZone('Asia/Manila'));
    
    $diff = $now->diff($ago);

    // 3. Calculate weeks manually to avoid PHP 8.2 Deprecation Error
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($k == 'w') {
            $value = $weeks;
        } elseif ($k == 'd') {
            $value = $days;
        } else {
            $value = $diff->$k;
        }

        if ($value) {
            $v = $value . ' ' . $v . ($value > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// 2. Fetch System Stats
$total_sales = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Delivered'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
$total_sellers = $pdo->query("SELECT COUNT(*) FROM sellers WHERE is_verified = 1")->fetchColumn();
$pending_sellers_count = $pdo->query("SELECT COUNT(*) FROM sellers WHERE is_verified = 0")->fetchColumn();
$pending_products_count = $pdo->query("SELECT COUNT(*) FROM products WHERE product_status = 'Pending'")->fetchColumn();
$pending_withdrawals_count = $pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'Pending'")->fetchColumn();

// 3. FETCH SYSTEM HEALTH
try {
    $stmt = $pdo->query("SHOW TABLE STATUS");
    $db_size = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_size += $row["Data_length"] + $row["Index_length"];
    }
    $db_size_mb = round($db_size / 1024 / 1024, 2);
    $storage_percent = min(100, ($db_size_mb / 100) * 100); 

    $busy_metric = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= NOW() - INTERVAL 1 HOUR")->fetchColumn();
    $server_load = min(100, $busy_metric * 5); 

} catch (Exception $e) {
    $db_size_mb = 0;
    $storage_percent = 0;
    $server_load = 5;
}

// 4. Fetch Recent Activity
try {
    $stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 7");
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $recent_activities = []; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LaParfume</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/login.css">
    
    <style>
        body { background-color: #000; }
        
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; display: flex; flex-direction: column; padding: 20px; position: fixed; height: 100%; top: 0; left: 0; z-index: 100; }
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; background-color: #000; min-height: 100vh; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .admin-card { background: #161616; border: 1px solid #333; border-radius: 10px; padding: 25px; display: flex; align-items: center; justify-content: space-between; transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease; }
        .admin-card .icon-box { width: 50px; height: 50px; border-radius: 10px; background: rgba(29, 209, 161, 0.1); color: #1dd1a1; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .admin-card:hover { transform: translateY(-5px); border-color: #1dd1a1; box-shadow: 0 10px 20px rgba(29, 209, 161, 0.15); cursor: pointer; }
        .alert-pending { background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3); color: #ffc107; border-radius: 10px; padding: 15px; display: flex; align-items: center; justify-content: space-between; }
        
        .activity-item { border-bottom: 1px solid #333; padding: 15px 0; display: flex; align-items: center; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; flex-shrink: 0; }
        .activity-time { font-size: 0.8rem; color: #666; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="mb-5 px-2">
        <h3 class="fw-bold text-white">LA<span class="text-teal-400" style="color: #1dd1a1;">Admin</span></h3>
    </div>
    
    <nav class="nav flex-column">
        <a href="admin_dashboard.php" class="nav-link active">
            <i class="fas fa-chart-line"></i> Dashboard
        </a>
        
        <a href="verify_sellers.php" class="nav-link">
            <i class="fas fa-user-check"></i> Seller Requests
        </a>
        
        <a href="verify_products.php" class="nav-link">
            <i class="fas fa-boxes"></i> Product Review
        </a>

        <a href="users.php" class="nav-link">
            <i class="fas fa-users"></i> Users
        </a>
        
        <a href="products.php" class="nav-link">
            <i class="fas fa-box"></i> Products
        </a>
        
        <a href="admin_orders.php" class="nav-link">
            <i class="fas fa-shopping-cart"></i> Orders
        </a>

        <a href="manage_withdrawals.php" class="nav-link">
            <i class="fas fa-wallet"></i> Withdrawals
        </a>
            
        <a href="messages.php" class="nav-link">
            <i class="fas fa-envelope"></i> Messages
        </a>

        <a href="manage_home.php" class="nav-link">
            <i class="fas fa-edit"></i> Manage Home
        </a>
    </nav>

    <div class="mt-auto">
        <a href="../logout.php" class="nav-link text-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>
<div class="main-content">
    <h2 class="text-white fw-bold mb-4">Admin Overview</h2>

    <?php if ($pending_sellers_count > 0): ?>
    <div class="alert-pending mb-4">
        <div><i class="fas fa-exclamation-triangle me-2"></i><strong>Action Required:</strong> There are <?php echo $pending_sellers_count; ?> new seller applications.</div>
        <a href="verify_sellers.php" class="btn btn-sm btn-warning text-black fw-bold">Review Now</a>
    </div>
    <?php endif; ?>
        
    <?php if ($pending_products_count > 0): ?>
        <div class="alert-pending mb-4" style="background-color: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); color: #ff6b6b;">
            <div><i class="fas fa-box-open me-2"></i><strong>Product Review:</strong> There are <?php echo $pending_products_count; ?> new products waiting for approval.</div>
            <a href="verify_products.php" class="btn btn-sm btn-danger fw-bold">Review Now</a>
        </div>
        <?php endif; ?>
        
    <?php if ($pending_withdrawals_count > 0): ?>
    <div class="alert-pending mb-3" style="background-color: rgba(220, 53, 69, 0.1); border-color: rgba(220, 53, 69, 0.3); color: #ff6b6b;">
        <div><i class="fas fa-wallet me-2"></i><strong>Payout Request:</strong> You have <?php echo $pending_withdrawals_count; ?> seller withdrawal request(s).</div>
        <a href="manage_withdrawals.php" class="btn btn-sm btn-danger fw-bold">Process Payout</a>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <a href="admin_orders.php" class="text-decoration-none">
                <div class="admin-card">
                    <div><h6 class="text-muted mb-1">Total Sales</h6><h3 class="text-white fw-bold m-0">₱<?php echo number_format($total_sales ?? 0, 2); ?></h3></div>
                    <div class="icon-box"><i class="fas fa-peso-sign"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="products.php" class="text-decoration-none">
                <div class="admin-card">
                    <div><h6 class="text-muted mb-1">Products</h6><h3 class="text-white fw-bold m-0"><?php echo $total_products; ?></h3></div>
                    <div class="icon-box"><i class="fas fa-box-open"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="users.php" class="text-decoration-none">
                <div class="admin-card">
                    <div><h6 class="text-muted mb-1">Customers</h6><h3 class="text-white fw-bold m-0"><?php echo $total_customers; ?></h3></div>
                    <div class="icon-box"><i class="fas fa-users"></i></div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="verify_sellers.php" class="text-decoration-none">
                <div class="admin-card">
                    <div><h6 class="text-muted mb-1">Active Sellers</h6><h3 class="text-white fw-bold m-0"><?php echo $total_sellers; ?></h3></div>
                    <div class="icon-box"><i class="fas fa-store"></i></div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="bg-dark p-4 rounded border border-secondary">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="text-white m-0">Recent System Activity</h5>
                    <a href="activity_logs.php" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="activity-list">
                    <?php if (count($recent_activities) > 0): ?>
                        <?php foreach ($recent_activities as $activity): ?>
                            <?php 
                                $icon = 'fa-info-circle'; $bg = 'bg-secondary'; $text = 'text-white';
                                switch($activity['log_type']) {
                                    case 'Order':   $icon = 'fa-shopping-cart'; $bg = 'rgba(29, 209, 161, 0.2)'; $text = '#1dd1a1'; break;
                                    case 'Seller':  $icon = 'fa-store'; $bg = 'rgba(255, 193, 7, 0.2)'; $text = '#ffc107'; break;
                                    case 'User':    $icon = 'fa-user-plus'; $bg = 'rgba(52, 152, 219, 0.2)'; $text = '#3498db'; break;
                                }
                                
                                // FORCE $ TO ₱ REPLACEMENT
                                $message = str_replace('$', '₱', htmlspecialchars($activity['message']));
                                
                                // CALCULATE RELATIVE TIME (AGO)
                                $time_display = time_elapsed_string($activity['created_at']);
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background: <?php echo $bg; ?>; color: <?php echo $text; ?>;"><i class="fas <?php echo $icon; ?>"></i></div>
                                <div class="flex-grow-1">
                                    <p class="mb-0 text-light" style="font-size: 0.95rem;"><?php echo $message; ?></p>
                                    <span class="activity-time"><i class="far fa-clock me-1"></i> <?php echo $time_display; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted"><p>No recent activity.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="bg-dark p-4 rounded border border-secondary h-100">
                <h5 class="text-white mb-3">System Health</h5>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Database Size (<?php echo $db_size_mb; ?> MB)</span>
                        <span><?php echo $storage_percent; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px; background: #333;">
                        <div class="progress-bar bg-info" style="width: <?php echo $storage_percent; ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Server Load (Activity)</span>
                        <span><?php echo $server_load; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px; background: #333;">
                        <div class="progress-bar bg-success" style="width: <?php echo $server_load; ?>%"></div>
                    </div>
                </div>
                
                <div class="mt-4">
                     <h6 class="text-white-50 small text-uppercase mb-3">Quick Actions</h6>
                     <a href="manage_categories.php" class="btn btn-outline-light btn-sm w-100 mb-2">Manage Categories</a>
                     <a href="system_settings.php" class="btn btn-outline-light btn-sm w-100">System Settings</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>