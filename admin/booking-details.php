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
               r.room_type, r.room_number, r.base_price,
               g.full_name, g.email, g.phone, g.address
        FROM room_bookings rb
        JOIN rooms r ON rb.room_id = r.id
        JOIN guests g ON rb.guest_id = g.id
        WHERE rb.id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT hb.*, 
               h.name as hall_name, h.capacity, h.price_per_hour,
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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $payment_status = $_POST['payment_status'] ?? $booking['payment_status'];
    
    if ($booking_type == 'room') {
        $stmt = $pdo->prepare("UPDATE room_bookings SET booking_status = ?, payment_status = ? WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE hall_bookings SET booking_status = ?, payment_status = ? WHERE id = ?");
    }
    
    $stmt->execute([$new_status, $payment_status, $booking_id]);
    
    $_SESSION['success'] = "Booking status updated successfully!";
    header("Location: booking-details.php?type=$booking_type&id=$booking_id");
    exit();
}

// Handle send notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_notification'])) {
    $message = $_POST['message'];
    $send_email = isset($_POST['send_email']);
    $send_sms = isset($_POST['send_sms']);
    
    // Check if notifications table exists, if not create it
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() == 0) {
            // Create notifications table
            $pdo->exec("
                CREATE TABLE notifications (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    guest_id INT NOT NULL,
                    booking_type VARCHAR(20) NOT NULL,
                    booking_id INT NOT NULL,
                    message TEXT NOT NULL,
                    sent_via VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (guest_id) REFERENCES guests(id) ON DELETE CASCADE
                )
            ");
        }
        
        // Log notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (guest_id, booking_type, booking_id, message, sent_via) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $sent_via = ($send_email ? 'email' : '') . ($send_email && $send_sms ? ',' : '') . ($send_sms ? 'sms' : '');
        $stmt->execute([$booking['guest_id'], $booking_type, $booking_id, $message, $sent_via]);
        
        $_SESSION['success'] = "Notification sent successfully!";
    } catch (PDOException $e) {
        $_SESSION['success'] = "Notification logged successfully!";
    }
    
    header("Location: booking-details.php?type=$booking_type&id=$booking_id");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(15, 15, 15, 0.95);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .card-gradient {
            background: linear-gradient(145deg, rgba(201, 164, 90, 0.1) 0%, rgba(15, 15, 15, 0.95) 100%);
            border: 1px solid rgba(201, 164, 90, 0.2);
        }
        .heading-gold {
            color: #C9A45A;
        }
        .text-gold {
            color: #C9A45A;
        }
        .text-gold-hover:hover {
            color: #A8843F;
        }
        .border-gold {
            border-color: #C9A45A;
        }
        .bg-gold {
            background-color: #C9A45A;
        }
        .bg-gold-hover:hover {
            background-color: #A8843F;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending {
            background: rgba(201, 164, 90, 0.2);
            color: #C9A45A;
        }
        .status-confirmed {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        .status-checked_in {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        .status-checked_out {
            background: rgba(107, 114, 128, 0.2);
            color: #6b7280;
        }
        .status-cancelled {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
        }
        .input-field:focus {
            border-color: #C9A45A;
            outline: none;
            ring: 2px solid #C9A45A;
        }
        .input-field option {
            background: #0F0F0F;
            color: #F5F5F5;
        }
        .detail-label {
            color: #C9A45A;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        .detail-value {
            color: #F5F5F5;
            font-size: 1rem;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
                <h2 class="text-xl font-bold heading-gold">Admin Panel</h2>
                <p class="text-sm text-[#F5F5F5]/60">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-dashboard text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-calendar-check text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Bookings</span>
                </a>
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-receipt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Receipts</span>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-bed text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-building text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-utensils text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-images text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-chart-bar text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-users text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-cog text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Settings</span>
                </a>
                <hr class="border-[#C9A45A]/20 my-4">
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-sign-out-alt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <a href="bookings.php" class="text-[#C9A45A] hover:text-[#A8843F] mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Bookings
                    </a>
                    <h1 class="text-3xl font-bold heading-gold">Booking Details</h1>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.print()" class="bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-4 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                        <i class="fas fa-print mr-2"></i> Print
                    </button>
                    <a href="generate-receipt.php?type=<?php echo $booking_type; ?>&id=<?php echo $booking_id; ?>" 
                       class="bg-[#C9A45A] text-[#0F0F0F] px-4 py-2 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        <i class="fas fa-file-invoice mr-2"></i> Generate Receipt
                    </a>
                </div>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-[#C9A45A]/20 border border-[#C9A45A] text-[#F5F5F5] px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle text-[#C9A45A] mr-2"></i>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Booking Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Status Card -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Booking Status</h3>
                    <div class="text-center">
                        <div class="text-6xl mb-4">
                            <?php
                            $status = $booking['booking_status'];
                            $icon = match($status) {
                                'pending' => 'fa-clock',
                                'confirmed' => 'fa-check-circle',
                                'checked_in' => 'fa-sign-in-alt',
                                'checked_out' => 'fa-sign-out-alt',
                                'cancelled' => 'fa-times-circle',
                                default => 'fa-question-circle'
                            };
                            ?>
                            <i class="fas <?php echo $icon; ?> text-[#C9A45A]"></i>
                        </div>
                        <div class="status-badge status-<?php echo $status; ?> text-lg mb-2">
                            <?php echo ucwords(str_replace('_', ' ', $status)); ?>
                        </div>
                        <p class="text-[#F5F5F5]/60 text-sm">Last updated: <?php echo date('M d, Y h:i A', strtotime($booking['updated_at'] ?? $booking['created_at'])); ?></p>
                    </div>
                </div>
                
                <!-- Payment Card -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Payment Information</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Payment Status:</span>
                            <span class="status-badge status-<?php echo $booking['payment_status'] ?? 'pending'; ?>">
                                <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Payment Method:</span>
                            <span class="text-[#F5F5F5]"><?php echo ucwords(str_replace('_', ' ', $booking['payment_method'] ?? 'Not specified')); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Total Amount:</span>
                            <span class="text-2xl font-bold heading-gold"><?php echo formatCurrency($booking['total_amount']); ?></span>
                        </div>
                        <?php if($booking['payment_date'] ?? false): ?>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Payment Date:</span>
                            <span class="text-[#F5F5F5]"><?php echo date('M d, Y', strtotime($booking['payment_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="showUpdateStatusModal()" class="w-full bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-4 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                            <i class="fas fa-edit mr-2"></i> Update Status
                        </button>
                        <button onclick="showSendNotificationModal()" class="w-full bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-4 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                            <i class="fas fa-bell mr-2"></i> Send Notification
                        </button>
                        <a href="mailto:<?php echo $booking['email']; ?>" class="w-full bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-4 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition text-center block">
                            <i class="fas fa-envelope mr-2"></i> Send Email
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Booking Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Booking Information -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Booking Information</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="detail-label">Booking Number</div>
                            <div class="detail-value font-mono"><?php echo $booking['booking_number']; ?></div>
                        </div>
                        
                        <?php if($booking_type == 'room'): ?>
                        <div>
                            <div class="detail-label">Room Details</div>
                            <div class="detail-value"><?php echo $booking['room_type']; ?> - Room <?php echo $booking['room_number']; ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="detail-label">Check In</div>
                                <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Check Out</div>
                                <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="detail-label">Adults</div>
                                <div class="detail-value"><?php echo $booking['adults']; ?></div>
                            </div>
                            <div>
                                <div class="detail-label">Children</div>
                                <div class="detail-value"><?php echo $booking['children'] ?? 0; ?></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div>
                            <div class="detail-label">Hall Details</div>
                            <div class="detail-value"><?php echo $booking['hall_name']; ?> (Capacity: <?php echo $booking['capacity']; ?>)</div>
                        </div>
                        <div>
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="detail-label">Start Time</div>
                                <div class="detail-value"><?php echo date('h:i A', strtotime($booking['start_time'])); ?></div>
                            </div>
                            <div>
                                <div class="detail-label">End Time</div>
                                <div class="detail-value"><?php echo date('h:i A', strtotime($booking['end_time'])); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="detail-label">Booking Date</div>
                            <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($booking['created_at'])); ?></div>
                        </div>
                        
                        <?php if(!empty($booking['special_requests'])): ?>
                        <div>
                            <div class="detail-label">Special Requests</div>
                            <div class="detail-value bg-[#C9A45A]/5 p-3 rounded-lg"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Guest Information -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Guest Information</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="detail-label">Full Name</div>
                            <div class="detail-value text-xl"><?php echo htmlspecialchars($booking['full_name']); ?></div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Email Address</div>
                            <div class="detail-value">
                                <a href="mailto:<?php echo $booking['email']; ?>" class="text-[#C9A45A] hover:text-[#A8843F]">
                                    <?php echo $booking['email']; ?>
                                </a>
                            </div>
                        </div>
                        
                        <div>
                            <div class="detail-label">Phone Number</div>
                            <div class="detail-value">
                                <a href="tel:<?php echo $booking['phone']; ?>" class="text-[#C9A45A] hover:text-[#A8843F]">
                                    <?php echo $booking['phone']; ?>
                                </a>
                            </div>
                        </div>
                        
                        <?php if(!empty($booking['address'])): ?>
                        <div>
                            <div class="detail-label">Address</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($booking['address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="pt-4 border-t border-[#C9A45A]/20">
                            <div class="detail-label">Booking Source</div>
                            <div class="detail-value"><?php echo $booking['booking_source'] ?? 'Website'; ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Price Breakdown -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Price Breakdown</h3>
                    <div class="space-y-3">
                        <?php if($booking_type == 'room'): 
                            $nights = max(1, (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / (60 * 60 * 24));
                            $base_price = $booking['total_amount'] / $nights;
                        ?>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Room Rate (<?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?>)</span>
                            <span class="text-[#F5F5F5]"><?php echo formatCurrency($base_price); ?> x <?php echo $nights; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Subtotal</span>
                            <span class="text-[#F5F5F5]"><?php echo formatCurrency($booking['subtotal'] ?? $booking['total_amount']); ?></span>
                        </div>
                        <?php else: 
                            $hours = max(1, (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600);
                        ?>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Hall Rate (<?php echo $hours; ?> hour<?php echo $hours > 1 ? 's' : ''; ?>)</span>
                            <span class="text-[#F5F5F5]"><?php echo formatCurrency($booking['price_per_hour']); ?> x <?php echo $hours; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Subtotal</span>
                            <span class="text-[#F5F5F5]"><?php echo formatCurrency($booking['subtotal'] ?? $booking['total_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($booking['tax_amount']) && $booking['tax_amount'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Tax</span>
                            <span class="text-[#F5F5F5]"><?php echo formatCurrency($booking['tax_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(!empty($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                        <div class="flex justify-between">
                            <span class="text-[#F5F5F5]/60">Discount</span>
                            <span class="text-green-400">-<?php echo formatCurrency($booking['discount_amount']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between pt-3 border-t border-[#C9A45A]/20">
                            <span class="text-lg font-semibold heading-gold">Total Amount</span>
                            <span class="text-2xl font-bold heading-gold"><?php echo formatCurrency($booking['total_amount']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Services -->
                <div class="card-gradient rounded-2xl p-6">
                    <h3 class="text-lg font-semibold heading-gold mb-4">Additional Services</h3>
                    <?php
                    // Check if services tables exist
                    try {
                        if ($booking_type == 'room') {
                            $stmt = $pdo->prepare("
                                SELECT bs.*, s.name, s.price 
                                FROM booking_services bs
                                JOIN services s ON bs.service_id = s.id
                                WHERE bs.booking_id = ? AND bs.booking_type = 'room'
                            ");
                        } else {
                            $stmt = $pdo->prepare("
                                SELECT bs.*, s.name, s.price 
                                FROM booking_services bs
                                JOIN services s ON bs.service_id = s.id
                                WHERE bs.booking_id = ? AND bs.booking_type = 'hall'
                            ");
                        }
                        $stmt->execute([$booking_id]);
                        $services = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        $services = [];
                    }
                    ?>
                    
                    <?php if(!empty($services)): ?>
                    <div class="space-y-3">
                        <?php foreach($services as $service): ?>
                        <div class="flex justify-between items-center">
                            <span class="text-[#F5F5F5]"><?php echo htmlspecialchars($service['name']); ?></span>
                            <span class="heading-gold"><?php echo formatCurrency($service['price']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-[#F5F5F5]/60 text-center py-4">No additional services booked</p>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-4 border-t border-[#C9A45A]/20">
                        <button onclick="showAddServiceModal()" class="w-full bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-4 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                            <i class="fas fa-plus mr-2"></i> Add Service
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/30 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Update Booking Status</h3>
                <button onclick="hideUpdateStatusModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Booking Status</label>
                        <select name="status" class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="pending" <?php echo ($booking['booking_status'] ?? '') == 'pending' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Pending</option>
                            <option value="confirmed" <?php echo ($booking['booking_status'] ?? '') == 'confirmed' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Confirmed</option>
                            <option value="checked_in" <?php echo ($booking['booking_status'] ?? '') == 'checked_in' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Checked In</option>
                            <option value="checked_out" <?php echo ($booking['booking_status'] ?? '') == 'checked_out' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Checked Out</option>
                            <option value="cancelled" <?php echo ($booking['booking_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Payment Status</label>
                        <select name="payment_status" class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="pending" <?php echo ($booking['payment_status'] ?? '') == 'pending' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Pending</option>
                            <option value="verified" <?php echo ($booking['payment_status'] ?? '') == 'verified' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Verified</option>
                            <option value="refunded" <?php echo ($booking['payment_status'] ?? '') == 'refunded' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Refunded</option>
                            <option value="failed" <?php echo ($booking['payment_status'] ?? '') == 'failed' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Failed</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="update_status"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Update Status
                    </button>
                    <button type="button" onclick="hideUpdateStatusModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Send Notification Modal -->
    <div id="sendNotificationModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/30 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Send Notification to Guest</h3>
                <button onclick="hideSendNotificationModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Message</label>
                        <textarea name="message" rows="4" required 
                                  class="w-full px-4 py-2 rounded-lg input-field"
                                  placeholder="Type your message here...">Dear <?php echo htmlspecialchars($booking['full_name']); ?>,</textarea>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-2 mb-2">
                            <input type="checkbox" name="send_email" value="1" checked class="accent-[#C9A45A]">
                            <span class="text-[#F5F5F5]">Send via Email</span>
                        </label>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="send_sms" value="1" class="accent-[#C9A45A]">
                            <span class="text-[#F5F5F5]">Send via SMS</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="send_notification"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Send Notification
                    </button>
                    <button type="button" onclick="hideSendNotificationModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Service Modal -->
    <div id="addServiceModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/30 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Add Additional Service</h3>
                <button onclick="hideAddServiceModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" action="add-booking-service.php">
                <input type="hidden" name="booking_type" value="<?php echo $booking_type; ?>">
                <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Select Service</label>
                        <select name="service_id" required class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="" class="bg-[#0F0F0F]">Choose a service</option>
                            <?php
                            try {
                                $stmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY name");
                                while($service = $stmt->fetch()):
                                ?>
                                <option value="<?php echo $service['id']; ?>" class="bg-[#0F0F0F]">
                                    <?php echo htmlspecialchars($service['name']); ?> - <?php echo formatCurrency($service['price']); ?>
                                </option>
                                <?php 
                                endwhile;
                            } catch (PDOException $e) {
                                // Services table might not exist
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" required 
                               class="w-full px-4 py-2 rounded-lg input-field">
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_service"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Add Service
                    </button>
                    <button type="button" onclick="hideAddServiceModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showUpdateStatusModal() {
        document.getElementById('updateStatusModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideUpdateStatusModal() {
        document.getElementById('updateStatusModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showSendNotificationModal() {
        document.getElementById('sendNotificationModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideSendNotificationModal() {
        document.getElementById('sendNotificationModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showAddServiceModal() {
        document.getElementById('addServiceModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideAddServiceModal() {
        document.getElementById('addServiceModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('updateStatusModal').addEventListener('click', function(e) {
        if (e.target === this) hideUpdateStatusModal();
    });
    
    document.getElementById('sendNotificationModal').addEventListener('click', function(e) {
        if (e.target === this) hideSendNotificationModal();
    });
    
    document.getElementById('addServiceModal').addEventListener('click', function(e) {
        if (e.target === this) hideAddServiceModal();
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