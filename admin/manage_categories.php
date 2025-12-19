<?php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category_name'])) {
    $cat_name = trim($_POST['category_name']);
    if(!empty($cat_name)){
        // Check if exists
        $check = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $check->execute([$cat_name]);
        if($check->rowCount() > 0){
            $error = "Category already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            if($stmt->execute([$cat_name])){
                $message = "Category added successfully!";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$_GET['delete']]);
        header("Location: manage_categories.php"); 
        exit();
    } catch (Exception $e) {
        $error = "Cannot delete category. It might be assigned to products.";
    }
}

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories - LaParfume</title>
    
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

        /* CATEGORY SPECIFIC STYLES */
        .glass-card {
            background: rgba(22, 22, 22, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
        }

        .form-control { background-color: #2b2b2b; border: 1px solid #444; color: #fff; padding: 12px; }
        .form-control:focus { background-color: #333; border-color: #1dd1a1; color: #fff; box-shadow: none; }

        .category-list { list-style: none; padding: 0; margin: 0; }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.02);
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            border: 1px solid transparent;
            transition: all 0.3s;
        }
        .category-item:hover {
            background: rgba(29, 209, 161, 0.05);
            border-color: rgba(29, 209, 161, 0.3);
            transform: translateX(5px);
        }
        .cat-icon {
            width: 35px; height: 35px;
            background: rgba(29, 209, 161, 0.1);
            color: #1dd1a1;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .btn-delete {
            color: #aaa;
            width: 30px; height: 30px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            transition: 0.2s;
        }
        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
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
            <h2 class="fw-bold text-white m-0">Manage Categories</h2>
            <a href="admin_dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
        </div>

        <?php if($message): ?><div class="alert alert-success bg-dark border-success text-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger bg-dark border-danger text-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="glass-card h-100">
                    <h5 class="text-white mb-4"><i class="fas fa-plus-circle me-2 text-teal-400"></i>Add New</h5>
                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label text-muted">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Summer Collection" required>
                            <div class="form-text text-secondary">This will appear in the shop filters.</div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold" style="background: #1dd1a1; border: none; color: black; padding: 10px;">
                            Add Category
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="text-white m-0">Existing Categories</h5>
                        <span class="badge bg-dark border border-secondary"><?php echo count($categories); ?> Total</span>
                    </div>
                    
                    <div class="category-list">
                        <?php if(count($categories) > 0): ?>
                            <?php foreach($categories as $cat): ?>
                            <div class="category-item">
                                <div class="d-flex align-items-center">
                                    <div class="cat-icon"><i class="fas fa-tag"></i></div>
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                </div>
                                <a href="manage_categories.php?delete=<?php echo $cat['category_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this category?');" title="Delete">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted py-4">No categories found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>