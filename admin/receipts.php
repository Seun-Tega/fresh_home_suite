<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check role-based access (optional - add if you have roles)
$can_verify = in_array($_SESSION['admin_role'] ?? 'admin', ['super_admin', 'admin', 'finance']);

// Handle receipt verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_receipt']) && $can_verify) {
    $booking_id = $_POST['booking_id'];
    $booking_type = $_POST['booking_type'];
    
    if ($booking_type == 'room') {
        $stmt = $pdo->prepare("UPDATE room_bookings SET payment_status = 'verified', receipt_verified_by = ?, receipt_verified_at = NOW() WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE hall_bookings SET payment_status = 'verified', receipt_verified_by = ?, receipt_verified_at = NOW() WHERE id = ?");
    }
    
    $stmt->execute([$_SESSION['admin_id'], $booking_id]);
    
    $_SESSION['success'] = "Payment verified successfully!";
    header("Location: receipts.php");
    exit();
}

// Handle receipt rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_receipt']) && $can_verify) {
    $booking_id = $_POST['booking_id'];
    $booking_type = $_POST['booking_type'];
    
    if ($booking_type == 'room') {
        $stmt = $pdo->prepare("UPDATE room_bookings SET payment_status = 'pending', receipt_path = NULL WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE hall_bookings SET payment_status = 'pending', receipt_path = NULL WHERE id = ?");
    }
    
    $stmt->execute([$booking_id]);
    
    $_SESSION['success'] = "Receipt rejected. Guest can upload again.";
    header("Location: receipts.php");
    exit();
}

