<?php
session_start();
require_once '../config/database.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

// --- HANDLE DELETE MESSAGE ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM messages WHERE message_id = ?")->execute([$id]);
    header("Location: messages.php"); exit();
}

// --- FETCH MESSAGES FOR ADMIN ---
// We select messages where recipient_type is 'Admin'
$stmt = $pdo->query("SELECT * FROM messages WHERE recipient_type = 'Admin' ORDER BY created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inbox - LaParfume Admin</title>
    
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
        
        /* Message Cards */
        .message-card { background: #161616; border: 1px solid #333; border-radius: 12px; padding: 20px; margin-bottom: 20px; transition: 0.3s; }
        .message-card:hover { border-color: #1dd1a1; box-shadow: 0 0 15px rgba(29,209,161,0.1); }
        .sender-initial { width: 45px; height: 45px; background: rgba(29, 209, 161, 0.2); color: #1dd1a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; margin-right: 15px; }
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

        <a href="manage_withdrawals.php" class="nav-link">
            <i class="fas fa-wallet"></i> Withdrawals
        </a>

        <a href="messages.php" class="nav-link active">
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
        <h2 class="fw-bold mb-4">Admin Inbox</h2>
        
        <?php if (empty($messages)): ?>
            <div class="text-center py-5 text-muted">
                <i class="far fa-envelope-open fa-3x mb-3 opacity-50"></i>
                <h4>No messages found.</h4>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($messages as $msg): ?>
                <div class="col-12">
                    <div class="message-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="sender-initial">
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h5 class="mb-0 text-white fw-bold"><?php echo htmlspecialchars($msg['sender_name']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($msg['sender_email']); ?></small>
                                </div>
                            </div>
                            <small class="text-secondary">
                                <i class="far fa-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </small>
                        </div>
                        
                        <h6 class="text-teal-400 mb-2">Subject: <?php echo htmlspecialchars($msg['subject']); ?></h6>
                        <p class="text-light opacity-75 bg-dark p-3 rounded border border-secondary mb-3">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </p>
                        
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="mailto:<?php echo $msg['sender_email']; ?>?subject=Re: <?php echo $msg['subject']; ?>" class="btn btn-sm btn-outline-light">
                                <i class="fas fa-reply me-1"></i> Reply via Email
                            </a>
                            <a href="messages.php?delete=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this message?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>