<?php
$page_title = 'Booking Confirmation';
require_once 'config/config.php';
require_once 'includes/header.php';

$booking_number = $_GET['booking'] ?? '';

// Try to find booking in room bookings
$stmt = $pdo->prepare("
    SELECT rb.*, r.room_type, g.full_name, g.email, g.phone, 'room' as booking_type
    FROM room_bookings rb
    JOIN rooms r ON rb.room_id = r.id
    JOIN guests g ON rb.guest_id = g.id
    WHERE rb.booking_number = ?
");
$stmt->execute([$booking_number]);
$booking = $stmt->fetch();

// If not found, check hall bookings
if (!$booking) {
    $stmt = $pdo->prepare("
        SELECT hb.*, h.name as hall_name, g.full_name, g.email, g.phone, 'hall' as booking_type
        FROM hall_bookings hb
        JOIN hall h ON hb.hall_id = h.id
        JOIN guests g ON hb.guest_id = g.id
        WHERE hb.booking_number = ?
    ");
    $stmt->execute([$booking_number]);
    $booking = $stmt->fetch();
}

if (!$booking) {
    redirect('index.php');
}

// Get bank accounts
$bank_accounts = getBankAccounts($pdo);
?>

<div class="max-w-3xl mx-auto">
    <!-- Success Animation -->
    <div class="text-center mb-8" data-aos="zoom-in">
        <div class="inline-block p-6 bg-green-500 rounded-full mb-4">
            <i class="fas fa-check-circle text-white text-6xl"></i>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">Booking Confirmed!</h1>
        <p class="text-xl text-white/80">Your booking has been successfully received</p>
    </div>
    
    <!-- Booking Details Card -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl overflow-hidden mb-8" data-aos="fade-up">
        <div class="p-8">
            <div class="text-center mb-6">
                <span class="text-2xl font-bold text-white">Booking #<?php echo $booking['booking_number']; ?></span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Guest Information</h3>
                    <div class="space-y-2 text-white/80">
                        <p><span class="font-medium">Name:</span> <?php echo htmlspecialchars($booking['full_name']); ?></p>
                        <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($booking['email']); ?></p>
                        <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($booking['phone']); ?></p>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4">Booking Details</h3>
                    <div class="space-y-2 text-white/80">
                        <?php if($booking['booking_type'] == 'room'): ?>
                        <p><span class="font-medium">Room:</span> <?php echo $booking['room_type']; ?></p>
                        <p><span class="font-medium">Check-in:</span> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></p>
                        <p><span class="font-medium">Check-out:</span> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></p>
                        <p><span class="font-medium">Guests:</span> <?php echo $booking['total_guests']; ?> (<?php echo $booking['adults']; ?> Adults, <?php echo $booking['children']; ?> Children)</p>
                        <?php else: ?>
                        <p><span class="font-medium">Hall:</span> <?php echo $booking['hall_name']; ?></p>
                        <p><span class="font-medium">Event:</span> <?php echo $booking['event_type']; ?></p>
                        <p><span class="font-medium">Date:</span> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                        <p><span class="font-medium">Time:</span> <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-white/20 mt-6 pt-6">
                <div class="flex justify-between items-center">
                    <span class="text-white/80">Total Amount:</span>
                    <span class="text-3xl font-bold text-yellow-300"><?php echo formatCurrency($booking['total_amount']); ?></span>
                </div>
                
                <div class="mt-4">
                    <span class="inline-block px-4 py-2 rounded-full 
                        <?php 
                        if($booking['payment_status'] == 'verified') {
                            echo 'bg-green-500';
                        } elseif($booking['payment_status'] == 'receipt_uploaded') {
                            echo 'bg-blue-500';
                        } else {
                            echo 'bg-yellow-500';
                        }
                        ?> 
                        text-white font-semibold">
                        Payment Status: <?php echo ucfirst(str_replace('_', ' ', $booking['payment_status'])); ?>
                    </span>
                </div>
                
                <?php if($booking['payment_method'] == 'bank_transfer' && $booking['payment_status'] == 'pending'): ?>
                <div class="mt-6 p-4 bg-blue-500/20 rounded-lg">
                    <h4 class="font-semibold text-white mb-3">📋 Payment Instructions</h4>
                    
                    <div class="space-y-3 mb-4">
                        <?php foreach($bank_accounts as $account): ?>
                        <div class="bg-white/10 p-3 rounded">
                            <p class="text-white font-bold"><?php echo $account['bank_name']; ?></p>
                            <p class="text-white/80 text-sm">Account Name: <?php echo $account['account_name']; ?></p>
                            <p class="text-white/80 text-sm">Account Number: <span class="font-mono font-bold text-yellow-300"><?php echo $account['account_number']; ?></span></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="text-white/90 mb-3">After making payment, please upload your receipt:</p>
                    
                    <form action="upload-receipt.php" method="POST" enctype="multipart/form-data" class="space-y-3">
                        <input type="hidden" name="booking_type" value="<?php echo $booking['booking_type']; ?>">
                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                        
                        <div>
                            <input type="file" name="receipt" accept="image/*" required
                                   class="w-full px-4 py-2 bg-white/10 text-white rounded-lg border border-white/20">
                        </div>
                        
                        <button type="submit" name="upload_receipt"
                                class="w-full bg-yellow-500 text-white py-2 rounded-lg hover:bg-yellow-600 transition">
                            <i class="fas fa-upload mr-2"></i> Upload Receipt
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <?php if($booking['payment_status'] == 'receipt_uploaded'): ?>
                <div class="mt-6 p-4 bg-green-500/20 rounded-lg text-center">
                    <i class="fas fa-check-circle text-4xl text-green-400 mb-2"></i>
                    <p class="text-white font-semibold">Receipt Uploaded Successfully!</p>
                    <p class="text-white/70 text-sm">Your receipt is pending verification. We'll notify you once confirmed.</p>
                </div>
                <?php endif; ?>
                
                <?php if($booking['payment_status'] == 'verified'): ?>
                <div class="mt-6 p-4 bg-green-500/20 rounded-lg text-center">
                    <i class="fas fa-check-circle text-4xl text-green-400 mb-2"></i>
                    <p class="text-white font-semibold">Payment Verified!</p>
                    <p class="text-white/70 text-sm">Your booking is confirmed. We look forward to welcoming you!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-4 justify-center" data-aos="fade-up">
        <a href="my-account.php" class="bg-yellow-500 text-white px-8 py-3 rounded-lg hover:bg-yellow-600 transition text-center">
            <i class="fas fa-user mr-2"></i> View My Bookings
        </a>
        <a href="index.php" class="bg-white/20 text-white px-8 py-3 rounded-lg hover:bg-white/30 transition text-center">
            <i class="fas fa-home mr-2"></i> Back to Home
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>