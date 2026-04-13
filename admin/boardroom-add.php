<?php
session_start();
require_once '../config/config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Add Board Room';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $capacity = $_POST['capacity'];
    $size_sqft = $_POST['size_sqft'];
    $price_per_hour = $_POST['price_per_hour'];
    $amenities = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $display_order = $_POST['display_order'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO boardrooms (name, description, capacity, size_sqft, price_per_hour, amenities, is_available, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $capacity, $size_sqft, $price_per_hour, $amenities, $is_available, $display_order]);
        
        $boardroom_id = $pdo->lastInsertId();
        
        // Handle image uploads
        if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
            $upload_dir = '../uploads/boardrooms/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $is_primary = true; // First image is primary
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] == 0) {
                    $file_name = time() . '_' . $_FILES['images']['name'][$key];
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $db_path = 'uploads/boardrooms/' . $file_name;
                        
                        $img_stmt = $pdo->prepare("INSERT INTO boardroom_images (boardroom_id, image_path, is_primary) VALUES (?, ?, ?)");
                        $img_stmt->execute([$boardroom_id, $db_path, $is_primary ? 1 : 0]);
                        
                        $is_primary = false;
                    }
                }
            }
        }
        
        $_SESSION['success'] = "Board room added successfully";
        header("Location: boardrooms.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error adding board room: " . $e->getMessage();
    }
}

// Amenities list
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
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(15, 15, 15, 0.95);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .form-card {
            background: linear-gradient(145deg, rgba(201, 164, 90, 0.1) 0%, rgba(15, 15, 15, 0.95) 100%);
            border: 1px solid rgba(201, 164, 90, 0.2);
        }
        .heading-gold {
            color: #C9A45A;
        }
        .form-input {
            background: rgba(15, 15, 15, 0.8);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
            transition: all 0.3s;
        }
        .form-input:focus {
            border-color: #C9A45A;
            outline: none;
            box-shadow: 0 0 0 2px rgba(201, 164, 90, 0.2);
        }
        .form-label {
            color: #C9A45A;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }
        .amenity-checkbox {
            accent-color: #C9A45A;
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
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold heading-gold">Add New Board Room</h1>
                <a href="boardrooms.php" class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Back to List
                </a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>
            
            <div class="form-card rounded-2xl p-8">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div>
                            <div class="mb-4">
                                <label class="form-label">Board Room Name *</label>
                                <input type="text" name="name" required 
                                       class="form-input w-full px-4 py-3 rounded-lg">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Description *</label>
                                <textarea name="description" rows="4" required 
                                          class="form-input w-full px-4 py-3 rounded-lg"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="form-label">Capacity *</label>
                                    <input type="number" name="capacity" required 
                                           class="form-input w-full px-4 py-3 rounded-lg">
                                </div>
                                <div>
                                    <label class="form-label">Size (sq ft) *</label>
                                    <input type="number" name="size_sqft" required 
                                           class="form-input w-full px-4 py-3 rounded-lg">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="form-label">Price per Hour (₦) *</label>
                                    <input type="number" name="price_per_hour" required 
                                           class="form-input w-full px-4 py-3 rounded-lg">
                                </div>
                                <div>
                                    <label class="form-label">Display Order</label>
                                    <input type="number" name="display_order" value="0" 
                                           class="form-input w-full px-4 py-3 rounded-lg">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" name="is_available" checked class="amenity-checkbox w-4 h-4">
                                    <span class="text-[#F5F5F5]">Available for booking</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div>
                            <div class="mb-4">
                                <label class="form-label">Upload Images</label>
                                <div class="border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-6 text-center">
                                    <input type="file" name="images[]" multiple accept="image/*" 
                                           class="hidden" id="imageInput">
                                    <label for="imageInput" class="cursor-pointer">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-[#C9A45A] mb-2"></i>
                                        <p class="text-[#F5F5F5]">Click to upload images</p>
                                        <p class="text-sm text-[#F5F5F5]/60">First image will be primary</p>
                                    </label>
                                    <div id="imagePreview" class="grid grid-cols-3 gap-2 mt-4"></div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label mb-2">Amenities</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php foreach($amenities_list as $value => $label): ?>
                                    <label class="flex items-center space-x-2">
                                        <input type="checkbox" name="amenities[]" value="<?php echo $value; ?>" 
                                               class="amenity-checkbox w-4 h-4">
                                        <span class="text-sm text-[#F5F5F5]"><?php echo $label; ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-8">
                        <a href="boardrooms.php" 
                           class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] px-6 py-3 rounded-lg transition-all">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-6 py-3 rounded-lg transition-all">
                            <i class="fas fa-save mr-2"></i> Save Board Room
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Image preview
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-20 object-cover rounded-lg">
                    ${i === 0 ? '<span class="absolute top-0 left-0 bg-[#C9A45A] text-[#0F0F0F] text-xs px-1 rounded">Primary</span>' : ''}
                `;
                preview.appendChild(div);
            }
            
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>