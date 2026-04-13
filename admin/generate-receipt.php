<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get booking details
$booking_type = $_GET['type'] ?? 'room';
$booking_id = $_GET['id'] ?? 0;

if (!$booking_id) {
    header("Location: bookings.php");
    exit();
}

// Fetch booking details based on type
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

// Generate receipt number
$receipt_number = 'RCP-' . date('Ymd') . '-' . str_pad($booking_id, 5, '0', STR_PAD_LEFT);

// Handle receipt generation and download
if (isset($_GET['download']) && $_GET['download'] == 'pdf') {
    // For PDF download, you would typically use a library like DOMPDF or TCPDF
    // Since we don't have that, we'll create an HTML receipt and use browser print
    header("Location: receipt-print.php?type=$booking_type&id=$booking_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $booking['booking_number']; ?> - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
                padding: 20px;
            }
            .receipt-container {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
        body {
            background: #f3f4f6;
            font-family: 'Inter', sans-serif;
        }
        .receipt-container {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .header-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem;
        }
        .gold-text {
            color: #C9A45A;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header-gradient">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">RECEIPT</h1>
                    <p class="text-white/80 mt-1"><?php echo $receipt_number; ?></p>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($settings['hotel_name'] ?? 'Fresh Home & Suite'); ?></h2>
                    <p class="text-white/80 text-sm"><?php echo htmlspecialchars($settings['hotel_address'] ?? ''); ?></p>
                    <p class="text-white/80 text-sm">Tel: <?php echo htmlspecialchars($settings['hotel_phone'] ?? ''); ?></p>
                    <p class="text-white/80 text-sm">Email: <?php echo htmlspecialchars($settings['hotel_email'] ?? ''); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Receipt Body -->
        <div class="p-8">
            <!-- Receipt Info -->
            <div class="flex justify-between mb-8 pb-8 border-b border-gray-200">
                <div>
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">RECEIPT TO:</h3>
                    <p class="font-bold text-lg"><?php echo htmlspecialchars($booking['full_name']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($booking['email']); ?></p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($booking['phone']); ?></p>
                </div>
                <div class="text-right">
                    <h3 class="text-sm font-semibold text-gray-500 mb-2">RECEIPT DETAILS:</h3>
                    <p><span class="font-semibold">Receipt No:</span> <?php echo $receipt_number; ?></p>
                    <p><span class="font-semibold">Date:</span> <?php echo date('F d, Y h:i A'); ?></p>
                    <p><span class="font-semibold">Booking #:</span> <?php echo $booking['booking_number']; ?></p>
                </div>
            </div>
            
            <!-- Booking Details -->
            <table class="w-full mb-8">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left py-3 px-4 font-semibold text-gray-600">Description</th>
                        <th class="text-center py-3 px-4 font-semibold text-gray-600">Duration</th>
                        <th class="text-right py-3 px-4 font-semibold text-gray-600">Rate</th>
                        <th class="text-right py-3 px-4 font-semibold text-gray-600">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($booking_type == 'room'): 
                        $nights = max(1, (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / (60 * 60 * 24));
                        $base_price = $booking['total_amount'] / $nights;
                    ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-4 px-4">
                            <p class="font-semibold">Room Booking - <?php echo $booking['room_type']; ?></p>
                            <p class="text-sm text-gray-500">Room <?php echo $booking['room_number']; ?></p>
                            <p class="text-sm text-gray-500">Check In: <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                            <p class="text-sm text-gray-500">Check Out: <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                        </td>
                        <td class="text-center py-4 px-4"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></td>
                        <td class="text-right py-4 px-4"><?php echo formatCurrency($base_price); ?></td>
                        <td class="text-right py-4 px-4 font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                    </tr>
                    <?php else: 
                        $hours = max(1, (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600);
                    ?>
                    <tr class="border-b border-gray-200">
                        <td class="py-4 px-4">
                            <p class="font-semibold">Hall Booking - <?php echo $booking['hall_name']; ?></p>
                            <p class="text-sm text-gray-500">Date: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                            <p class="text-sm text-gray-500">Time: <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></p>
                        </td>
                        <td class="text-center py-4 px-4"><?php echo $hours; ?> hour<?php echo $hours > 1 ? 's' : ''; ?></td>
                        <td class="text-right py-4 px-4"><?php echo formatCurrency($booking['price_per_hour']); ?></td>
                        <td class="text-right py-4 px-4 font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    
                    <!-- Additional Services would go here if they exist -->
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="flex justify-end mb-8">
                <div class="w-64">
                    <div class="flex justify-between py-2">
                        <span class="font-semibold">Subtotal:</span>
                        <span><?php echo formatCurrency($booking['total_amount']); ?></span>
                    </div>
                    <?php if(!empty($booking['tax_amount']) && $booking['tax_amount'] > 0): ?>
                    <div class="flex justify-between py-2">
                        <span class="font-semibold">Tax:</span>
                        <span><?php echo formatCurrency($booking['tax_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                    <div class="flex justify-between py-2">
                        <span class="font-semibold">Discount:</span>
                        <span>-<?php echo formatCurrency($booking['discount_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between py-3 border-t-2 border-gray-300 text-lg font-bold">
                        <span>Total:</span>
                        <span class="gold-text"><?php echo formatCurrency($booking['total_amount']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="font-bold text-lg mb-3">Payment Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Payment Method</p>
                        <p class="font-semibold"><?php echo ucwords(str_replace('_', ' ', $booking['payment_method'] ?? 'Not specified')); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Payment Status</p>
                        <p class="font-semibold <?php echo ($booking['payment_status'] ?? 'pending') == 'verified' ? 'text-green-600' : 'text-yellow-600'; ?>">
                            <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                        </p>
                    </div>
                    <?php if(!empty($booking['payment_date'])): ?>
                    <div>
                        <p class="text-gray-500 text-sm">Payment Date</p>
                        <p class="font-semibold"><?php echo date('M d, Y h:i A', strtotime($booking['payment_date'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="text-center text-gray-500 text-sm border-t border-gray-200 pt-6">
                <p class="mb-2">Thank you for choosing <?php echo htmlspecialchars($settings['hotel_name'] ?? 'Fresh Home & Suite'); ?>!</p>
                <p>This is a computer generated receipt. No signature required.</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="bg-gray-50 rounded-b-2xl p-6 flex justify-end gap-4 no-print">
            <button onclick="window.print()" class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition flex items-center">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
            <a href="booking-details.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" 
               class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Booking
            </a>
        </div>
    </div>
    
    <script>
    // Auto-trigger print dialog when page loads? Uncomment if desired
    // window.onload = function() {
    //     window.print();
    // }
    </script>
</body>
</html>