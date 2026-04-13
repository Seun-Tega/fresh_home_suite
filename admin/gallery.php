<?php
session_start();
require_once '../config/config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Gallery Management';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_image'])) {
    $category = $_POST['category'];
    $item_id = $_POST['item_id'] ?? null;
    $is_primary = isset($_POST['is_primary']) ? 1 : 0;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/gallery/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['image']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
            $db_path = 'uploads/gallery/' . $file_name;
            
            // Insert based on category
            switch($category) {
                case 'room':
                    $stmt = $pdo->prepare("INSERT INTO room_images (room_id, image_path, is_primary) VALUES (?, ?, ?)");
                    $stmt->execute([$item_id, $db_path, $is_primary]);
                    break;
                case 'hall':
                    $stmt = $pdo->prepare("INSERT INTO hall_images (hall_id, image_path, is_primary) VALUES (?, ?, ?)");
                    $stmt->execute([$item_id, $db_path, $is_primary]);
                    break;
                case 'boardroom':
                    $stmt = $pdo->prepare("INSERT INTO boardroom_images (boardroom_id, image_path, is_primary) VALUES (?, ?, ?)");
                    $stmt->execute([$item_id, $db_path, $is_primary]);
                    break;
                case 'food':
                    $stmt = $pdo->prepare("UPDATE food_items SET image_path = ? WHERE id = ?");
                    $stmt->execute([$db_path, $item_id]);
                    break;
                case 'general':
                    // For general gallery images, create a gallery table if needed
                    $stmt = $pdo->prepare("INSERT INTO gallery_images (category, image_path, title) VALUES (?, ?, ?)");
                    $stmt->execute([$category, $db_path, $_POST['title'] ?? '']);
                    break;
            }
            
            $_SESSION['success'] = "Image uploaded successfully";
        } else {
            $_SESSION['error'] = "Error uploading image";
        }
    }
    
    header("Location: gallery.php");
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];
    $image_path = $_GET['path'];
    
    // Delete file
    $file_path = '../' . $image_path;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete from database
    switch($type) {
        case 'room':
            $stmt = $pdo->prepare("DELETE FROM room_images WHERE id = ?");
            break;
        case 'hall':
            $stmt = $pdo->prepare("DELETE FROM hall_images WHERE id = ?");
            break;
        case 'boardroom':
            $stmt = $pdo->prepare("DELETE FROM boardroom_images WHERE id = ?");
            break;
        case 'food':
            $stmt = $pdo->prepare("UPDATE food_items SET image_path = NULL WHERE id = ?");
            break;
    }
    
    if (isset($stmt)) {
        $stmt->execute([$id]);
    }
    
    $_SESSION['success'] = "Image deleted successfully";
    header("Location: gallery.php");
    exit();
}

