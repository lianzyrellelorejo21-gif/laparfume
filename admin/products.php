<?php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

$message = '';
$error = '';

// --- 1. HANDLE "DELETE" (SOFT DELETE / ARCHIVE) ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Instead of deleting, we hide it (Soft Delete)
    $pdo->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?")->execute([$id]);
    header("Location: products.php"); exit();
}

// --- 2. HANDLE "RESTORE" (Bring it back) ---
if (isset($_GET['restore'])) {
    $id = $_GET['restore'];
    $pdo->prepare("UPDATE products SET is_active = 1 WHERE product_id = ?")->execute([$id]);
    header("Location: products.php"); exit();
}

// --- 3. HANDLE "HARD DELETE" (PERMANENT DELETE) ---
// Keeps order history intact while deleting the product record
if (isset($_GET['hard_delete'])) {
    $id = $_GET['hard_delete'];
    try {
        // A. Temporarily Disable Foreign Key Checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        // B. Delete from product_details
        $pdo->prepare("DELETE FROM product_details WHERE product_id = ?")->execute([$id]);
        
        // C. Delete the product itself
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        
        // D. Re-enable Foreign Key Checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        $message = "Product permanently deleted. (Existing order records have been preserved)";
    } catch (PDOException $e) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch Products
$sql = "SELECT p.*, s.business_name, s.full_name 
        FROM products p 
        JOIN sellers s ON p.seller_id = s.seller_id 
        ORDER BY p.is_active DESC, p.date_added DESC"; 
$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Inventory - LaParfume</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; overflow-x: hidden; min-height: 100vh; }
        .text-muted {
            color: #aaa !important;
        }
        .sidebar { width: 260px; background-color: #111; border-right: 1px solid #333; position: fixed; top: 0; left: 0; height: 100vh; padding: 20px; z-index: 1000; display: flex; flex-direction: column; }
        .main-content { margin-left: 260px; padding: 30px; }
        .nav-link { color: #aaa; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background-color: #1dd1a1; color: #000; font-weight: 600; }
        .sidebar .mt-auto { margin-top: auto !important; }
        .table-custom { background: #161616; border-radius: 12px; overflow: hidden; width: 100%; }
        .table-custom th { background: #222; color: #fff; border: none; padding: 15px; }
        .table-custom td { background: transparent; color: #ccc; border-bottom: 1px solid #333; padding: 15px; vertical-align: middle; }
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
        
        /* Styles for Inactive Rows */
        .row-inactive td { opacity: 0.5; }
        .badge-archived { background: #ff4757; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px; }
        
        /* Modal Styles */
        .modal-content { background: #1a1a1a; border: 1px solid #333; color: white; }
        .modal-header { border-bottom: 1px solid #333; }
        .modal-footer { border-top: 1px solid #333; }
        .btn-close { filter: invert(1); }
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
        
        <a href="products.php" class="nav-link active">
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
        <h2 class="fw-bold mb-4">Global Inventory Control</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger bg-dark border-danger text-danger mb-4">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if($message): ?>
            <div class="alert alert-success bg-dark border-success text-success mb-4">
                <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th width="80">Image</th>
                        <th>Product Name</th>
                        <th>Seller / Store</th>
                        <th>Price</th>
                        <th>Stock Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                        <tr class="<?php echo ($p['is_active'] == 0) ? 'row-inactive' : ''; ?>">
                            <td>
                                <?php $img = !empty($p['image']) ? '../images/' . $p['image'] : 'https://via.placeholder.com/50'; ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" style="width: 50px; height: 50px; border-radius: 8px; object-fit:cover;">
                            </td>
                            <td>
                                <div class="text-white fw-bold">
                                    <?php echo htmlspecialchars($p['product_name']); ?>
                                    <?php if($p['is_active'] == 0): ?>
                                        <span class="badge-archived">ARCHIVED</span>
                                    <?php endif; ?>
                                </div>
                                <small style="color: #aaa;">ID: #<?php echo $p['product_id']; ?></small>
                            </td>
                            <td>
                                <span class="text-teal-400 fw-bold" style="color: #1dd1a1;">
                                    <?php echo htmlspecialchars(!empty($p['business_name']) ? $p['business_name'] : $p['full_name']); ?>
                                </span>
                            </td>
                            <td class="text-white">₱<?php echo number_format($p['price'], 2); ?></td>
                            
                            <td>
                                <?php if($p['is_active'] == 0): ?>
                                    <span class="text-muted">Inactive</span>
                                <?php elseif($p['stock'] == 0): ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php elseif($p['stock'] <= 5): ?>
                                    <span class="text-warning fw-bold"><?php echo $p['stock']; ?> (Low)</span>
                                <?php else: ?>
                                    <span class="text-success"><?php echo $p['stock']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if($p['is_active'] == 1): ?>
                                    <a href="products.php?delete=<?php echo $p['product_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Archive this product? It will be hidden from the shop.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="products.php?restore=<?php echo $p['product_id']; ?>" class="btn btn-sm btn-outline-success me-1" title="Restore Product">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                    
                                    <button type="button" class="btn btn-sm btn-danger" title="Delete Permanently" onclick="confirmHardDelete(<?php echo $p['product_id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No products found in system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Permanent Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-white">
                    <p class="mb-2">Are you sure you want to <strong class="text-danger">permanently delete</strong> this product?</p>
                    <p class="small text-muted mb-0">This action cannot be undone. However, existing order records for this product will be preserved in customer history.</p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                    <a href="#" id="confirmDeleteLink" class="btn btn-danger">Yes, Delete It</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmHardDelete(id) {
            // Update the link in the modal "Yes" button
            document.getElementById('confirmDeleteLink').href = "products.php?hard_delete=" + id;
            
            // Show the modal
            var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            myModal.show();
        }
    </script>
</body>
</html>