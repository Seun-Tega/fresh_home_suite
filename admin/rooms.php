<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle room operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_room'])) {
        // Add room logic
        $room_number = $_POST['room_number'];
        $room_type = $_POST['room_type'];
        $description = $_POST['description'];
        $base_price = $_POST['base_price'];
        $max_occupancy = $_POST['max_occupancy'];
        $bed_type = $_POST['bed_type'];
        $square_feet = $_POST['square_feet'];
        $amenities = $_POST['amenities'];
        
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, description, base_price, max_occupancy, bed_type, square_feet, amenities) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_number, $room_type, $description, $base_price, $max_occupancy, $bed_type, $square_feet, $amenities]);
        
        $room_id = $pdo->lastInsertId();
        
        // Handle image uploads
        if (isset($_FILES['room_images']) && !empty($_FILES['room_images']['name'][0])) {
            $upload_dir = '../uploads/rooms/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $files = $_FILES['room_images'];
            $is_primary = true; // First image is primary
            
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] == 0) {
                    $file_name = time() . '_' . $i . '_' . basename($files['name'][$i]);
                    $target_path = $upload_dir . $file_name;
                    $db_path = 'uploads/rooms/' . $file_name;
                    
                    // Check file type
                    $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($imageFileType, $allowed_types)) {
                        if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                            // Insert into room_images table
                            $img_stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                            $img_stmt->execute([$room_id, $db_path, $is_primary ? 1 : 0]);
                            $is_primary = false; // Only first image is primary
                        }
                    }
                }
            }
        }
        
        $_SESSION['success'] = "Room added successfully with images!";
        header("Location: rooms.php");
        exit();
    }
    
    if (isset($_POST['edit_room'])) {
        // Edit room logic
        $id = $_POST['id'];
        $room_number = $_POST['room_number'];
        $room_type = $_POST['room_type'];
        $description = $_POST['description'];
        $base_price = $_POST['base_price'];
        $max_occupancy = $_POST['max_occupancy'];
        $bed_type = $_POST['bed_type'];
        $square_feet = $_POST['square_feet'];
        $amenities = $_POST['amenities'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, description=?, base_price=?, max_occupancy=?, bed_type=?, square_feet=?, amenities=?, status=? WHERE id=?");
        $stmt->execute([$room_number, $room_type, $description, $base_price, $max_occupancy, $bed_type, $square_feet, $amenities, $status, $id]);
        
        // Handle new image uploads
        if (isset($_FILES['new_room_images']) && !empty($_FILES['new_room_images']['name'][0])) {
            $upload_dir = '../uploads/rooms/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $files = $_FILES['new_room_images'];
            
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] == 0) {
                    $file_name = time() . '_' . $i . '_' . basename($files['name'][$i]);
                    $target_path = $upload_dir . $file_name;
                    $db_path = 'uploads/rooms/' . $file_name;
                    
                    $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($imageFileType, $allowed_types)) {
                        if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                            $img_stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, 0)");
                            $img_stmt->execute([$id, $db_path]);
                        }
                    }
                }
            }
        }
        
        $_SESSION['success'] = "Room updated successfully!";
        header("Location: rooms.php");
        exit();
    }
    
    if (isset($_POST['delete_room'])) {
        $id = $_POST['id'];
        
        // Get images to delete files
        $img_stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ?");
        $img_stmt->execute([$id]);
        $images = $img_stmt->fetchAll();
        
        // Delete image files
        foreach ($images as $image) {
            $file_path = '../' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Delete database records (foreign key will handle this if set to CASCADE)
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Room deleted successfully!";
        header("Location: rooms.php");
        exit();
    }
    
    if (isset($_POST['delete_room_image'])) {
        $image_id = $_POST['image_id'];
        $room_id = $_POST['room_id'];
        
        // Get image path
        $img_stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE id = ?");
        $img_stmt->execute([$image_id]);
        $image = $img_stmt->fetch();
        
        if ($image) {
            // Delete file
            $file_path = '../' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete database record
            $del_stmt = $pdo->prepare("DELETE FROM room_images WHERE id = ?");
            $del_stmt->execute([$image_id]);
        }
        
        header("Location: rooms.php");
        exit();
    }
    
    if (isset($_POST['set_primary_image'])) {
        $image_id = $_POST['image_id'];
        $room_id = $_POST['room_id'];
        
        // Remove primary from all images of this room
        $reset_stmt = $pdo->prepare("UPDATE room_images SET is_primary = 0 WHERE room_id = ?");
        $reset_stmt->execute([$room_id]);
        
        // Set new primary
        $primary_stmt = $pdo->prepare("UPDATE room_images SET is_primary = 1 WHERE id = ?");
        $primary_stmt->execute([$image_id]);
        
        header("Location: rooms.php");
        exit();
    }
}

