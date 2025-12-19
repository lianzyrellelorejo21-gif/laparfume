<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

// Fetch All Orders from the system
$sql = "SELECT o.*, c.full_name 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        ORDER BY o.order_date DESC";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Orders - LaParfume</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
    /* 1. LAYOUT RESET */
    body { 
        background-color: #000; 
        color: #fff; 
        font-family: 'Poppins', sans-serif; 
        overflow-x: hidden; 
        min-height: 100vh;
    }

    /* 2. SIDEBAR FIXES (Keeps Logout at Bottom) */
    .sidebar { 
        width: 260px; 
        background-color: #111; /* Consistent Dark Background */
        border-right: 1px solid #333; 
        position: fixed; 
        top: 0; 
        left: 0; 
        height: 100vh; /* FORCE FULL SCREEN HEIGHT */
        padding: 20px; 
        z-index: 1000;
        
        /* Flexbox is crucial for the logout button positioning */
        display: flex; 
        flex-direction: column; 
    }

    .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }

    /* 3. MAIN CONTENT OFFSET */
    .main-content { 
        margin-left: 260px; 
        padding: 30px; 
    }

    /* 4. NAV LINKS (Bright Green Active State) */
    .nav-link { 
        color: #aaa; 
        padding: 12px 15px; 
        border-radius: 8px; 
        margin-bottom: 5px; 
        text-decoration: none; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        transition: all 0.3s ease;
    }

    /* This makes the active link look exactly like the Dashboard */
    .nav-link:hover, .nav-link.active { 
        background-color: #1dd1a1; /* Solid Bright Green */
        color: #000;               /* Black Text */
        font-weight: 600;
    }

    /* 5. LOGOUT BUTTON POSITIONING */
    /* This pushes the div to the very bottom of the flex container */
    .sidebar .mt-auto {
        margin-top: auto !important;
    }

    /* 6. TABLE STYLES (Glass Look) */
    .table-custom { 
        background: #161616; 
        border-radius: 12px; 
        overflow: hidden; 
        width: 100%; 
    }
    .table-custom th { 
        background: #222; 
        color: #fff; 
        border: none; 
        padding: 15px; 
    }
    .table-custom td { 
        background: transparent; 
        color: #ccc; 
        border-bottom: 1px solid #333; 
        padding: 15px; 
        vertical-align: middle; 
    }
    
    /* 7. GLOW BLOBS (Optional) */
    .glow-blob { 
        position: absolute; width: 600px; height: 600px; 
        background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); 
        border-radius: 50%; pointer-events: none; z-index: -1; 
    }
    .blob-1 { top: -200px; left: -200px; } 
    .blob-2 { bottom: -200px; right: -200px; }
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
        
        <a href="admin_orders.php" class="nav-link active">
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
        <h2 class="fw-bold mb-4">Global Order History</h2>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($orders) > 0): ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="text-white fw-bold">#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td class="text-teal-400" style="color: #1dd1a1;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <?php 
                                    $badge = 'bg-secondary';
                                    if($order['status'] == 'Pending') $badge = 'bg-warning text-dark';
                                    if($order['status'] == 'Processing') $badge = 'bg-info text-dark';
                                    if($order['status'] == 'Delivered') $badge = 'bg-success';
                                    if($order['status'] == 'Cancelled') $badge = 'bg-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?>"><?php echo $order['status']; ?></span>
                            </td>
                            <td>
                                <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-light" title="View Details">
                                  <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No orders placed yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>