// Get all images for gallery
$stmt = $pdo->query("
    SELECT 'room' as source, ri.id, ri.image_path, ri.is_primary, r.room_type as title 
    FROM room_images ri
    JOIN rooms r ON ri.room_id = r.id
    UNION ALL
    SELECT 'hall' as source, hi.id, hi.image_path, hi.is_primary, 'Event Hall' as title
    FROM hall_images hi
    UNION ALL
    SELECT 'boardroom' as source, bri.id, bri.image_path, bri.is_primary, br.name as title
    FROM boardroom_images bri
    JOIN boardrooms br ON bri.boardroom_id = br.id
    UNION ALL
    SELECT 'food' as source, fi.id, fi.image_path, 0 as is_primary, fi.name as title
    FROM food_items fi
    WHERE fi.image_path IS NOT NULL
    ORDER BY source, is_primary DESC
");
$gallery_images = $stmt->fetchAll();

// Get items for dropdowns
$rooms = $pdo->query("SELECT id, room_type FROM rooms")->fetchAll();
$boardrooms = $pdo->query("SELECT id, name FROM boardrooms")->fetchAll();
$food_items = $pdo->query("SELECT id, name FROM food_items")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 0.5rem;
        }
        .gallery-item img {
            transition: transform 0.3s ease;
        }
        .gallery-item:hover img {
            transform: scale(1.05);
        }
        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }
        .primary-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: #C9A45A;
            color: #0F0F0F;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar (same as boardrooms.php) -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <!-- ... same sidebar code as boardrooms.php ... -->
        </div>
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
            <h1 class="text-3xl font-bold heading-gold mb-8">Gallery Management</h1>
            
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
            
            <!-- Upload Form -->
            <div class="card-gradient rounded-2xl p-6 mb-8">
                <h2 class="text-xl font-bold heading-gold mb-4">Upload New Image</h2>
                
                <form action="" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[#C9A45A] text-sm mb-2">Category *</label>
                        <select name="category" id="categorySelect" required 
                                class="w-full bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-lg px-4 py-2 text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                            <option value="">Select Category</option>
                            <option value="room">Room</option>
                            <option value="hall">Event Hall</option>
                            <option value="boardroom">Board Room</option>
                            <option value="food">Food Item</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    
                    <div id="itemSelectDiv" class="hidden">
                        <label class="block text-[#C9A45A] text-sm mb-2">Select Item *</label>
                        <select name="item_id" id="itemSelect" class="w-full bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-lg px-4 py-2 text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                            <option value="">Select Item</option>
                            <!-- Rooms -->
                            <optgroup label="Rooms" id="roomsGroup" class="hidden">
                                <?php foreach($rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>" data-category="room"><?php echo $room['room_type']; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <!-- Board Rooms -->
                            <optgroup label="Board Rooms" id="boardroomsGroup" class="hidden">
                                <?php foreach($boardrooms as $br): ?>
                                <option value="<?php echo $br['id']; ?>" data-category="boardroom"><?php echo $br['name']; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <!-- Food Items -->
                            <optgroup label="Food Items" id="foodGroup" class="hidden">
                                <?php foreach($food_items as $food): ?>
                                <option value="<?php echo $food['id']; ?>" data-category="food"><?php echo $food['name']; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div id="titleDiv" class="hidden">
                        <label class="block text-[#C9A45A] text-sm mb-2">Image Title</label>
                        <input type="text" name="title" class="w-full bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-lg px-4 py-2 text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-[#C9A45A] text-sm mb-2">Image *</label>
                        <input type="file" name="image" accept="image/*" required 
                               class="w-full bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-lg px-4 py-2 text-[#F5F5F5] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:bg-[#C9A45A] file:text-[#0F0F0F] hover:file:bg-[#A8843F]">
                    </div>
                    
                    <div class="flex items-center">
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_primary" class="w-4 h-4 accent-[#C9A45A]">
                            <span class="text-[#F5F5F5]">Set as Primary Image</span>
                        </label>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" name="upload_image" 
                                class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-6 py-2 rounded-lg transition-all">
                            <i class="fas fa-upload mr-2"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Gallery Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($gallery_images as $image): ?>
                <div class="gallery-item card-gradient p-2">
                    <img src="../<?php echo $image['image_path']; ?>" alt="<?php echo $image['title']; ?>" 
                         class="w-full h-32 object-cover rounded-lg">
                    
                    <?php if($image['is_primary']): ?>
                    <span class="primary-badge">PRIMARY</span>
                    <?php endif; ?>
                    
                    <div class="gallery-overlay">
                        <div class="text-center">
                            <p class="text-xs mb-2"><?php echo $image['title']; ?></p>
                            <a href="?delete&type=<?php echo $image['source']; ?>&id=<?php echo $image['id']; ?>&path=<?php echo urlencode($image['image_path']); ?>" 
                               class="text-red-500 hover:text-red-400"
                               onclick="return confirm('Delete this image?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if(empty($gallery_images)): ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-6xl text-[#C9A45A]/30 mb-4"></i>
                <h3 class="text-2xl text-[#F5F5F5] mb-2">No Images Yet</h3>
                <p class="text-[#F5F5F5]/60">Upload your first image using the form above</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    // Show/hide fields based on category
    document.getElementById('categorySelect').addEventListener('change', function() {
        const category = this.value;
        const itemDiv = document.getElementById('itemSelectDiv');
        const titleDiv = document.getElementById('titleDiv');
        const roomsGroup = document.getElementById('roomsGroup');
        const boardroomsGroup = document.getElementById('boardroomsGroup');
        const foodGroup = document.getElementById('foodGroup');
        const itemSelect = document.getElementById('itemSelect');
        
        // Hide all groups
        roomsGroup.classList.add('hidden');
        boardroomsGroup.classList.add('hidden');
        foodGroup.classList.add('hidden');
        
        if (category === 'general') {
            itemDiv.classList.add('hidden');
            titleDiv.classList.remove('hidden');
            itemSelect.required = false;
        } else if (category === 'hall') {
            itemDiv.classList.add('hidden');
            titleDiv.classList.add('hidden');
            itemSelect.required = false;
        } else if (category) {
            itemDiv.classList.remove('hidden');
            titleDiv.classList.add('hidden');
            itemSelect.required = true;
            
            // Show relevant group
            if (category === 'room') {
                roomsGroup.classList.remove('hidden');
            } else if (category === 'boardroom') {
                boardroomsGroup.classList.remove('hidden');
            } else if (category === 'food') {
                foodGroup.classList.remove('hidden');
            }
        } else {
            itemDiv.classList.add('hidden');
            titleDiv.classList.add('hidden');
            itemSelect.required = false;
        }
    });
    </script>
</body>
</html>