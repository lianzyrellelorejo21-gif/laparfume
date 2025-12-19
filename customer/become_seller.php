<?php
session_start();

// FIX 1: Add '../' to go up one level to find the config folder
require_once '../config/database.php';

// 1. Ensure user is logged in as a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    // FIX 2: Add '../' to redirect to login in the main folder
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Pre-fill data from session if available
$currentUserEmail = $_SESSION['user_email'] ?? '';
$currentUserName = $_SESSION['user_name'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $store_name = htmlspecialchars(trim($_POST['store_name'] ?? ''));
    $business_email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $address = htmlspecialchars(trim($_POST['address'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($store_name) || empty($business_email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($business_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT email FROM sellers WHERE email = ? UNION SELECT email FROM admins WHERE email = ?");
        $stmt->execute([$business_email, $business_email]);
        
        if ($stmt->fetch()) {
            $error = 'This email is already registered as a seller or admin.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $pdo->prepare("INSERT INTO sellers (full_name, email, password, phone, business_address, is_active, date_created) VALUES (?, ?, ?, ?, ?, 1, NOW())");
                
                if ($stmt->execute([$store_name, $business_email, $hashed_password, $phone, $address])) {
                    // FIX 3: Link back to main login page
                    $success = 'Application successful! You can now <a href="../login.php" class="text-teal-400 font-bold">Log In</a> as a seller.';
                } else {
                    $error = 'Could not register seller account. Please try again.';
                }
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - LaParfume</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FIX 4: Add '../' to find the assets folder from inside seller folder -->
    <link rel="stylesheet" href="../assets/login.css">
</head>
<body>
    
    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>

    <div class="top-banner">
        <!-- FIX 5: Link back to main shop page -->
        Flash Sale For Some Perfume And Free Express Delivery – OFF 50%! <a href="../shop.php">ShopNow</a>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <!-- FIX 6: Link back to main index -->
            <a class="navbar-brand" href="../index.php" class="fw-bold text-white">LA<span class="text-teal-400" style="color: #1dd1a1;">Parfume</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <!-- FIX 7: Update Nav Links with '../' -->
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <!-- Assuming profile is inside customer folder, we go back up then into customer -->
                    <li class="nav-item"><a class="nav-link" href="../customer/account.php">My Profile</a></li>
                </ul>
                
                <div class="d-flex align-items-center text-white">
                    <span class="me-3 d-none d-md-block">Hello, <?php echo htmlspecialchars($currentUserName); ?></span>
                    <!-- FIX 8: Logout link -->
                    <a href="../logout.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="signup-section">
        <div class="signup-card">
            <h1>Become a Seller</h1>
            <p class="signup-subtitle">Start selling your products on LaParfume</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php else: ?>
            
            <form method="POST" action="" id="sellerForm" autocomplete="off">
                
                <div class="mb-4">
                    <label class="form-label text-white-50 text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Store Name</label>
                    <input type="text" name="store_name" class="form-control"
                           placeholder="Enter your Store Name" required>
                </div>

                <div class="mb-4">
                    <label class="form-label text-white-50 text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Business Email</label>
                    <input type="email" name="email" class="form-control"
                           placeholder="Business Email Address" required value="<?php echo htmlspecialchars($currentUserEmail); ?>">
                    <div class="form-text text-white-50" style="font-size: 11px;">
                        * Using a different email from your customer account is recommended.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-white-50 text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Business Phone</label>
                    <input type="tel" name="phone" class="form-control"
                           placeholder="Business Phone Number">
                </div>

                <div class="mb-4">
                    <label class="form-label text-white-50 text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Business Address</label>
                    <textarea name="address" class="form-control" rows="2" 
                              placeholder="Store Location / Address" style="resize: none;"></textarea>
                </div>

                <div class="mb-4 password-wrapper">
                    <label class="form-label text-white-50 text-uppercase" style="font-size: 12px; letter-spacing: 1px;">Seller Password</label>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Create a password for your seller account" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'icon1')">
                        <i class="far fa-eye" id="icon1"></i>
                    </button>
                </div>

                <div class="mb-4 password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                           placeholder="Confirm Password" required minlength="6">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'icon2')">
                        <i class="far fa-eye" id="icon2"></i>
                    </button>
                </div>
                        
                <button type="submit" class="btn btn-signup" style="background-color: #f39c12; color: #000;">
                    <i class="fas fa-store me-2"></i> Register Store
                </button>
                
                <div class="text-center mt-3">
                    <!-- FIX 9: Cancel Link -->
                    <a href="../customer/account.php" class="text-white-50" style="text-decoration: none; font-size: 14px;">Cancel and go back</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; ITP - 7, Laparfume System</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>