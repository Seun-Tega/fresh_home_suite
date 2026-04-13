<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_booking_status'])) {
        $booking_id = $_POST['booking_id'];
        $booking_type = $_POST['booking_type'];
        $status = $_POST['status'];
        
        if ($booking_type == 'room') {
            $stmt = $pdo->prepare("UPDATE room_bookings SET booking_status = ? WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE hall_bookings SET booking_status = ? WHERE id = ?");
        }
        
        $stmt->execute([$status, $booking_id]);
        
        $_SESSION['success'] = "Booking status updated successfully!";
        header("Location: bookings.php");
        exit();
    }
    
    if (isset($_POST['add_manual_booking'])) {
        // Add manual booking for walk-in guests
        $guest_name = $_POST['guest_name'];
        $guest_email = $_POST['guest_email'];
        $guest_phone = $_POST['guest_phone'];
        $room_id = $_POST['room_id'];
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $adults = $_POST['adults'];
        $children = $_POST['children'];
        $total_amount = $_POST['total_amount'];
        
        // Check if guest exists or create new
        $stmt = $pdo->prepare("SELECT id FROM guests WHERE email = ?");
        $stmt->execute([$guest_email]);
        $guest = $stmt->fetch();
        
        if ($guest) {
            $guest_id = $guest['id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO guests (full_name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$guest_name, $guest_email, $guest_phone]);
            $guest_id = $pdo->lastInsertId();
        }
        
        // Generate booking number
        $booking_number = generateBookingNumber('RM');
        
        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO room_bookings (
                booking_number, guest_id, room_id, check_in, check_out, 
                adults, children, total_guests, subtotal, total_amount, 
                payment_method, payment_status, booking_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pay_at_hotel', 'verified', 'confirmed', NOW())
        ");
        
        $total_guests = $adults + $children;
        $stmt->execute([
            $booking_number, $guest_id, $room_id, $check_in, $check_out,
            $adults, $children, $total_guests, $total_amount, $total_amount
        ]);
        
        $_SESSION['success'] = "Manual booking added successfully!";
        header("Location: bookings.php");
        exit();
    }
}

// Get filter parameters
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$filter_date = $_GET['date'] ?? '';

// Get room bookings
$room_query = "
    SELECT rb.*, r.room_type, r.room_number, g.full_name, g.email, g.phone,
           'room' as booking_type
    FROM room_bookings rb
    JOIN rooms r ON rb.room_id = r.id
    JOIN guests g ON rb.guest_id = g.id
    WHERE 1=1
";

if ($filter_status != 'all') {
    $room_query .= " AND rb.booking_status = '" . $filter_status . "'";
}

if ($filter_date) {
    $room_query .= " AND DATE(rb.check_in) = '" . $filter_date . "'";
}

$room_query .= " ORDER BY rb.created_at DESC";

$stmt = $pdo->query($room_query);
$room_bookings = $stmt->fetchAll();

// Get hall bookings
$hall_query = "
    SELECT hb.*, h.name as hall_name, g.full_name, g.email, g.phone,
           'hall' as booking_type
    FROM hall_bookings hb
    JOIN hall h ON hb.hall_id = h.id
    JOIN guests g ON hb.guest_id = g.id
    WHERE 1=1
";

if ($filter_status != 'all') {
    $hall_query .= " AND hb.booking_status = '" . $filter_status . "'";
}

if ($filter_date) {
    $hall_query .= " AND DATE(hb.booking_date) = '" . $filter_date . "'";
}

$hall_query .= " ORDER BY hb.created_at DESC";

$stmt = $pdo->query($hall_query);
$hall_bookings = $stmt->fetchAll();

// Merge bookings based on filter type
$all_bookings = [];
if ($filter_type == 'all' || $filter_type == 'room') {
    $all_bookings = array_merge($all_bookings, $room_bookings);
}
if ($filter_type == 'all' || $filter_type == 'hall') {
    $all_bookings = array_merge($all_bookings, $hall_bookings);
}

// Sort by created date
usort($all_bookings, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get rooms for manual booking
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number");
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Fresh Home & Suite</title>
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
        .table-row-hover:hover {
            background: rgba(201, 164, 90, 0.05);
        }
        .modal-content {
            background: #0F0F0F;
            border: 1px solid rgba(201, 164, 90, 0.3);
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
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold heading-gold">Booking Management</h1>
                <button onclick="showManualBookingModal()" 
                        class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                    <i class="fas fa-user-plus mr-2"></i> Add Manual Booking
                </button>
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
            
            <!-- Filters -->
            <div class="card-gradient rounded-2xl p-6 mb-8">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">Booking Type</label>
                        <select name="type" class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>All Bookings</option>
                            <option value="room" <?php echo $filter_type == 'room' ? 'selected' : ''; ?>>Room Bookings</option>
                            <option value="hall" <?php echo $filter_type == 'hall' ? 'selected' : ''; ?>>Hall Bookings</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">Status</label>
                        <select name="status" class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo $filter_status == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" <?php echo $filter_status == 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-[#C9A45A] mb-2 font-medium">Date</label>
                        <input type="date" name="date" value="<?php echo $filter_date; ?>" 
                               class="w-full px-4 py-2 rounded-lg input-field">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-2 rounded-lg hover:bg-[#A8843F] transition w-full font-semibold">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Bookings List -->
            <div class="card-gradient rounded-2xl p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#C9A45A]/20">
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Booking #</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Type</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Guest</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Item</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Check In/Date</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Check Out/Time</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Amount</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Status</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Payment</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_bookings as $booking): ?>
                            <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                <td class="py-3 text-[#F5F5F5]"><?php echo $booking['booking_number']; ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $booking['booking_type'] == 'room' ? 'bg-[#C9A45A]/20 text-[#C9A45A]' : 'bg-[#C9A45A]/20 text-[#C9A45A]'; ?>">
                                        <?php echo ucfirst($booking['booking_type']); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <div class="text-[#F5F5F5]"><?php echo $booking['full_name']; ?></div>
                                    <div class="text-xs text-[#F5F5F5]/50"><?php echo $booking['email']; ?></div>
                                </td>
                                <td class="py-3 text-[#F5F5F5]">
                                    <?php if($booking['booking_type'] == 'room'): ?>
                                        <?php echo $booking['room_type']; ?> (<?php echo $booking['room_number']; ?>)
                                    <?php else: ?>
                                        <?php echo $booking['hall_name']; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-[#F5F5F5]">
                                    <?php if($booking['booking_type'] == 'room'): ?>
                                        <?php echo date('M d, Y', strtotime($booking['check_in'])); ?>
                                    <?php else: ?>
                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-[#F5F5F5]">
                                    <?php if($booking['booking_type'] == 'room'): ?>
                                        <?php echo date('M d, Y', strtotime($booking['check_out'])); ?>
                                    <?php else: ?>
                                        <?php echo date('h:i A', strtotime($booking['start_time'])); ?> - 
                                        <?php echo date('h:i A', strtotime($booking['end_time'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-[#C9A45A] font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td class="py-3"><?php echo getStatusBadge($booking['booking_status']); ?></td>
                                <td class="py-3"><?php echo getStatusBadge($booking['payment_status']); ?></td>
                                <td class="py-3">
                                    <button onclick="showStatusModal(<?php echo htmlspecialchars(json_encode($booking)); ?>)" 
                                            class="text-[#C9A45A] hover:text-[#A8843F] mr-3 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="booking-details.php?type=<?php echo $booking['booking_type']; ?>&id=<?php echo $booking['id']; ?>" 
                                       class="text-[#C9A45A] hover:text-[#A8843F] transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($all_bookings)): ?>
                            <tr>
                                <td colspan="10" class="py-8 text-center text-[#F5F5F5]/60">
                                    No bookings found
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Manual Booking Modal -->
    <div id="manualBookingModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Add Manual Booking (Walk-in Guest)</h3>
                <button onclick="hideManualBookingModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-[#C9A45A]">Guest Information</h4>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Full Name *</label>
                            <input type="text" name="guest_name" required 
                                   class="w-full px-4 py-2 rounded-lg input-field">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Email *</label>
                            <input type="email" name="guest_email" required 
                                   class="w-full px-4 py-2 rounded-lg input-field">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Phone *</label>
                            <input type="tel" name="guest_phone" required 
                                   class="w-full px-4 py-2 rounded-lg input-field">
                        </div>
                    </div>
                    
                    <h4 class="text-lg font-semibold text-[#C9A45A] pt-4">Booking Details</h4>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Select Room *</label>
                        <select name="room_id" required class="w-full px-4 py-2 rounded-lg input-field">
                            <option value="" class="bg-[#0F0F0F]">Choose a room</option>
                            <?php foreach($rooms as $room): ?>
                            <option value="<?php echo $room['id']; ?>" class="bg-[#0F0F0F]">
                                <?php echo $room['room_number']; ?> - <?php echo $room['room_type']; ?> 
                                (<?php echo formatCurrency($room['base_price']); ?>/night)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Check-in Date *</label>
                            <input type="date" name="check_in" min="<?php echo date('Y-m-d'); ?>" required 
                                   class="w-full px-4 py-2 rounded-lg input-field">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Check-out Date *</label>
                            <input type="date" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required 
                                   class="w-full px-4 py-2 rounded-lg input-field">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Adults *</label>
                            <select name="adults" required class="w-full px-4 py-2 rounded-lg input-field">
                                <?php for($i = 1; $i <= 4; $i++): ?>
                                <option value="<?php echo $i; ?>" class="bg-[#0F0F0F]"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Children</label>
                            <select name="children" class="w-full px-4 py-2 rounded-lg input-field">
                                <?php for($i = 0; $i <= 3; $i++): ?>
                                <option value="<?php echo $i; ?>" class="bg-[#0F0F0F]"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Total Amount (₦) *</label>
                        <input type="number" name="total_amount" required 
                               class="w-full px-4 py-2 rounded-lg input-field">
                        <p class="text-xs text-[#F5F5F5]/50 mt-1">Calculate based on room price and number of nights</p>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_manual_booking"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Add Booking
                    </button>
                    <button type="button" onclick="hideManualBookingModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Update Booking Status</h3>
                <button onclick="hideStatusModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="statusForm">
                <input type="hidden" name="booking_id" id="status_booking_id">
                <input type="hidden" name="booking_type" id="status_booking_type">
                
                <div class="mb-6">
                    <label class="block text-[#F5F5F5]/70 mb-2">Booking #<span id="status_booking_number" class="text-[#C9A45A] ml-1"></span></label>
                    
                    <select name="status" class="w-full px-4 py-2 rounded-lg input-field">
                        <option value="pending" class="bg-[#0F0F0F]">Pending</option>
                        <option value="confirmed" class="bg-[#0F0F0F]">Confirmed</option>
                        <option value="checked_in" class="bg-[#0F0F0F]">Checked In</option>
                        <option value="checked_out" class="bg-[#0F0F0F]">Checked Out</option>
                        <option value="cancelled" class="bg-[#0F0F0F]">Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" name="update_booking_status"
                        class="w-full bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                    Update Status
                </button>
            </form>
        </div>
    </div>
    
    <script>
    function showManualBookingModal() {
        document.getElementById('manualBookingModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideManualBookingModal() {
        document.getElementById('manualBookingModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showStatusModal(booking) {
        document.getElementById('status_booking_id').value = booking.id;
        document.getElementById('status_booking_type').value = booking.booking_type;
        document.getElementById('status_booking_number').textContent = booking.booking_number;
        
        // Set current status
        const select = document.querySelector('select[name="status"]');
        select.value = booking.booking_status;
        
        document.getElementById('statusModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideStatusModal() {
        document.getElementById('statusModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('manualBookingModal').addEventListener('click', function(e) {
        if (e.target === this) hideManualBookingModal();
    });
    
    document.getElementById('statusModal').addEventListener('click', function(e) {
        if (e.target === this) hideStatusModal();
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