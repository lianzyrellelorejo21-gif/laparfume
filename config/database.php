<?php
// config/database.php
// Database Configuration for LaParfume

define('DB_HOST', 'trolley.proxy.rlwy.net');
define('DB_NAME', 'railway');
define('DB_USER', 'railway');
define('DB_PASS', 'MWQqxGxtsltcCjGnSAyYYzYhlOdzaweL');
define('DB_CHARSET', 'utf8mb4');

// Create PDO connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        redirect('login.php');
    }
}

function requireSeller() {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller') {
        redirect('login.php');
    }
}

function formatPrice($price) {
    return '₱' . number_format($price, 2);
}

function getCartCount() {
    global $pdo;
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
        return 0;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
}

function getWishlistCount() {
    global $pdo;
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
        return 0;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE customer_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetchColumn();
}

if (!function_exists('logActivity')) {
    function logActivity($pdo, $type, $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO activity_logs (log_type, message) VALUES (?, ?)");
            $stmt->execute([$type, $message]);
        } catch (Exception $e) {
            // Silent fail so it doesn't break the user experience
        }
    }
}
?>
