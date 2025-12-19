<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$message = '';

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $p_id = intval($_POST['product_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE products SET product_status = 'Approved', is_active = 1 WHERE product_id = ?");
        if ($stmt->execute([$p_id])) $message = "Product approved and is now live!";
    
    } elseif ($action === 'reject') {
        // You can choose to delete it or just mark as rejected
        $stmt = $pdo->prepare("UPDATE products SET product_status = 'Rejected', is_active = 0 WHERE product_id = ?");
        if ($stmt->execute([$p_id])) $message = "Product rejected.";
    }
}

// Fetch Pending Products
$stmt = $pdo->query("
    SELECT p.*, s.business_name, s.full_name 
    FROM products p 
    JOIN sellers s ON p.seller_id = s.seller_id 
    WHERE p.product_status = 'Pending' 
    ORDER BY p.date_added ASC
");
$pending_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Products - Admin</title>
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
        
        .table-custom { background: #161616; border-radius: 12px; overflow: hidden; width: 100%; }
        .table-custom th { background: #222; color: #fff; border: none; padding: 15px; }
        .table-custom td { background: transparent; color: #ccc; border-bottom: 1px solid #333; padding: 15px; vertical-align: middle; }
        
        .img-thumb { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
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
        
        <a href="verify_products.php" class="nav-link active">
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
            <h2 class="text-white fw-bold m-0">Product Review</h2>
            <span class="badge bg-warning text-dark"><?php echo count($pending_products); ?> Pending</span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success bg-dark border-success text-success mb-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Price & Stock</th>
                        <th>Seller</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pending_products) > 0): ?>
                        <?php foreach ($pending_products as $p): ?>
                        <tr>
                            <td>
                                <img src="../images/<?php echo $p['image']; ?>" class="img-thumb">
                            </td>
                            <td>
                                <div class="fw-bold text-white"><?php echo htmlspecialchars($p['product_name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($p['description'], 0, 50)); ?>...</small>
                            </td>
                            <td>
                                <div class="text-teal-400 fw-bold">₱<?php echo number_format($p['price'], 2); ?></div>
                                <small class="text-muted">Stock: <?php echo $p['stock']; ?></small>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-white-50">
                                    <?php echo htmlspecialchars($p['business_name'] ?? $p['full_name']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <form method="POST" class="d-inline-flex gap-2">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success fw-bold" onclick="return confirm('Approve this product?');">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this product?');">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No pending products.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>