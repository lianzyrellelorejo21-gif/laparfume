<?php
session_start();
require_once '../config/database.php';

// 1. Security Check
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 0;

// 2. Fetch Order Details (Verify ownership)
$stmt = $pdo->prepare("
    SELECT o.*, c.full_name, c.email, c.phone, c.address 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.customer_id 
    WHERE o.order_id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or belongs to someone else
if (!$order) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h3>Receipt not found or access denied.</h3></div>");
}

// 3. Fetch Order Items
$stmtItems = $pdo->prepare("
    SELECT oi.*, p.product_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $order_id; ?> - LaParfume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        body { background: #555; font-family: 'Poppins', sans-serif; padding: 30px 0; }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            border-radius: 8px;
        }
        
        /* Header Styling */
        .brand-logo { font-size: 2rem; font-weight: 800; color: #000; letter-spacing: -1px; }
        .brand-logo span { color: #1dd1a1; }
        
        .invoice-details { text-align: right; }
        .invoice-details h5 { font-weight: bold; margin-bottom: 5px; }
        .status-badge { 
            display: inline-block; 
            padding: 5px 10px; 
            background: #d1e7dd; 
            color: #0f5132; 
            border-radius: 4px; 
            font-size: 0.8rem; 
            font-weight: bold; 
            text-transform: uppercase; 
        }

        /* Table Styling */
        .table-custom th { background-color: #f8f9fa; text-transform: uppercase; font-size: 0.85rem; color: #666; }
        .total-row td { background-color: #1dd1a1 !important; color: #000; font-weight: bold; font-size: 1.1rem; }
        
        /* Footer */
        .footer-note { font-size: 0.85rem; color: #777; margin-top: 40px; border-top: 1px solid #eee; padding-top: 20px; text-align: center; }

        /* Print Specifics */
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; max-width: 100%; padding: 20px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="receipt-container" id="receipt-content">
        
        <div class="row mb-5">
            <div class="col-6">
                <div class="brand-logo">LA<span>Parfume</span></div>
                <div class="text-muted small mt-2">
                    Official Receipt<br>
                    maiah@laparfume.com<br>
                    www.laparfume.atwebpages.com
                </div>
            </div>
            <div class="col-6 invoice-details">
                <h5>ORDER #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></h5>
                <div class="text-muted small mb-2">Date: <?php echo date('F d, Y', strtotime($order['order_date'])); ?></div>
                <span class="status-badge"><?php echo $order['status']; ?></span>
            </div>
        </div>

        <div class="row mb-5">
            <div class="col-6">
                <h6 class="text-uppercase text-muted small fw-bold mb-3">Billed To</h6>
                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($order['full_name']); ?></h5>
                <div class="text-muted small">
                    <?php echo htmlspecialchars($order['address']); ?><br>
                    <?php echo htmlspecialchars($order['phone']); ?><br>
                    <?php echo htmlspecialchars($order['email']); ?>
                </div>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-uppercase text-muted small fw-bold mb-3">Payment Info</h6>
                <div class="fw-bold"><?php echo htmlspecialchars($order['payment_method'] ?? 'Cash on Delivery'); ?></div>
            </div>
        </div>

        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th scope="col" style="width: 50%;">Item Description</th>
                    <th scope="col" class="text-center">Qty</th>
                    <th scope="col" class="text-end">Unit Price</th>
                    <th scope="col" class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-end">₱<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr class="total-row">
                    <td colspan="3" class="text-end border-0">GRAND TOTAL</td>
                    <td class="text-end border-0">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer-note">
            Thank you for shopping with LaParfume! We hope you enjoy your purchase.<br>
        </div>
    </div>

    <div class="text-center mt-4 mb-5 no-print d-flex justify-content-center gap-2">
        <button onclick="window.print()" class="btn btn-outline-light">
            <i class="fas fa-print me-2"></i> Print
        </button>
        <button id="downloadPdf" class="btn btn-success fw-bold" style="background-color: #1dd1a1; border: none; color: black;">
            <i class="fas fa-download me-2"></i> Download PDF
        </button>
        <button onclick="window.close()" class="btn btn-dark">Close</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <script>
        document.getElementById('downloadPdf').addEventListener('click', function () {
            var element = document.getElementById('receipt-content');
            var opt = {
                margin:       0.5,
                filename:     'LaParfume_Receipt_#<?php echo $order_id; ?>.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        });
    </script>

</body>
</html>