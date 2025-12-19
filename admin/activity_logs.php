<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Fetch ALL Activity Logs
$stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Activity Logs - LaParfume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; overflow-x: hidden; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; position: fixed; top: 0; left: 0; height: 100vh; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }
        
        /* Table Styling */
        .table-custom { background: #161616; border-radius: 12px; overflow: hidden; width: 100%; }
        .table-custom th { background: #222; color: #fff; border: none; padding: 15px; }
        
        /* CRITICAL FIX: Force text color */
        .table-custom td { 
            background: transparent; 
            color: #e0e0e0 !important; /* Brighter grey */
            border-bottom: 1px solid #333; 
            padding: 15px; 
            vertical-align: middle; 
        }
        
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
        
        .badge-log { padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
        .bg-order { background: rgba(29, 209, 161, 0.2); color: #1dd1a1; }
        .bg-user { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .bg-seller { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .bg-product { background: rgba(155, 89, 182, 0.2); color: #9b59b6; }
    </style>
</head>
<body>
    <div class="glow-blob blob-1"></div><div class="glow-blob blob-2"></div>

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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-white m-0">System Activity Logs</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <?php 
                            $badgeClass = 'bg-secondary';
                            if($log['log_type'] == 'Order') $badgeClass = 'bg-order';
                            if($log['log_type'] == 'User') $badgeClass = 'bg-user';
                            if($log['log_type'] == 'Seller') $badgeClass = 'bg-seller';
                            if($log['log_type'] == 'Product') $badgeClass = 'bg-product';

                            // Convert server time to Philippine Time
                            try {
                                $date = new DateTime($log['created_at']);
                                $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                $formatted_date = $date->format('M d, Y h:i A');
                            } catch (Exception $e) {
                                $formatted_date = $log['created_at']; 
                            }
                            
                            // FORCE $ TO ₱ REPLACEMENT
                            $message = str_replace('$', '₱', htmlspecialchars($log['message']));
                        ?>
                    <tr>
                        <td><span class="badge-log <?php echo $badgeClass; ?>"><?php echo $log['log_type']; ?></span></td>
                        <td><?php echo $message; ?></td>
                        <td style="color: #aaa;"><?php echo $formatted_date; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>