// Get statistics
$stats = [
    'pending_room' => $pdo->query("SELECT COUNT(*) FROM room_bookings WHERE payment_method = 'bank_transfer' AND payment_status = 'receipt_uploaded'")->fetchColumn(),
    'pending_hall' => $pdo->query("SELECT COUNT(*) FROM hall_bookings WHERE payment_method = 'bank_transfer' AND payment_status = 'receipt_uploaded'")->fetchColumn(),
    'verified_today' => $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM room_bookings WHERE payment_status = 'verified' AND DATE(receipt_verified_at) = CURDATE()
            UNION ALL
            SELECT id FROM hall_bookings WHERE payment_status = 'verified' AND DATE(receipt_verified_at) = CURDATE()
        ) as verified
    ")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipts Management - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201, 164, 90, 0.2);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #C9A45A;
            transform: translateY(-2px);
        }
        .table-header {
            background: rgba(201, 164, 90, 0.1);
            color: #C9A45A;
        }
        .table-row {
            transition: all 0.3s ease;
        }
        .table-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .btn-verify {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .btn-verify:hover {
            background: rgba(34, 197, 94, 0.3);
        }
        .btn-reject {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        .receipt-preview {
            max-width: 300px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid rgba(201, 164, 90, 0.3);
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar (same as other admin pages) -->
        <div class="sidebar w-64 text-white p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
                <h2 class="text-xl font-bold text-[#C9A45A]">Admin Panel</h2>
                <p class="text-sm text-white/60">Welcome, <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-dashboard w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span>Bookings</span>
                </a>
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 text-[#C9A45A]">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Receipts</span>
                    <?php if($stats['pending_room'] + $stats['pending_hall'] > 0): ?>
                    <span class="ml-auto bg-[#C9A45A] text-[#0F0F0F] text-xs font-bold px-2 py-1 rounded-full">
                        <?php echo $stats['pending_room'] + $stats['pending_hall']; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-bed w-5"></i>
                    <span>Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-building w-5"></i>
                    <span>Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-utensils w-5"></i>
                    <span>Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-images w-5"></i>
                    <span>Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
                <hr class="border-white/10 my-4">
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-500/10 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Header with Stats -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">Receipt Verification</h1>
                <?php if(!$can_verify): ?>
                <div class="bg-yellow-500/20 border border-yellow-500/30 text-yellow-200 px-4 py-2 rounded-lg">
                    <i class="fas fa-info-circle mr-2"></i> View Only Mode
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Pending Room Receipts</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['pending_room']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-bed text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Pending Hall Receipts</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['pending_hall']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-building text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Verified Today</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['verified_today']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-lg mb-6 flex items-center" data-aos="fade-in">
                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6 border-b border-[#C9A45A]/20">
                <div class="flex space-x-6">
                    <button onclick="showTab('room')" class="tab-btn active px-4 py-3 text-[#C9A45A] border-b-2 border-[#C9A45A] font-medium">
                        Room Bookings
                        <?php if($stats['pending_room'] > 0): ?>
                        <span class="ml-2 bg-[#C9A45A] text-[#0F0F0F] text-xs font-bold px-2 py-1 rounded-full">
                            <?php echo $stats['pending_room']; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <button onclick="showTab('hall')" class="tab-btn px-4 py-3 text-white/70 hover:text-[#C9A45A] font-medium">
                        Hall Bookings
                        <?php if($stats['pending_hall'] > 0): ?>
                        <span class="ml-2 bg-[#C9A45A] text-[#0F0F0F] text-xs font-bold px-2 py-1 rounded-full">
                            <?php echo $stats['pending_hall']; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
            
            <!-- Room Bookings Tab -->
            <div id="roomTab" class="tab-content">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead class="table-header">
                                <tr>
                                    <th class="text-left py-3 px-4 rounded-l-lg">Booking #</th>
                                    <th class="text-left py-3 px-4">Guest</th>
                                    <th class="text-left py-3 px-4">Room</th>
                                    <th class="text-left py-3 px-4">Amount</th>
                                    <th class="text-left py-3 px-4">Receipt</th>
                                    <th class="text-left py-3 px-4">Uploaded</th>
                                    <th class="text-left py-3 px-4 rounded-r-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT rb.*, r.room_type, g.full_name, g.email
                                    FROM room_bookings rb
                                    JOIN rooms r ON rb.room_id = r.id
                                    JOIN guests g ON rb.guest_id = g.id
                                    WHERE rb.payment_method = 'bank_transfer' 
                                    AND rb.payment_status = 'receipt_uploaded'
                                    ORDER BY rb.receipt_uploaded_at DESC
                                ");
                                
                                if($stmt->rowCount() > 0):
                                    while($booking = $stmt->fetch()):
                                ?>
                                <tr class="table-row border-b border-white/10">
                                    <td class="py-4 px-4">
                                        <span class="font-mono text-sm text-[#C9A45A]"><?php echo $booking['booking_number']; ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                        <div class="text-white/50 text-xs"><?php echo $booking['email']; ?></div>
                                    </td>
                                    <td class="py-4 px-4"><?php echo $booking['room_type']; ?></td>
                                    <td class="py-4 px-4 font-bold text-[#C9A45A]"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td class="py-4 px-4">
                                        <button onclick="showReceipt('<?php echo '../' . $booking['receipt_path']; ?>')" 
                                                class="text-blue-400 hover:text-blue-300 flex items-center">
                                            <i class="fas fa-image mr-2"></i> View Receipt
                                        </button>
                                    </td>
                                    <td class="py-4 px-4 text-white/60 text-sm">
                                        <?php echo date('M d, Y', strtotime($booking['receipt_uploaded_at'])); ?>
                                        <div class="text-xs"><?php echo date('h:i A', strtotime($booking['receipt_uploaded_at'])); ?></div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if($can_verify): ?>
                                        <div class="flex space-x-2">
                                            <form method="POST" class="inline" onsubmit="return confirm('Verify this payment?')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="booking_type" value="room">
                                                <button type="submit" name="verify_receipt" 
                                                        class="btn-verify px-3 py-1.5 rounded-lg text-sm font-medium flex items-center">
                                                    <i class="fas fa-check mr-1"></i> Verify
                                                </button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Reject this receipt? The guest will need to upload again.')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="booking_type" value="room">
                                                <button type="submit" name="reject_receipt" 
                                                        class="btn-reject px-3 py-1.5 rounded-lg text-sm font-medium flex items-center">
                                                    <i class="fas fa-times mr-1"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-white/40">No access</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-receipt text-5xl text-white/20 mb-3"></i>
                                            <p class="text-white/60">No pending receipts for room bookings</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Hall Bookings Tab -->
            <div id="hallTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead class="table-header">
                                <tr>
                                    <th class="text-left py-3 px-4 rounded-l-lg">Booking #</th>
                                    <th class="text-left py-3 px-4">Guest</th>
                                    <th class="text-left py-3 px-4">Event</th>
                                    <th class="text-left py-3 px-4">Amount</th>
                                    <th class="text-left py-3 px-4">Receipt</th>
                                    <th class="text-left py-3 px-4">Uploaded</th>
                                    <th class="text-left py-3 px-4 rounded-r-lg">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("
                                    SELECT hb.*, h.name as hall_name, g.full_name, g.email
                                    FROM hall_bookings hb
                                    JOIN hall h ON hb.hall_id = h.id
                                    JOIN guests g ON hb.guest_id = g.id
                                    WHERE hb.payment_method = 'bank_transfer' 
                                    AND hb.payment_status = 'receipt_uploaded'
                                    ORDER BY hb.receipt_uploaded_at DESC
                                ");
                                
                                if($stmt->rowCount() > 0):
                                    while($booking = $stmt->fetch()):
                                ?>
                                <tr class="table-row border-b border-white/10">
                                    <td class="py-4 px-4">
                                        <span class="font-mono text-sm text-[#C9A45A]"><?php echo $booking['booking_number']; ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                        <div class="text-white/50 text-xs"><?php echo $booking['email']; ?></div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div><?php echo $booking['event_type']; ?></div>
                                        <div class="text-white/50 text-xs"><?php echo $booking['hall_name']; ?></div>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-[#C9A45A]"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td class="py-4 px-4">
                                        <button onclick="showReceipt('<?php echo '../' . $booking['receipt_path']; ?>')" 
                                                class="text-blue-400 hover:text-blue-300 flex items-center">
                                            <i class="fas fa-image mr-2"></i> View Receipt
                                        </button>
                                    </td>
                                    <td class="py-4 px-4 text-white/60 text-sm">
                                        <?php echo date('M d, Y', strtotime($booking['receipt_uploaded_at'])); ?>
                                        <div class="text-xs"><?php echo date('h:i A', strtotime($booking['receipt_uploaded_at'])); ?></div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if($can_verify): ?>
                                        <div class="flex space-x-2">
                                            <form method="POST" class="inline" onsubmit="return confirm('Verify this payment?')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="booking_type" value="hall">
                                                <button type="submit" name="verify_receipt" 
                                                        class="btn-verify px-3 py-1.5 rounded-lg text-sm font-medium flex items-center">
                                                    <i class="fas fa-check mr-1"></i> Verify
                                                </button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Reject this receipt? The guest will need to upload again.')">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="booking_type" value="hall">
                                                <button type="submit" name="reject_receipt" 
                                                        class="btn-reject px-3 py-1.5 rounded-lg text-sm font-medium flex items-center">
                                                    <i class="fas fa-times mr-1"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-white/40">No access</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-receipt text-5xl text-white/20 mb-3"></i>
                                            <p class="text-white/60">No pending receipts for hall bookings</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Verified Receipts Summary (Optional) -->
            <div class="mt-8 bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6" data-aos="fade-up">
                <h3 class="text-lg font-bold text-[#C9A45A] mb-4 flex items-center">
                    <i class="fas fa-history mr-2"></i> Recent Verifications
                </h3>
                <div class="text-white/60 text-sm">
                    <p>View full verification history in the <a href="reports.php" class="text-[#C9A45A] hover:underline">Reports</a> section.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Receipt Preview Modal -->
    <div id="receiptModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-4xl w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-[#C9A45A]">Payment Receipt</h3>
                <button onclick="hideReceiptModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <div class="bg-black/30 rounded-lg overflow-hidden">
                <img id="receiptImage" src="" alt="Receipt" class="w-full h-auto max-h-[70vh] object-contain">
            </div>
            <div class="mt-4 flex justify-end">
                <button onclick="hideReceiptModal()" 
                        class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'text-[#C9A45A]', 'border-[#C9A45A]');
            btn.classList.add('text-white/70');
        });
        
        event.target.classList.add('active', 'text-[#C9A45A]', 'border-b-2', 'border-[#C9A45A]');
        event.target.classList.remove('text-white/70');
        
        // Show selected tab
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        document.getElementById(tabName + 'Tab').classList.remove('hidden');
    }
    
    function showReceipt(path) {
        document.getElementById('receiptImage').src = path;
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
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>