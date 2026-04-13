<?php
session_start();
require_once '../config/config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Manage Board Rooms';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Get images to delete files
        $stmt = $pdo->prepare("SELECT image_path FROM boardroom_images WHERE boardroom_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        // Delete image files
        foreach ($images as $image) {
            $file_path = '../' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete from database (images will be deleted by cascade)
        $stmt = $pdo->prepare("DELETE FROM boardrooms WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Board room deleted successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting board room";
    }
    header("Location: boardrooms.php");
    exit();
}

// Get all board rooms
$stmt = $pdo->query("
    SELECT br.*, 
           (SELECT COUNT(*) FROM boardroom_images WHERE boardroom_id = br.id) as image_count
    FROM boardrooms br
    ORDER BY br.display_order
");
$boardrooms = $stmt->fetchAll();

// Amenities list for reference
$amenities_list = [
    'projector' => 'Projector & Screen',
    'whiteboard' => 'Whiteboard',
    'wifi' => 'Free WiFi',
    'conferencing' => 'Video Conferencing',
    'catering' => 'Catering Available',
    'ac' => 'Air Conditioning',
    'sound' => 'Sound System',
    'recording' => 'Recording Facility',
    'secretarial' => 'Secretarial Services',
    'refreshments' => 'Refreshments'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Fresh Home & Suite</title>
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
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-available {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        .status-booked {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
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
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
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
                <a href="boardrooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-door-open text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Board Rooms</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-utensils text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Restaurant Menu</span>
                </a>
                <a href="gallery.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-images text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Gallery</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-photo-video text-[#C9A45A]"></i>
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
                <h1 class="text-3xl font-bold heading-gold">Manage Board Rooms</h1>
                <a href="boardroom-add.php" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-plus mr-2"></i> Add New Board Room
                </a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-500 px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Board Rooms Table -->
            <div class="card-gradient rounded-2xl p-6">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#C9A45A]/20">
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">ID</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Image</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Name</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Capacity</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Price/Hour</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Images</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Status</th>
                                <th class="text-left py-3 text-[#C9A45A] font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($boardrooms as $room): ?>
                            <tr class="border-b border-[#C9A45A]/10 table-row-hover transition-colors">
                                <td class="py-3 text-[#F5F5F5]">#<?php echo $room['id']; ?></td>
                                <td class="py-3">
                                    <?php
                                    $img_stmt = $pdo->prepare("SELECT image_path FROM boardroom_images WHERE boardroom_id = ? AND is_primary = 1 LIMIT 1");
                                    $img_stmt->execute([$room['id']]);
                                    $primary_img = $img_stmt->fetch();
                                    if($primary_img):
                                    ?>
                                    <img src="../<?php echo $primary_img['image_path']; ?>" alt="<?php echo $room['name']; ?>" class="w-16 h-16 object-cover rounded-lg">
                                    <?php else: ?>
                                    <div class="w-16 h-16 bg-[#C9A45A]/20 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-door-open text-2xl text-[#C9A45A]/50"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-[#F5F5F5] font-semibold"><?php echo $room['name']; ?></td>
                                <td class="py-3 text-[#F5F5F5]"><?php echo $room['capacity']; ?> people</td>
                                <td class="py-3 heading-gold font-semibold">₦<?php echo number_format($room['price_per_hour'], 0); ?></td>
                                <td class="py-3 text-[#F5F5F5]">
                                    <span class="bg-[#C9A45A]/20 text-[#C9A45A] px-2 py-1 rounded text-sm">
                                        <?php echo $room['image_count']; ?> images
                                    </span>
                                </td>
                                <td class="py-3">
                                    <?php if($room['is_available']): ?>
                                    <span class="status-badge status-available">Available</span>
                                    <?php else: ?>
                                    <span class="status-badge status-booked">Booked</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <div class="flex space-x-2">
                                        <a href="boardroom-edit.php?id=<?php echo $room['id']; ?>" 
                                           class="text-[#C9A45A] hover:text-[#A8843F] transition-colors p-2"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="boardroom-images.php?id=<?php echo $room['id']; ?>" 
                                           class="text-[#C9A45A] hover:text-[#A8843F] transition-colors p-2"
                                           title="Manage Images">
                                            <i class="fas fa-images"></i>
                                        </a>
                                        <a href="?delete=<?php echo $room['id']; ?>" 
                                           class="text-red-500 hover:text-red-400 transition-colors p-2"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this board room? All images will be deleted too.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($boardrooms)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-[#F5F5F5]/60">
                                    <i class="fas fa-door-open text-4xl mb-2 opacity-50"></i>
                                    <p>No board rooms added yet</p>
                                    <a href="boardroom-add.php" class="text-[#C9A45A] hover:underline mt-2 inline-block">
                                        Add your first board room
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
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