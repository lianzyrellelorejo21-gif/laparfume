<?php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$message = '';

// --- HANDLE ACTIONS (Approve, Reject, Deactivate, Activate) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $seller_id = intval($_POST['seller_id']); // Security Fix: Force integer
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE sellers SET is_verified = 1, is_active = 1 WHERE seller_id = ?");
        if ($stmt->execute([$seller_id])) $message = "Seller approved successfully!";
    
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM sellers WHERE seller_id = ?");
        if ($stmt->execute([$seller_id])) $message = "Application rejected.";
    
    } elseif ($action === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE sellers SET is_active = 0 WHERE seller_id = ?");
        if ($stmt->execute([$seller_id])) $message = "Seller account deactivated (Banned).";
    
    } elseif ($action === 'activate') {
        $stmt = $pdo->prepare("UPDATE sellers SET is_active = 1 WHERE seller_id = ?");
        if ($stmt->execute([$seller_id])) $message = "Seller account re-activated.";
    }
}

// Fetch Pending Sellers
$stmt = $pdo->query("SELECT * FROM sellers WHERE is_verified = 0 ORDER BY date_created DESC");
$pending_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Verified Sellers (FIXED SORTING: Active First, Banned Last)
$stmt = $pdo->query("SELECT * FROM sellers WHERE is_verified = 1 ORDER BY is_active DESC, date_created DESC");
$active_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Sellers - LaParfume Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reuse Master Admin CSS (Same as Dashboard) */
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; overflow-x: hidden; min-height: 100vh; }
        .text-muted {
            color: #aaa !important;
        }
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
        
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; position: fixed; top: 0; left: 0; height: 100vh; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }
        
        .table-custom { background: #161616; border-radius: 12px; overflow: hidden; width: 100%; }
        .table-custom th { background: #222; color: #fff; border: none; padding: 15px; }
        .table-custom td { background: transparent; color: #ccc; border-bottom: 1px solid #333; padding: 15px; vertical-align: middle; }
        
        .badge-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; border: 1px solid rgba(255, 193, 7, 0.2); padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; }
        .badge-banned { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; }
        .badge-active { background: rgba(25, 135, 84, 0.2); color: #198754; border: 1px solid #198754; padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; }
        
        .alert-glass { background: rgba(29, 209, 161, 0.1); border: 1px solid #1dd1a1; color: #1dd1a1; backdrop-filter: blur(5px); border-radius: 8px; }
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
        
        <a href="verify_sellers.php" class="nav-link active">
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
            <h2 class="text-white fw-bold m-0">Seller Applications</h2>
            <span class="badge bg-dark border border-secondary"><?php echo count($pending_sellers); ?> New Requests</span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-glass d-flex align-items-center mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="section-header mt-4 mb-3">
            <h5 class="text-warning m-0"><i class="fas fa-clock me-2"></i>Pending Approval</h5>
        </div>
        
        <div class="table-responsive mb-5">
            <table class="table table-custom">
                <thead>
                    <tr><th>ID</th><th>Store Name</th><th>Contact Info</th><th>Date Applied</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (count($pending_sellers) > 0): ?>
                        <?php foreach ($pending_sellers as $seller): ?>
                        <tr>
                            <td>#<?php echo $seller['seller_id']; ?></td>
                            <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                            <td><div><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($seller['email']); ?></div><div><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($seller['phone']); ?></div></td>
                            <td><?php echo date('M d, Y', strtotime($seller['date_created'])); ?></td>
                            <td><span class="badge-pending">Pending Review</span></td>
                            <td class="text-end">
                                <form method="POST" class="d-inline-flex gap-2">
                                    <input type="hidden" name="seller_id" value="<?php echo $seller['seller_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success fw-bold" style="background:#1dd1a1; border:none; color:black;"><i class="fas fa-check"></i> Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this application?');"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-5">No pending applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-header mb-3">
            <h5 class="text-white-50 m-0"><i class="fas fa-users me-2"></i>Verified Sellers Directory</h5>
        </div>
        
        <div class="table-responsive">
    <table class="table table-custom">
        <thead>
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Store Name</th>
                <th>Email</th>
                <th>Joined Date</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($active_sellers as $seller): ?>
            <tr style="<?php echo ($seller['is_active'] == 0) ? 'opacity:0.6;' : ''; ?>">
                <td>#<?php echo $seller['seller_id']; ?></td>
                
                <td>
                    <?php 
                        $logo = !empty($seller['business_logo']) ? '../images/sellers/' . $seller['business_logo'] : '../assets/images/placeholder_store.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($logo); ?>" 
                         alt="Store Logo" 
                         style="width: 40px; height: 40px; border-radius: 5px; object-fit: cover;">
                </td>
                
                <td class="text-white fw-bold"><?php echo htmlspecialchars($seller['business_name'] ?? $seller['full_name']); ?></td>
                <td><?php echo htmlspecialchars($seller['email']); ?></td>
                <td><?php echo date('M d, Y', strtotime($seller['date_created'])); ?></td>
                <td>
                    <?php if($seller['is_active']): ?>
                        <span class="badge-active">Active</span>
                    <?php else: ?>
                        <span class="badge-banned">Banned</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <form method="POST">
                        <input type="hidden" name="seller_id" value="<?php echo $seller['seller_id']; ?>">
                        <?php if($seller['is_active']): ?>
                            <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to BAN this seller? They will not be able to login.');">
                                <i class="fas fa-ban"></i> Ban
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-undo"></i> Unban
                            </button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    </div>

</body>
</html>