// Get all rooms with their primary image
$stmt = $pdo->query("
    SELECT r.*, 
           (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM rooms r 
    ORDER BY r.room_number
");
$rooms = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Fresh Home & Suite</title>
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
        .modal {
            background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(10px);
        }
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #C9A45A;
        }
        .primary-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #C9A45A;
            color: #0F0F0F;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar (same as before) -->
        <div class="sidebar w-64 text-white p-6 overflow-y-auto">
            <!-- ... sidebar content (keep your existing sidebar) ... -->
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
                <h2 class="text-xl font-bold text-[#C9A45A]">Admin Panel</h2>
                <p class="text-sm text-white/60">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
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
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Receipts</span>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 text-[#C9A45A]">
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
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">Room Management</h1>
                <button onclick="showAddModal()" 
                        class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                    <i class="fas fa-plus mr-2"></i> Add New Room
                </button>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Rooms Table with Images -->
            <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-white">
                        <thead>
                            <tr class="border-b border-[#C9A45A]/20">
                                <th class="text-left py-3">Image</th>
                                <th class="text-left py-3">Room #</th>
                                <th class="text-left py-3">Type</th>
                                <th class="text-left py-3">Price</th>
                                <th class="text-left py-3">Max</th>
                                <th class="text-left py-3">Bed</th>
                                <th class="text-left py-3">Size</th>
                                <th class="text-left py-3">Status</th>
                                <th class="text-left py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rooms as $room): ?>
                            <tr class="border-b border-white/10 hover:bg-white/5">
                                <td class="py-3">
                                    <?php if($room['primary_image']): ?>
                                        <img src="../<?php echo $room['primary_image']; ?>" 
                                             alt="<?php echo $room['room_type']; ?>"
                                             class="w-16 h-16 object-cover rounded-lg border-2 border-[#C9A45A]">
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-[#C9A45A]/10 rounded-lg flex items-center justify-center border border-[#C9A45A]/20">
                                            <i class="fas fa-image text-[#C9A45A] text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3"><?php echo $room['room_number']; ?></td>
                                <td class="py-3"><?php echo $room['room_type']; ?></td>
                                <td class="py-3 text-[#C9A45A] font-bold"><?php echo formatCurrency($room['base_price']); ?></td>
                                <td class="py-3"><?php echo $room['max_occupancy']; ?></td>
                                <td class="py-3"><?php echo $room['bed_type']; ?></td>
                                <td class="py-3"><?php echo $room['square_feet']; ?> ft²</td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $room['status'] == 'available' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?>">
                                        <?php echo ucfirst($room['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($room)); ?>)" 
                                            class="text-[#C9A45A] hover:text-[#A8843F] mr-3 transition" title="Edit Room">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="showImagesModal(<?php echo $room['id']; ?>, '<?php echo $room['room_type']; ?>')" 
                                            class="text-blue-400 hover:text-blue-300 mr-3 transition" title="Manage Images">
                                        <i class="fas fa-images"></i>
                                    </button>
                                    <button onclick="showDeleteModal(<?php echo $room['id']; ?>)" 
                                            class="text-red-400 hover:text-red-300 transition" title="Delete Room">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Room Modal with Image Upload -->
    <div id="addModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add New Room</h3>
                <button onclick="hideAddModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white/70 mb-2">Room Number *</label>
                        <input type="text" name="room_number" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Room Type *</label>
                        <input type="text" name="room_type" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Description *</label>
                        <textarea name="description" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Base Price (₦) *</label>
                        <input type="number" name="base_price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Max Occupancy *</label>
                        <input type="number" name="max_occupancy" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Bed Type *</label>
                        <input type="text" name="bed_type" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Square Feet *</label>
                        <input type="number" name="square_feet" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Amenities (comma separated) *</label>
                        <input type="text" name="amenities" required 
                               placeholder="TV, WiFi, AC, Mini Bar"
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <!-- Image Upload Section -->
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Room Images</label>
                        <div class="border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-6 text-center hover:border-[#C9A45A] transition cursor-pointer" onclick="document.getElementById('roomImages').click()">
                            <i class="fas fa-cloud-upload-alt text-4xl text-[#C9A45A] mb-2"></i>
                            <p class="text-white/70">Click to upload or drag and drop</p>
                            <p class="text-white/50 text-sm mt-1">JPG, PNG, GIF (Max 5MB each)</p>
                            <input type="file" id="roomImages" name="room_images[]" multiple accept="image/*" class="hidden" onchange="previewImages(this)">
                        </div>
                        
                        <!-- Image Preview Container -->
                        <div id="imagePreviewContainer" class="grid grid-cols-4 gap-4 mt-4"></div>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_room"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Add Room
                    </button>
                    <button type="button" onclick="hideAddModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Room Modal with Image Management -->
    <div id="editModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit Room</h3>
                <button onclick="hideEditModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Existing fields (same as add modal) -->
                    <div>
                        <label class="block text-white/70 mb-2">Room Number *</label>
                        <input type="text" name="room_number" id="edit_room_number" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Room Type *</label>
                        <input type="text" name="room_type" id="edit_room_type" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Description *</label>
                        <textarea name="description" id="edit_description" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Base Price (₦) *</label>
                        <input type="number" name="base_price" id="edit_base_price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Max Occupancy *</label>
                        <input type="number" name="max_occupancy" id="edit_max_occupancy" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Bed Type *</label>
                        <input type="text" name="bed_type" id="edit_bed_type" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Square Feet *</label>
                        <input type="number" name="square_feet" id="edit_square_feet" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Amenities *</label>
                        <input type="text" name="amenities" id="edit_amenities" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Status</label>
                        <select name="status" id="edit_status" class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white">
                            <option value="available">Available</option>
                            <option value="maintenance">Under Maintenance</option>
                        </select>
                    </div>
                    
                    <!-- Current Images Display -->
                   <td class="py-3">
    <?php if($room['primary_image']): ?>
        <img src="<?php echo SITE_URL . $room['primary_image']; ?>" 
             alt="<?php echo $room['room_type']; ?>"
             class="w-16 h-16 object-cover rounded-lg border-2 border-[#C9A45A]">
    <?php else: ?>
        <div class="w-16 h-16 bg-[#C9A45A]/10 rounded-lg flex items-center justify-center border border-[#C9A45A]/20">
            <i class="fas fa-image text-[#C9A45A] text-2xl"></i>
        </div>
    <?php endif; ?>
</td>
                    <!-- Add New Images -->
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Add New Images</label>
                        <div class="border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-6 text-center hover:border-[#C9A45A] transition cursor-pointer" onclick="document.getElementById('newRoomImages').click()">
                            <i class="fas fa-cloud-upload-alt text-4xl text-[#C9A45A] mb-2"></i>
                            <p class="text-white/70">Click to add more images</p>
                            <input type="file" id="newRoomImages" name="new_room_images[]" multiple accept="image/*" class="hidden" onchange="previewNewImages(this)">
                        </div>
                        
                        <!-- New Images Preview -->
                        <div id="newImagesPreview" class="grid grid-cols-4 gap-4 mt-4"></div>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_room"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Update Room
                    </button>
                    <button type="button" onclick="hideEditModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Image Management Modal -->
    <div id="imagesModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Manage Images - <span id="modalRoomName"></span></h3>
                <button onclick="hideImagesModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <div id="roomImagesGrid" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <!-- Images will be loaded here via JavaScript -->
            </div>
            
            <div class="mt-6 text-center">
                <button onclick="hideImagesModal()" 
                        class="px-6 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
                    Close
                </button>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Delete Room?</h3>
                <p class="text-white/60 mb-6">This action cannot be undone. All images will be deleted.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_id">
                    <button type="submit" name="delete_room"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Image preview function for add modal
    function previewImages(input) {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';
        
        if (input.files) {
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border-2 border-[#C9A45A]">
                        <span class="absolute -top-2 -right-2 bg-[#C9A45A] text-[#0F0F0F] w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold">${i+1}</span>
                    `;
                    container.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Preview new images in edit modal
    function previewNewImages(input) {
        const container = document.getElementById('newImagesPreview');
        container.innerHTML = '';
        
        if (input.files) {
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border-2 border-[#C9A45A]">
                        <span class="absolute top-1 right-1 bg-green-500 text-white text-xs px-1 rounded">New</span>
                    `;
                    container.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
        }
    }
    
    // Load room images for management modal
    function loadRoomImages(roomId) {
        fetch(`get-room-images.php?room_id=${roomId}`)
            .then(response => response.json())
            .then(images => {
                const grid = document.getElementById('roomImagesGrid');
                grid.innerHTML = '';
                
                images.forEach(img => {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                        <img src="../${img.image_path}" class="w-full h-32 object-cover rounded-lg border-2 ${img.is_primary ? 'border-[#C9A45A]' : 'border-white/20'}">
                        ${img.is_primary ? '<span class="absolute top-2 left-2 bg-[#C9A45A] text-[#0F0F0F] text-xs px-2 py-1 rounded-full">PRIMARY</span>' : ''}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2 rounded-lg">
                            ${!img.is_primary ? `
                                <form method="POST" class="inline">
                                    <input type="hidden" name="image_id" value="${img.id}">
                                    <input type="hidden" name="room_id" value="${roomId}">
                                    <button type="submit" name="set_primary_image" class="bg-[#C9A45A] text-[#0F0F0F] p-2 rounded-full hover:bg-[#A8843F]">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                            ` : ''}
                            <form method="POST" class="inline" onsubmit="return confirm('Delete this image?')">
                                <input type="hidden" name="image_id" value="${img.id}">
                                <input type="hidden" name="room_id" value="${roomId}">
                                <button type="submit" name="delete_room_image" class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    `;
                    grid.appendChild(div);
                });
            });
    }
    
    // Show images modal
    function showImagesModal(roomId, roomName) {
        document.getElementById('modalRoomName').textContent = roomName;
        document.getElementById('imagesModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        loadRoomImages(roomId);
    }
    
    function hideImagesModal() {
        document.getElementById('imagesModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Add modal functions
    function showAddModal() {
        document.getElementById('addModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideAddModal() {
        document.getElementById('addModal').style.display = 'none';
        document.getElementById('imagePreviewContainer').innerHTML = '';
        document.body.style.overflow = 'auto';
    }
    
    // Edit modal functions
    function showEditModal(room) {
        document.getElementById('edit_id').value = room.id;
        document.getElementById('edit_room_number').value = room.room_number;
        document.getElementById('edit_room_type').value = room.room_type;
        document.getElementById('edit_description').value = room.description;
        document.getElementById('edit_base_price').value = room.base_price;
        document.getElementById('edit_max_occupancy').value = room.max_occupancy;
        document.getElementById('edit_bed_type').value = room.bed_type;
        document.getElementById('edit_square_feet').value = room.square_feet;
        document.getElementById('edit_amenities').value = room.amenities;
        document.getElementById('edit_status').value = room.status;
        
        // Load current images
        fetch(`get-room-images.php?room_id=${room.id}`)
            .then(response => response.json())
            .then(images => {
                const container = document.getElementById('currentImagesContainer');
                container.innerHTML = '';
                
                images.forEach(img => {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="../${img.image_path}" class="w-full h-24 object-cover rounded-lg border-2 ${img.is_primary ? 'border-[#C9A45A]' : 'border-white/20'}">
                        ${img.is_primary ? '<span class="absolute top-1 left-1 bg-[#C9A45A] text-[#0F0F0F] text-xs px-1 rounded">Primary</span>' : ''}
                    `;
                    container.appendChild(div);
                });
            });
        
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.getElementById('newImagesPreview').innerHTML = '';
        document.body.style.overflow = 'auto';
    }
    
    // Delete modal functions
    function showDeleteModal(id) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target === this) hideAddModal();
    });
    
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditModal();
    });
    
    document.getElementById('imagesModal').addEventListener('click', function(e) {
        if (e.target === this) hideImagesModal();
    });
    
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteModal();
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