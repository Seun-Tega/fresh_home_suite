<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get booking details (same as above but with print-optimized layout)
$booking_type = $_GET['type'] ?? 'room';
$booking_id = $_GET['id'] ?? 0;

if (!$booking_id) {
    header("Location: bookings.php");
    exit();
}

// Fetch booking details (same query as above)
if ($booking_type == 'room') {
    $stmt = $pdo->prepare("
        SELECT rb.*, 
               r.room_type, r.room_number,
               g.full_name, g.email, g.phone, g.address
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        JOIN guests g ON rb.guest_id = g.id
        WHERE rb.id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT hb.*, 
               h.name as hall_name, h.price_per_hour,
               g.full_name, g.email, g.phone, g.address
        FROM hall_bookings hb
        JOIN hall h ON hb.hall_id = h.id
        JOIN guests g ON hb.guest_id = g.id
        WHERE hb.id = ?
    ");
}

$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: bookings.php");
    exit();
}

// Get site settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $booking['booking_number']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            background: white;
            padding: 20px;
            font-size: 12px;
        }
        .receipt {
            max-width: 300px;
            margin: 0 auto;
            border: 1px dashed #000;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
        }
        .receipt-info {
            margin-bottom: 10px;
        }
        .receipt-info p {
            display: flex;
            justify-content: space-between;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .items th, .items td {
            text-align: left;
            padding: 5px 0;
        }
        .items th {
            border-bottom: 1px solid #000;
        }
        .total {
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
            text-align: right;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 10px;
        }
        .line {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1><?php echo htmlspecialchars($settings['hotel_name'] ?? 'Fresh Home & Suite'); ?></h1>
            <p><?php echo htmlspecialchars($settings['hotel_address'] ?? ''); ?></p>
            <p>Tel: <?php echo htmlspecialchars($settings['hotel_phone'] ?? ''); ?></p>
            <p>Email: <?php echo htmlspecialchars($settings['hotel_email'] ?? ''); ?></p>
        </div>
        
        <div class="receipt-info">
            <p><span>Receipt No:</span> <span><?php echo $receipt_number; ?></span></p>
            <p><span>Date:</span> <span><?php echo date('Y-m-d H:i'); ?></span></p>
            <p><span>Booking #:</span> <span><?php echo $booking['booking_number']; ?></span></p>
        </div>
        
        <div class="line"></div>
        
        <p><strong>Bill To:</strong> <?php echo htmlspecialchars($booking['full_name']); ?></p>
        <p><?php echo htmlspecialchars($booking['phone']); ?></p>
        
        <div class="line"></div>
        
        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if($booking_type == 'room'): 
                    $nights = max(1, (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / (60 * 60 * 24));
                ?>
                <tr>
                    <td><?php echo $booking['room_type']; ?></td>
                    <td><?php echo $nights; ?></td>
                    <td><?php echo formatCurrency($booking['total_amount'] / $nights); ?></td>
                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                </tr>
                <?php else: 
                    $hours = max(1, (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600);
                ?>
                <tr>
                    <td><?php echo $booking['hall_name']; ?></td>
                    <td><?php echo $hours; ?></td>
                    <td><?php echo formatCurrency($booking['price_per_hour']); ?></td>
                    <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="total">
            <p>Total: <?php echo formatCurrency($booking['total_amount']); ?></p>
        </div>
        
        <div class="line"></div>
        
        <div class="footer">
            <p>Payment Method: <?php echo ucwords(str_replace('_', ' ', $booking['payment_method'] ?? 'Not specified')); ?></p>
            <p>Payment Status: <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?></p>
            <p>Thank you for your patronage!</p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>