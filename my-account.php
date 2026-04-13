<?php
$page_title = 'My Account';
require_once 'config/config.php';
require_once 'includes/header.php';

// Check if logged in
if (!isset($_SESSION['guest_id'])) {
    redirect('login.php');
}

$guest_id = $_SESSION['guest_id'];

// Get user bookings
$stmt = $pdo->prepare("
    SELECT rb.*, r.room_type 
    FROM room_bookings rb
    JOIN rooms r ON rb.room_id = r.id
    WHERE rb.guest_id = ?
    ORDER BY rb.created_at DESC
");
$stmt->execute([$guest_id]);
$room_bookings = $stmt->fetchAll();

// Get hall bookings
$stmt = $pdo->prepare("
    SELECT hb.*, h.name as hall_name
    FROM hall_bookings hb
    JOIN hall h ON hb.hall_id = h.id
    WHERE hb.guest_id = ?
    ORDER BY hb.created_at DESC
");
$stmt->execute([$guest_id]);
$hall_bookings = $stmt->fetchAll();

// Get user details
$stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
$stmt->execute([$guest_id]);
$guest = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    $stmt = $pdo->prepare("UPDATE guests SET full_name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$full_name, $phone, $address, $guest_id]);
    
    $_SESSION['guest_name'] = $full_name;
    $success = "Profile updated successfully!";
}

// Handle receipt upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_receipt'])) {
    $booking_id = $_POST['booking_id'];
    $booking_type = $_POST['booking_type'];
    
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == 0) {
        $upload_result = uploadFile($_FILES['receipt'], 'receipts');
        
        if ($upload_result['success']) {
            if ($booking_type == 'room') {
                $stmt = $pdo->prepare("UPDATE room_bookings SET receipt_path = ?, payment_status = 'receipt_uploaded', receipt_uploaded_at = NOW() WHERE id = ? AND guest_id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE hall_bookings SET receipt_path = ?, payment_status = 'receipt_uploaded', receipt_uploaded_at = NOW() WHERE id = ? AND guest_id = ?");
            }
            
            $stmt->execute([$upload_result['file_path'], $booking_id, $guest_id]);
            
            $success = "Receipt uploaded successfully! Awaiting verification.";
        } else {
            $error = $upload_result['message'];
        }
    }
}

// Get bank accounts
$bank_accounts = getBankAccounts($pdo);
?>

