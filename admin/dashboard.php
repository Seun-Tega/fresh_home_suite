<?php
session_start();
require_once '../config/config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fresh Home & Suite</title>
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
        .table-row-hover:hover {
            background: rgba(201, 164, 90, 0.05);
        }
        .quick-action-card {
            transition: all 0.3s ease;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            border-color: #C9A45A;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar - Navigation with ALL Links -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4" onerror="this.style.display='none'">
                <h2 class="text-xl font-bold heading-gold">Admin Panel</h2>
                <p class="text-sm text-[#F5F5F5]/60">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            
            <nav class="space-y-2">
                <!-- Dashboard -->
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-dashboard text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Dashboard</span>
                </a>
                
                <!-- Bookings -->
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-calendar-check text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Bookings</span>
                </a>
                
                <!-- Receipts -->
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-receipt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Receipts</span>
                </a>
                
                <!-- Rooms -->
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-bed text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Rooms</span>
                </a>
                
                <!-- Event Hall -->
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-building text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Event Hall</span>
                </a>
                
                <!-- Board Rooms Main -->
                <a href="boardrooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-door-open text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Board Rooms</span>
                </a>
                
                <!-- NEW: Upload Board Room Images -->
                <a href="boardroom-images-simple.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors ml-6">
                    <i class="fas fa-images text-[#C9A45A] text-sm"></i>
                    <span class="text-[#F5F5F5] text-sm">↳ Upload Board Room Images</span>
                </a>
                
                <!-- Restaurant Menu -->
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-utensils text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Restaurant Menu</span>
                </a>
                
                <!-- Gallery -->
                <a href="gallery.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-images text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Gallery</span>
                </a>
                
                <!-- Media Library -->
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-photo-video text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Media Library</span>
                </a>
                
                <!-- NEW: Upload Video -->
                <a href="upload-video-simple.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-video text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Upload Video</span>
                </a>
                
                <!-- Reports -->
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-chart-bar text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Reports</span>
                </a>
                
                <!-- Users -->
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-users text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Users</span>
                </a>
                
                <!-- Settings -->
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-cog text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Settings</span>
                </a>
                
                <hr class="border-[#C9A45A]/20 my-4">
                
                <!-- Logout -->
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-sign-out-alt text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold heading-gold">Dashboard</h1>
                <p class="text-[#F5F5F5]/60 mt-1">Welcome back! Here's what's happening with your hotel today.</p>
            </div>
            
            <!-- Quick Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <?php
                // Get stats
                $today = date('Y-m-d');
                
                // Today's bookings
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_bookings WHERE DATE(created_at) = ?");
                $stmt->execute([$today]);
                $today_bookings = $stmt->fetchColumn();
                
                // Total revenue this month
                $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM room_bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND payment_status = 'verified'");
                $stmt->execute();
                $month_revenue = $stmt->fetchColumn() ?? 0;
                
                // Pending inquiries
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM room_bookings WHERE booking_status = 'pending'");
                $stmt->execute();
                $pending = $stmt->fetchColumn();
                
                // Occupancy rate
                $stmt = $pdo->query("SELECT COUNT(*) FROM rooms");
                $total_rooms = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("SELECT COUNT(DISTINCT room_id) FROM room_bookings WHERE booking_status IN ('confirmed', 'checked_in') AND ? BETWEEN check_in AND check_out");
                $stmt->execute([$today]);
                $occupied = $stmt->fetchColumn();
                $occupancy_rate = $total_rooms > 0 ? round(($occupied / $total_rooms) * 100) : 0;
                
                if (!function_exists('formatCurrency')) {
                    function formatCurrency($amount) {
                        return '₦' . number_format($amount, 0);
                    }
                }
                if (!function_exists('getStatusBadge')) {
                    function getStatusBadge($status) {
                        $colors = [
                            'pending' => 'bg-yellow-500/20 text-yellow-500',
                            'confirmed' => 'bg-green-500/20 text-green-500',
                            'checked_in' => 'bg-blue-500/20 text-blue-500',
                            'checked_out' => 'bg-gray-500/20 text-gray-500',
                            'cancelled' => 'bg-red-500/20 text-red-500'
                        ];
                        $color = $colors[$status] ?? 'bg-gray-500/20 text-gray-500';
                        return "<span class='px-2 py-1 rounded-full text-xs font-semibold $color'>" . ucfirst($status) . "</span>";
                    }
                }
                ?>
                
                <div class="card-gradient rounded-2xl p-6 hover:border-[#C9A45A]/40 transition-all" data-aos="fade-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#F5F5F5]/60">Today's Bookings</p>
                            <h3 class="text-3xl font-bold heading-gold"><?php echo $today_bookings; ?></h3>
                        </div>
                        <div class="text-4xl text-[#C9A45A]">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-gradient rounded-2xl p-6 hover:border-[#C9A45A]/40 transition-all" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#F5F5F5]/60">Monthly Revenue</p>
                            <h3 class="text-3xl font-bold heading-gold"><?php echo formatCurrency($month_revenue); ?></h3>
                        </div>
                        <div class="text-4xl text-[#C9A45A]">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-gradient rounded-2xl p-6 hover:border-[#C9A45A]/40 transition-all" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#F5F5F5]/60">Pending</p>
                            <h3 class="text-3xl font-bold heading-gold"><?php echo $pending; ?></h3>
                        </div>
                        <div class="text-4xl text-[#C9A45A]">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                
                <div class="card-gradient rounded-2xl p-6 hover:border-[#C9A45A]/40 transition-all" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[#F5F5F5]/60">Occupancy Rate</p>
                            <h3 class="text-3xl font-bold heading-gold"><?php echo $occupancy_rate; ?>%</h3>
                        </div>
                        <div class="text-4xl text-[#C9A45A]">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <div class="card-gradient rounded-2xl p-6" data-aos="fade-up">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold heading-gold">Recent Bookings</h2>
                    <a href="bookings.php" class="text-[#C9A45A] hover:text-[#A8843F] text-sm">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#C9A45A]/20">
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Booking #</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Guest</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Room</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Check In</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Check Out</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Total</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Status</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Action</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT rb.*, r.room_type, g.full_name 
                                    FROM room_bookings rb
                                    JOIN rooms r ON rb.room_id = r.id
                                    JOIN guests g ON rb.guest_id = g.id
                                    ORDER BY rb.created_at DESC
                                    LIMIT 10
                                ");
                                
                                while($booking = $stmt->fetch()):
                            ?>
                            <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                <td class="py-3 text-[#F5F5F5]"><?php echo $booking['booking_number']; ?></td>
                                <td class="py-3 text-[#F5F5F5]"><?php echo $booking['full_name']; ?></td>
                                <td class="py-3 text-[#F5F5F5]"><?php echo $booking['room_type']; ?></td>
                                <td class="py-3 text-[#F5F5F5]"><?php echo date('M d, Y', strtotime($booking['check_in'])); ?></td>
                                <td class="py-3 text-[#F5F5F5]"><?php echo date('M d, Y', strtotime($booking['check_out'])); ?></td>
                                <td class="py-3 heading-gold font-semibold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                <td class="py-3"><?php echo getStatusBadge($booking['booking_status']); ?></td>
                                <td class="py-3">
                                    <a href="booking-details.php?id=<?php echo $booking['id']; ?>" 
                                       class="text-[#C9A45A] hover:text-[#A8843F] transition-colors">
                                        View
                                    </a>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="8" class="py-4 text-center text-[#F5F5F5]/60">No bookings found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html>