<?php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE system_settings SET site_name=?, shipping_fee=?, contact_email=? WHERE setting_id=1");
    if ($stmt->execute([$_POST['site_name'], $_POST['shipping_fee'], $_POST['contact_email']])) {
        $message = "System settings updated successfully!";
    }
}

// Fetch Settings
$settings = $pdo->query("SELECT * FROM system_settings WHERE setting_id=1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - LaParfume Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/login.css">
    
    <style>
        /* MASTER ADMIN CSS */
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; overflow-x: hidden; min-height: 100vh; }
        
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; position: fixed; top: 0; left: 0; height: 100vh; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }

        /* GLOW BLOBS */
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }

        /* BIGGER SETTINGS CARD */
        .glass-card {
            background: rgba(22, 22, 22, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 50px; /* Increased padding */
            max-width: 900px; /* Increased width */
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .form-control { background-color: #2b2b2b; border: 1px solid #444; color: #fff; padding: 15px; border-radius: 8px; font-size: 1rem; }
        .form-control:focus { background-color: #333; border-color: #1dd1a1; color: #fff; box-shadow: none; }
        .form-label { color: #ccc; font-size: 1rem; font-weight: 500; margin-bottom: 10px; }
        
        .input-group-text { background-color: #222; border: 1px solid #444; color: #aaa; padding: 15px; }
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
            <h2 class="fw-bold text-white m-0">System Settings</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success bg-dark border-success text-success mb-4 d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="glass-card">
            <form method="POST">
                <h4 class="text-white mb-5 pb-2 border-bottom border-secondary">General Configuration</h4>
                
                <div class="mb-4">
                    <label class="form-label">Website Name</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                        <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'LaParfume'); ?>" required>
                    </div>
                    <div class="form-text text-secondary mt-2">This name appears in the browser title bar and footer.</div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Standard Shipping Fee</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" name="shipping_fee" class="form-control" value="<?php echo htmlspecialchars($settings['shipping_fee'] ?? '50.00'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Support Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'support@laparfume.com'); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-5">
                    <button type="submit" class="btn btn-success fw-bold px-5 py-3" style="background: #1dd1a1; border: none; color: black; font-size: 1.1rem;">
                        <i class="fas fa-save me-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>