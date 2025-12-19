<?php
session_start();
require_once '../config/database.php';

// 1. Check Login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $customer_id = $_SESSION['user_id'];

    // 2. Verify Order Ownership AND Status
    // We only allow cancellation if status is 'Pending'
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ? AND customer_id = ?");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && $order['status'] === 'Pending') {
        
        try {
            $pdo->beginTransaction();

            // 3. Restore Stock for each item in the order
            $stmt_items = $pdo->prepare("SELECT product_id, quantity, seller_id FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$order_id]);
            $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                // Return stock to product
                $update_stock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
                $update_stock->execute([$item['quantity'], $item['product_id']]);

                // Optional: Log this return in stock_logs so the seller knows why stock increased
                $log_stock = $pdo->prepare("INSERT INTO stock_logs (product_id, seller_id, quantity_added, date_added) VALUES (?, ?, ?, NOW())");
                $log_stock->execute([$item['product_id'], $item['seller_id'], $item['quantity']]);
            }

            // 4. Update Order Status to Cancelled
            $update_order = $pdo->prepare("UPDATE orders SET status = 'Cancelled', updated_date = NOW() WHERE order_id = ?");
            $update_order->execute([$order_id]);

            $pdo->commit();
            $_SESSION['success'] = "Order #$order_id has been successfully cancelled.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error cancelling order. Please contact support.";
        }

    } else {
        $_SESSION['error'] = "This order cannot be cancelled (it may have been processed already).";
    }
}

header('Location: account.php');
exit();
?>