<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$message = '';

// --- HANDLE ACTIONS ---
if (isset($_POST['action'])) {
    $withdrawal_id = $_POST['withdrawal_id'];
    $action = $_POST['action']; // 'Approved' or 'Rejected'
    
    // Update Status
    $stmt = $pdo->prepare("UPDATE withdrawals SET status = ?, date_processed = NOW() WHERE withdrawal_id = ?");
    if ($stmt->execute([$action, $withdrawal_id])) {
        $message = "Request has been " . strtolower($action) . ".";
    }
}

// Fetch Pending Requests
$stmt = $pdo->query("
    SELECT w.*, s.business_name, s.full_name, s.email 
    FROM withdrawals w 
    JOIN sellers s ON w.seller_id = s.seller_id 
    WHERE w.status = 'Pending' 
    ORDER BY w.date_requested ASC
");
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch History (Last 50 processed)
$stmt = $pdo->query("
    SELECT w.*, s.business_name 
    FROM withdrawals w 
    JOIN sellers s ON w.seller_id = s.seller_id 
    WHERE w.status != 'Pending' 
    ORDER BY w.date_processed DESC 
    LIMIT 50
");
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Withdrawals - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/login.css">
    <style>
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; min-height: 100vh; overflow-x: hidden; }
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; position: fixed; top: 0; left: 0; height: 100vh; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }
        
        .table-custom { background: #161616; border-radius: 12px; overflow: hidden; width: 100%; margin-bottom: 30px; }
        .table-custom th { background: #222; color: #fff; border: none; padding: 15px; }
        .table-custom td { background: transparent; color: #ccc; border-bottom: 1px solid #333; padding: 15px; vertical-align: middle; }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; text-transform: uppercase; font-weight: 600; }
        .bg-Pending { background: rgba(255, 193, 7, 0.15); color: #ffc107; }
        .bg-Approved { background: rgba(25, 135, 84, 0.15); color: #198754; }
        .bg-Rejected { background: rgba(220, 53, 69, 0.15); color: #dc3545; }
        
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
    </style>
</head>
<body>

    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>

    <div class="sidebar">
    <div class="mb-5 px-2">
        <h3 class="fw-bold text-white">LA<span class="text-teal-400" style="color: #1dd1a1;">Admin</span></h3>
    </div>
    
    <nav class="nav flex-column">
        <a href="admin_dashboard.php" class="nav-link">
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

        <a href="manage_withdrawals.php" class="nav-link active">
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
            <h2 class="fw-bold text-white m-0">Payout Requests</h2>
            <?php if(count($pending_requests) > 0): ?>
                <span class="badge bg-warning text-dark"><?php echo count($pending_requests); ?> Pending</span>
            <?php endif; ?>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success bg-dark border-success text-success mb-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <h5 class="text-warning mb-3">Pending Requests</h5>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Details</th>
                        <th>Requested</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($pending_requests) > 0): ?>
                        <?php foreach($pending_requests as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white"><?php echo htmlspecialchars($row['business_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($row['full_name']); ?></small>
                            </td>
                            <td class="fw-bold text-success fs-5">₱<?php echo number_format($row['amount'], 2); ?></td>
                            <td><span class="badge bg-dark border border-secondary"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
                            <td class="text-white small"><?php echo htmlspecialchars($row['account_details']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_requested'])); ?></td>
                            <td class="text-end">
                                <form method="POST" class="d-inline-flex gap-2">
                                    <input type="hidden" name="withdrawal_id" value="<?php echo $row['withdrawal_id']; ?>">
                                    <button type="submit" name="action" value="Approved" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Confirm payment sent to seller?');">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                    <button type="submit" name="action" value="Rejected" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this request? Funds will return to seller.');">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No pending withdrawal requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h5 class="text-white-50 mt-5 mb-3">Transaction History</h5>
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Seller</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Processed Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $row): ?>
                    <tr>
                        <td class="text-white"><?php echo htmlspecialchars($row['business_name']); ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['date_processed'])); ?></td>
                        <td><span class="badge-status bg-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>