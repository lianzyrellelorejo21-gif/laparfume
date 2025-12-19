<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php"); exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; $error = '';

// --- 1. HANDLE PROFILE PHOTO UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    if ($file['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $new_name = "user_" . $user_id . "_" . time() . "." . $ext;
            $destination = '../images/users/' . $new_name;
            if (!file_exists('../images/users')) { mkdir('../images/users', 0777, true); }
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("UPDATE customers SET profile_image = ? WHERE customer_id = ?");
                $stmt->execute([$new_name, $user_id]);
                $message = "Profile photo updated!";
            } else { $error = "Failed to move uploaded file."; }
        } else { $error = "Only JPG, PNG, and WEBP files allowed."; }
    }
}

// --- 2. HANDLE INFO UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));
    try {
        $stmt = $pdo->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE customer_id = ?");
        if ($stmt->execute([$full_name, $phone, $address, $user_id])) {
            $message = "Profile updated successfully!";
            $_SESSION['user_name'] = $full_name;
        }
    } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
}

// --- 3. DISPLAY SESSION MESSAGES ---
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch User
$user = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

// Fetch Orders
$orderStmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC");
$orderStmt->execute([$user_id]); 
$orders = $orderStmt->fetchAll();

// Fetch Notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notifStmt->execute([$user_id]);
$notifications = $notifStmt->fetchAll();
$unread_count = 0;
foreach($notifications as $n) { if($n['is_read'] == 0) $unread_count++; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - LaParfume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/login.css">
    <style>
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; }
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }
        .profile-header { background: rgba(22, 22, 22, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 40px; display: flex; align-items: center; gap: 30px; margin-bottom: 30px; }
        .profile-avatar-container { position: relative; width: 100px; height: 100px; cursor: pointer; }
        .profile-avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 2px solid #1dd1a1; background-color: #333; }
        .avatar-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); border-radius: 50%; display: flex; justify-content: center; align-items: center; opacity: 0; transition: 0.3s; }
        .profile-avatar-container:hover .avatar-overlay { opacity: 1; }
        .nav-tabs { border-bottom: 1px solid #333; margin-bottom: 20px; }
        .nav-link { color: #aaa; border: none; padding: 10px 20px; font-weight: 500; }
        .nav-link.active { background-color: transparent; color: #1dd1a1; border-bottom: 2px solid #1dd1a1; }
        .nav-link:hover { color: #fff; }
        .account-card { background: rgba(22, 22, 22, 0.6); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 30px; }
        .form-control { background-color: #111; border: 1px solid #333; color: #fff; padding: 12px; }
        .form-control:focus { background-color: #111; border-color: #1dd1a1; color: #fff; box-shadow: none; }
        .form-label { color: #ccc; font-size: 0.9rem; }
        .btn-save { background-color: #1dd1a1; color: #000; font-weight: 600; padding: 10px 25px; border: none; transition: 0.3s; }
        .btn-save:hover { background-color: #15a07c; box-shadow: 0 0 15px rgba(29, 209, 161, 0.4); }
        .btn-seller { background: transparent; border: 1px solid #1dd1a1; color: #1dd1a1; padding: 8px 20px; border-radius: 30px; text-decoration: none; transition: 0.3s; }
        .btn-seller:hover { background: #1dd1a1; color: #000; }
        /* Notif Styles */
        .notif-item { border-bottom: 1px solid #333; padding: 15px 0; }
        .notif-item:last-child { border-bottom: none; }
        .notif-unread { border-left: 3px solid #1dd1a1; padding-left: 15px; }
    </style>
</head>
<body>

    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">LA<span style="color:#1dd1a1;">Parfume</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="../shop.php">Shop</a></li>
                    <li class="nav-item"><a class="nav-link" href="../wishlist.php">Wishlist</a></li>
                    <li class="nav-item"><a class="nav-link" href="../contact.php">Contact</a></li>
                </ul>
                <div class="d-flex gap-3 align-items-center">
                    <a href="../wishlist.php" class="text-white" title="Wishlist"><i class="far fa-heart"></i></a>
                    <a href="../cart.php" class="text-white" title="Cart"><i class="fas fa-shopping-cart"></i></a>
                    <a href="../logout.php" class="text-danger" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($message): ?><div class="alert alert-success bg-dark border-success text-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger bg-dark border-danger text-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="profile-header">
            <form id="photoForm" method="POST" enctype="multipart/form-data">
                <input type="file" name="profile_photo" id="photoInput" style="display: none;" onchange="document.getElementById('photoForm').submit();">
                <div class="profile-avatar-container" onclick="document.getElementById('photoInput').click();">
                    <?php 
                        $imgSrc = !empty($user['profile_image']) && $user['profile_image'] != 'default.jpg' 
                                  ? "../images/users/" . $user['profile_image'] 
                                  : "https://via.placeholder.com/100/1dd1a1/000?text=" . strtoupper(substr($user['full_name'], 0, 1));
                    ?>
                    <img src="<?php echo $imgSrc; ?>" class="profile-avatar">
                    <div class="avatar-overlay"><i class="fas fa-camera text-white"></i></div>
                </div>
            </form>
            <div class="flex-grow-1">
                <h2 class="m-0 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="text-muted m-0"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <a href="become_seller.php" class="btn-seller"><i class="fas fa-store me-2"></i>Become a Seller</a>
        </div>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile">Profile</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#orders">My Orders</button></li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notifications" onclick="markNotificationsRead()">
                Notifications 
                <?php if($unread_count > 0): ?>
                    <span id="notif-badge" class="badge bg-danger rounded-pill ms-1"><?php echo $unread_count; ?></span>
                <?php endif; ?>
                </button>
            </li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#security">Security</button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="profile">
                <div class="account-card">
                    <h4 class="mb-4">Personal Information</h4>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                            <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></div>
                            <div class="col-12"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea></div>
                            <div class="col-12 mt-4"><button type="submit" name="update_profile" class="btn btn-save">Save Changes</button></div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tab-pane fade" id="orders">
                <div class="account-card">
                    <h4 class="mb-4">Order History</h4>
                    <?php if (count($orders) > 0): ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach($orders as $o): ?>
                                <?php 
                                    // --- CHANGED: JOIN SELLERS to get Business Name, REMOVED LIMIT ---
                                    $stmtItems = $pdo->prepare("
                                        SELECT p.image, p.product_name, s.business_name 
                                        FROM order_items oi 
                                        JOIN products p ON oi.product_id = p.product_id 
                                        LEFT JOIN sellers s ON oi.seller_id = s.seller_id
                                        WHERE oi.order_id = ?
                                    ");
                                    $stmtItems->execute([$o['order_id']]);
                                    $orderItems = $stmtItems->fetchAll();
                                    
                                    // Get Store Name (Assuming order is per-seller)
                                    $storeName = !empty($orderItems[0]['business_name']) ? $orderItems[0]['business_name'] : 'LaParfume Store';

                                    // --- STATUS COLOR LOGIC ---
                                    $statusClass = 'bg-secondary'; // Default Gray
                                    if ($o['status'] == 'Pending') $statusClass = 'bg-warning text-dark';
                                    elseif ($o['status'] == 'Processing') $statusClass = 'bg-info text-dark';
                                    elseif ($o['status'] == 'Shipped') $statusClass = 'bg-primary';
                                    elseif ($o['status'] == 'Delivered') $statusClass = 'bg-success';
                                    elseif ($o['status'] == 'Cancelled') $statusClass = 'bg-danger';
                                    // --------------------------
                                ?>
                                <div class="p-3 rounded border border-secondary" style="background: rgba(255,255,255,0.02);">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="text-teal-400 fw-bold mb-1"><i class="fas fa-store me-1"></i> <?php echo htmlspecialchars($storeName); ?></div>
                                            <span class="text-muted small">Order #<?php echo $o['order_id']; ?></span>
                                            <span class="text-white small ms-2"><i class="far fa-calendar-alt me-1"></i> <?php echo date('M d, Y', strtotime($o['order_date'])); ?></span>
                                        </div>
                                        
                                        <span class="badge <?php echo $statusClass; ?> px-3 py-1 rounded-pill"><?php echo $o['status']; ?></span>
                                    
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap mb-3">
                                        <?php foreach($orderItems as $item): ?>
                                            <img src="../images/<?php echo !empty($item['image']) ? $item['image'] : 'default.jpg'; ?>" 
                                                 title="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #333;">
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-end border-top border-secondary pt-3">
                                        <div><div class="text-muted small">Total Amount</div><div class="h5 m-0 text-teal-400 fw-bold" style="color: #1dd1a1;">₱<?php echo number_format($o['total_amount'], 2); ?></div></div>
                                        <div class="d-flex gap-2">
                                            
                                            <?php if($o['status'] === 'Pending'): ?>
                                                <form action="cancel_order.php" method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                    <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Order</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if($o['status'] == 'Delivered'): ?>
                                                <a href="print_receipt.php?order_id=<?php echo $o['order_id']; ?>" target="_blank" class="btn btn-sm btn-outline-light"><i class="fas fa-receipt me-1"></i> Receipt</a>
                                            <?php endif; ?>
                                            <a href="../track_order.php?order_id=<?php echo $o['order_id']; ?>" class="btn btn-sm fw-bold" style="background: rgba(29, 209, 161, 0.15); color: #1dd1a1; border: 1px solid #1dd1a1;">Track Order</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5"><i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i><p class="text-muted">You haven't placed any orders yet.</p><a href="../shop.php" class="btn btn-save mt-2">Start Shopping</a></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="notifications">
                <div class="account-card">
                    <h4 class="mb-4">Notifications</h4>
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach($notifications as $notif): ?>
                            <div class="notif-item <?php echo $notif['is_read'] == 0 ? 'notif-unread' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold text-white mb-1"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                    <small class="text-muted"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></small>
                                </div>
                                <p class="text-secondary mb-0 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No notifications yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="security">
                <div class="account-card">
                    <h4 class="mb-4">Change Password</h4>
                    <form><div class="mb-3"><label class="form-label">Current Password</label><input type="password" class="form-control"></div><div class="mb-3"><label class="form-label">New Password</label><input type="password" class="form-control"></div><button class="btn btn-save">Update Password</button></form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
    <script>
    function markNotificationsRead() {
        var badge = document.getElementById('notif-badge');
        if (badge) {
            badge.style.display = 'none';
        }
        fetch('mark_notifications_read.php')
            .then(response => response.json())
            .then(data => console.log('Notifications marked as read'))
            .catch(error => console.error('Error:', error));
    }
</script>
</body>
</html>