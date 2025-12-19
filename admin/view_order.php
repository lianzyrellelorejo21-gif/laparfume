<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php'); exit();
}

if (!isset($_GET['id'])) { header('Location: orders.php'); exit(); }
$order_id = $_GET['id'];

// Fetch Order Info
$stmt = $pdo->prepare("SELECT o.*, c.full_name, c.email, c.phone FROM orders o JOIN customers c ON o.customer_id = c.customer_id WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) { header('Location: orders.php'); exit(); }

// Fetch Payment Info
$stmtPay = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmtPay->execute([$order_id]);
$payment = $stmtPay->fetch(PDO::FETCH_ASSOC);

// Update Payment Status if Delivered
if ($order['status'] == 'Delivered' && $payment['payment_status'] != 'Completed') {
    $pdo->prepare("UPDATE payments SET payment_status = 'Completed' WHERE order_id = ?")->execute([$order_id]);
    $payment['payment_status'] = 'Completed';
}

// Fetch Order Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.product_name, p.image, s.business_name, s.full_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    JOIN sellers s ON oi.seller_id = s.seller_id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?php echo $order_id; ?> Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #000; color: #fff; font-family: 'Poppins', sans-serif; }
        
        .glow-blob { position: absolute; width: 600px; height: 600px; background: radial-gradient(circle, rgba(29,209,161,0.15) 0%, rgba(0,0,0,0) 70%); border-radius: 50%; pointer-events: none; z-index: -1; }
        .blob-1 { top: -200px; left: -200px; } .blob-2 { bottom: -200px; right: -200px; }

        /* Glass Invoice Card */
        .invoice-card {
            background: rgba(22, 22, 22, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 50px;
            max-width: 900px;
            margin: 40px auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        /* Badge Colors */
        .status-badge { font-size: 0.9rem; padding: 6px 15px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .bg-delivered { background: rgba(46, 204, 113, 0.2); color: #2ecc71; border: 1px solid #2ecc71; }
        .bg-pending { background: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }
        .bg-cancelled { background: rgba(231, 76, 60, 0.2); color: #e74c3c; border: 1px solid #e74c3c; }

        /* Table Styling - FORCED COLORS for Visibility */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-custom th { 
            color: #aaa !important; 
            border-bottom: 1px solid #333; 
            padding: 15px; 
            text-transform: uppercase; 
            font-size: 0.85rem; 
        }
        .table-custom td { 
            background: rgba(255, 255, 255, 0.03); 
            padding: 15px; 
            vertical-align: middle; 
            border: none; 
            color: #fff !important; /* Ensure text is white */
        }
        .table-custom tr td:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        .table-custom tr td:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }

        /* Labels */
        .info-label { color: #aaa; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 5px; font-weight: 600; }
        .info-value { font-size: 1.1rem; font-weight: 500; color: #fff; }
        .text-muted-light { color: #ccc !important; } /* Custom lighter gray for visibility */

        /* PRINT STYLES - FIXED */
        @media print {
            @page {
                size: auto;   /* auto is the initial value */
                margin: 0mm;  /* this affects the margin in the printer settings */
            }
            body { 
                background: #fff; 
                color: #ffffffff; 
                margin: 20mm; /* Add margin to body for print */
            }
            .container {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .invoice-card { 
                box-shadow: none; 
                border: none; 
                background: #fff; 
                padding: 0; 
                margin: 0; 
                width: 100%;
                max-width: 100%;
            }
            .glow-blob, .no-print { display: none !important; }
            
            /* Table Fixes for Print */
            .table-custom {
                width: 100%;
                border-collapse: collapse;
                border-spacing: 0;
            }
            .table-custom th { 
                color: #000 !important; 
                border-bottom: 2px solid #000; 
                padding: 10px;
            }
            .table-custom td { 
                background: #fff !important; 
                border-bottom: 1px solid #ddd; 
                color: #000 !important;
                padding: 10px;
            }
            .table-custom tr td:first-child, 
            .table-custom tr td:last-child { 
                border-radius: 0; 
            }

            /* Color overrides for print */
            .text-teal-400, .text-white, h3, h5, p { color: #000 !important; }
            .bg-dark, .badge { 
                background-color: transparent !important; 
                border: 1px solid #000 !important; 
                color: #000 !important; 
            }
            
            /* Hide background colors and shadows to save ink */
            * {
                box-shadow: none !important;
                text-shadow: none !important;
                background: transparent !important;
            }
            
            /* Ensure images print if needed, or hide them */
            img { display: block; } 
        }
    </style>
</head>
<body>

    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>

    <div class="container">
        <div class="invoice-card">
            <div class="d-flex justify-content-between align-items-center mb-5 no-print">
                <a href="admin_orders.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-2"></i> Back to Orders</a>
                <button onclick="window.print()" class="btn btn-success fw-bold" style="background: #1dd1a1; border:none; color:black;">
                    <i class="fas fa-print me-2"></i> Print Invoice
                </button>
            </div>

            <div class="row mb-5 border-bottom border-secondary pb-4">
                <div class="col-6">
                    <h3 class="fw-bold text-white">LA<span style="color: #1dd1a1;">Parfume</span></h3>
                    <p class="text-muted-light mb-0">Order Details & Receipt</p>
                </div>
                <div class="col-6 text-end">
                    <div class="info-label">Order ID</div>
                    <h3 class="fw-bold text-white">#<?php echo $order_id; ?></h3>
                    <div class="mt-2">
                        <?php 
                            $statusClass = 'bg-secondary';
                            if ($order['status'] == 'Delivered') $statusClass = 'bg-delivered';
                            elseif ($order['status'] == 'Pending') $statusClass = 'bg-pending';
                            elseif ($order['status'] == 'Cancelled') $statusClass = 'bg-cancelled';
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $order['status']; ?></span>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.03);">
                        <div class="info-label mb-3"><i class="fas fa-user me-2"></i> Customer</div>
                        <h5 class="text-white mb-1"><?php echo htmlspecialchars($order['full_name']); ?></h5>
                        <p class="text-muted-light mb-1"><?php echo htmlspecialchars($order['email']); ?></p>
                        <p class="text-muted-light mb-0"><?php echo htmlspecialchars($order['contact_phone']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded" style="background: rgba(255,255,255,0.03);">
                        <div class="info-label mb-3"><i class="fas fa-map-marker-alt me-2"></i> Shipping To</div>
                        <p class="text-white mb-0" style="white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        <?php if($order['notes']): ?>
                            <div class="mt-3 text-warning small"><i class="fas fa-sticky-note me-1"></i> Note: <?php echo htmlspecialchars($order['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Store</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="../images/<?php echo $item['image']; ?>" style="width:40px; height:40px; object-fit:cover; margin-right:15px; border-radius:6px;">
                                    <span class="text-white fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary text-white">
                                    <?php echo htmlspecialchars(!empty($item['business_name']) ? $item['business_name'] : $item['full_name']); ?>
                                </span>
                            </td>
                            <td class="text-center text-white"><?php echo $item['quantity']; ?></td>
                            <td class="text-end text-muted-light">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end text-white fw-bold">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="p-3 rounded border border-secondary d-flex justify-content-between align-items-center" style="background: rgba(255,255,255,0.02);">
                        <div>
                            <div class="info-label">Payment Method</div>
                            <div class="text-white fw-bold"><i class="fas fa-credit-card me-2"></i> <?php echo $payment['payment_method'] ?? 'N/A'; ?></div>
                        </div>
                        <div>
                            <div class="info-label text-end">Status</div>
                            <span class="badge <?php echo ($payment['payment_status']=='Completed')?'bg-success':'bg-warning text-dark'; ?>">
                                <?php echo $payment['payment_status'] ?? 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-end">
                        <div class="d-flex justify-content-between mb-2 text-muted-light">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($order['total_amount'] - 50, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted-light">
                            <span>Shipping Fee</span>
                            <span>₱50.00</span>
                        </div>
                        <div class="d-flex justify-content-between border-top border-secondary pt-3">
                            <span class="h5 text-white">Grand Total</span>
                            <span class="h4 fw-bold" style="color: #1dd1a1;">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>