<div class="max-w-7xl mx-auto">
    <!-- Welcome Header -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 mb-8" data-aos="fade-down">
        <h1 class="text-3xl font-bold text-white mb-2">
            Welcome back, <?php echo htmlspecialchars($guest['full_name']); ?>!
        </h1>
        <p class="text-white/70">Manage your bookings and profile</p>
    </div>
    
    <?php if(isset($success)): ?>
    <div class="bg-green-500/20 border border-green-500 text-green-100 px-6 py-4 rounded-lg mb-8">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
    <div class="bg-red-500/20 border border-red-500 text-red-100 px-6 py-4 rounded-lg mb-8">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabs -->
    <div class="mb-8" data-aos="fade-up">
        <div class="flex flex-wrap border-b border-white/20">
            <button onclick="showTab('bookings')" 
                    class="tab-btn active px-6 py-3 text-white font-semibold border-b-2 border-yellow-500">
                My Bookings
            </button>
            <button onclick="showTab('profile')" 
                    class="tab-btn px-6 py-3 text-white/70 hover:text-white font-semibold">
                Profile Settings
            </button>
        </div>
    </div>
    
    <!-- Bookings Tab -->
    <div id="bookingsTab" class="tab-content">
        <!-- Room Bookings -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-8" data-aos="fade-up">
            <h2 class="text-2xl font-bold text-white mb-6">Room Bookings</h2>
            
            <?php if(count($room_bookings) > 0): ?>
            <div class="space-y-4">
                <?php foreach($room_bookings as $booking): ?>
                <div class="bg-white/5 rounded-xl p-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-lg font-bold text-white">#<?php echo $booking['booking_number']; ?></span>
                                <?php echo getStatusBadge($booking['booking_status']); ?>
                                <?php echo getStatusBadge($booking['payment_status']); ?>
                            </div>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Room:</span> <?php echo $booking['room_type']; ?>
                            </p>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Check-in:</span> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?>
                            </p>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Check-out:</span> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                            </p>
                            <p class="text-white/70">
                                <span class="font-semibold">Total:</span> 
                                <span class="text-yellow-300 font-bold"><?php echo formatCurrency($booking['total_amount']); ?></span>
                            </p>
                        </div>
                        
                        <?php if($booking['payment_method'] == 'bank_transfer' && $booking['payment_status'] == 'pending'): ?>
                        <button onclick="showReceiptModal('room', <?php echo $booking['id']; ?>)" 
                                class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition">
                            Upload Receipt
                        </button>
                        <?php endif; ?>
                        
                        <?php if($booking['payment_status'] == 'verified'): ?>
                        <span class="text-green-400">
                            <i class="fas fa-check-circle mr-1"></i> Payment Verified
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-white/70 text-center py-8">No room bookings yet.</p>
            <?php endif; ?>
        </div>
        
        <!-- Hall Bookings -->
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6" data-aos="fade-up">
            <h2 class="text-2xl font-bold text-white mb-6">Hall Bookings</h2>
            
            <?php if(count($hall_bookings) > 0): ?>
            <div class="space-y-4">
                <?php foreach($hall_bookings as $booking): ?>
                <div class="bg-white/5 rounded-xl p-6">
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-lg font-bold text-white">#<?php echo $booking['booking_number']; ?></span>
                                <?php echo getStatusBadge($booking['booking_status']); ?>
                                <?php echo getStatusBadge($booking['payment_status']); ?>
                            </div>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Event:</span> <?php echo $booking['event_type']; ?>
                            </p>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Date:</span> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                            </p>
                            <p class="text-white/70 mb-1">
                                <span class="font-semibold">Time:</span> <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                            </p>
                            <p class="text-white/70">
                                <span class="font-semibold">Total:</span> 
                                <span class="text-yellow-300 font-bold"><?php echo formatCurrency($booking['total_amount']); ?></span>
                            </p>
                        </div>
                        
                        <?php if($booking['payment_method'] == 'bank_transfer' && $booking['payment_status'] == 'pending'): ?>
                        <button onclick="showReceiptModal('hall', <?php echo $booking['id']; ?>)" 
                                class="bg-yellow-500 text-white px-6 py-2 rounded-lg hover:bg-yellow-600 transition">
                            Upload Receipt
                        </button>
                        <?php endif; ?>
                        
                        <?php if($booking['payment_status'] == 'verified'): ?>
                        <span class="text-green-400">
                            <i class="fas fa-check-circle mr-1"></i> Payment Verified
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-white/70 text-center py-8">No hall bookings yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Profile Tab -->
    <div id="profileTab" class="tab-content hidden">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8" data-aos="fade-up">
            <h2 class="text-2xl font-bold text-white mb-6">Profile Information</h2>
            
            <form method="POST" class="max-w-2xl space-y-6">
                <div>
                    <label class="block text-white/70 mb-2">Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($guest['full_name']); ?>" required
                           class="w-full px-4 py-3 rounded-lg text-gray-800 focus:ring-2 focus:ring-yellow-500">
                </div>
                
                <div>
                    <label class="block text-white/70 mb-2">Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($guest['email']); ?>" disabled
                           class="w-full px-4 py-3 rounded-lg text-gray-500 bg-gray-100 cursor-not-allowed">
                    <p class="text-white/50 text-sm mt-1">Email cannot be changed</p>
                </div>
                
                <div>
                    <label class="block text-white/70 mb-2">Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($guest['phone']); ?>" required
                           class="w-full px-4 py-3 rounded-lg text-gray-800 focus:ring-2 focus:ring-yellow-500">
                </div>
                
                <div>
                    <label class="block text-white/70 mb-2">Address</label>
                    <textarea name="address" rows="3" 
                              class="w-full px-4 py-3 rounded-lg text-gray-800 focus:ring-2 focus:ring-yellow-500"><?php echo htmlspecialchars($guest['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile"
                        class="bg-gradient-to-r from-yellow-500 to-pink-500 text-white px-8 py-3 rounded-lg hover:from-yellow-600 hover:to-pink-600 transition">
                    Update Profile
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Upload Modal -->
<div id="receiptModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-white rounded-2xl max-w-md w-full mx-4 p-6" data-aos="zoom-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Upload Payment Receipt</h3>
            <button onclick="hideReceiptModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>
        
        <!-- Bank Details -->
        <div class="bg-blue-50 p-4 rounded-lg mb-6">
            <h4 class="font-semibold text-blue-800 mb-3">Bank Transfer Instructions</h4>
            <?php foreach($bank_accounts as $account): ?>
            <div class="mb-3 last:mb-0">
                <p class="font-medium text-gray-800"><?php echo $account['bank_name']; ?></p>
                <p class="text-sm text-gray-600">Account: <?php echo $account['account_name']; ?></p>
                <p class="text-sm text-gray-600">Number: <span class="font-mono font-bold"><?php echo $account['account_number']; ?></span></p>
            </div>
            <?php endforeach; ?>
        </div>
        
        <form id="receiptForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="booking_id" id="modal_booking_id">
            <input type="hidden" name="booking_type" id="modal_booking_type">
            
            <div class="mb-6">
                <label class="block text-gray-700 mb-2">Upload Receipt *</label>
                <input type="file" name="receipt" accept="image/*" required
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-600">
                <p class="text-xs text-gray-500 mt-1">Max file size: 5MB (JPG, PNG, JPEG only)</p>
            </div>
            
            <button type="submit" name="upload_receipt"
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                Upload Receipt
            </button>
        </form>
    </div>
</div>

<script>
function showTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active', 'border-yellow-500', 'text-white');
        btn.classList.add('text-white/70');
    });
    
    event.target.classList.add('active', 'border-yellow-500', 'text-white');
    event.target.classList.remove('text-white/70');
    
    // Show selected tab
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    document.getElementById(tabName + 'Tab').classList.remove('hidden');
}

function showReceiptModal(type, bookingId) {
    document.getElementById('modal_booking_type').value = type;
    document.getElementById('modal_booking_id').value = bookingId;
    document.getElementById('receiptModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideReceiptModal() {
    document.getElementById('receiptModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('receiptModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideReceiptModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>