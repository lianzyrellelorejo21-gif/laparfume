<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Update all unread notifications to read (1)
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode(['status' => 'success']);
}
?>