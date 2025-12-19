<?php
session_start();
require_once '../config/database.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_title = $_POST['hero_title'];
    
    // Check if a banner row exists
    $check = $pdo->query("SELECT section_id FROM homepage WHERE section_type = 'Banner'")->fetch();
    
    if ($check) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE homepage SET title = ? WHERE section_type = 'Banner'");
        $stmt->execute([$new_title]);
    } else {
        // Insert new row if table is empty
        $stmt = $pdo->prepare("INSERT INTO homepage (section_type, title) VALUES ('Banner', ?)");
        $stmt->execute([$new_title]);
    }
    $message = "Homepage banner updated successfully!";
}

// 3. Fetch Current Data
$current_data = $pdo->query("SELECT title FROM homepage WHERE section_type = 'Banner'")->fetch(PDO::FETCH_ASSOC);
$current_title = $current_data ? $current_data['title'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Home - LaParfume Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* MASTER ADMIN CSS */
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; overflow-x: hidden; min-height: 100vh; }
        .text-muted {
            color: #aaa !important;
        }
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
        
        .sidebar { width: 260px; background: rgba(22, 22, 22, 0.85); backdrop-filter: blur(10px); border-right: 1px solid rgba(255,255,255,0.1); position: fixed; height: 100vh; top: 0; left: 0; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }

        /* Form Card */
        .admin-card { background: #161616; border: 1px solid #333; border-radius: 12px; padding: 30px; }
        .form-control { background-color: #2b2b2b; border: 1px solid #444; color: #fff; padding: 12px; }
        .form-control:focus { background-color: #333; border-color: #1dd1a1; color: #fff; box-shadow: none; }
    </style>
</head>
<body>
    <div class="glow-blob blob-1"></div><div class="glow-blob blob-2"></div>

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

        <a href="manage_withdrawals.php" class="nav-link">
            <i class="fas fa-wallet"></i> Withdrawals
        </a>
            
        <a href="messages.php" class="nav-link">
            <i class="fas fa-envelope"></i> Messages
        </a>

        <a href="manage_home.php" class="nav-link active">
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
        <h2 class="fw-bold mb-4">Website Customization</h2>
        
        <?php if($message): ?>
            <div class="alert alert-success bg-dark border-success text-success mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="admin-card">
                    <h5 class="text-white mb-4">Homepage Banner</h5>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label text-muted">Main Headline Text</label>
                            <input type="text" name="hero_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($current_title); ?>" 
                                   placeholder="e.g. Up to 50% Off Luxury Scents" required>
                            <div class="form-text text-secondary">This text appears big on your homepage banner.</div>
                        </div>
                        <button type="submit" class="btn btn-success fw-bold" style="background-color: #1dd1a1; border: none; color: black; padding: 10px 30px;">
                            Update Banner
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="alert alert-info bg-dark border-info text-info">
                    <h6 class="fw-bold"><i class="fas fa-lightbulb me-2"></i>Tip</h6>
                    <p class="small mb-0">Use this to announce holiday sales like "Christmas Sale" or "Valentine's Special" instantly without coding.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>