<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle image upload
if (isset($_POST['upload_hall_images'])) {
    $hall_id = $_POST['hall_id'];
    
    if (isset($_FILES['hall_images']) && !empty($_FILES['hall_images']['name'][0])) {
        $upload_dir = '../uploads/hall/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $files = $_FILES['hall_images'];
        $upload_count = 0;
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == 0) {
                $file_name = time() . '_' . $i . '_' . basename($files['name'][$i]);
                $target_path = $upload_dir . $file_name;
                $db_path = 'uploads/hall/' . $file_name;
                
                // Check file type
                $imageFileType = strtolower(pathinfo($target_path, PATHINFO_EXTENSION));
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($imageFileType, $allowed_types)) {
                    if (move_uploaded_file($files['tmp_name'][$i], $target_path)) {
                        // Insert into hall_images table
                        $img_stmt = $pdo->prepare("INSERT INTO hall_images (hall_id, image_path) VALUES (?, ?)");
                        $img_stmt->execute([$hall_id, $db_path]);
                        $upload_count++;
                    }
                }
            }
        }
        
        $_SESSION['success'] = "$upload_count image(s) uploaded successfully!";
        header("Location: hall.php");
        exit();
    }
}

// Handle image deletion
if (isset($_POST['delete_hall_image'])) {
    $image_id = $_POST['image_id'];
    
    // Get image path
    $img_stmt = $pdo->prepare("SELECT image_path FROM hall_images WHERE id = ?");
    $img_stmt->execute([$image_id]);
    $image = $img_stmt->fetch();
    
    if ($image) {
        // Delete file
        $file_path = '../' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete database record
        $del_stmt = $pdo->prepare("DELETE FROM hall_images WHERE id = ?");
        $del_stmt->execute([$image_id]);
        
        $_SESSION['success'] = "Image deleted successfully!";
    }
    
    header("Location: hall.php");
    exit();
}

// Handle hall operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_hall'])) {
        // Update hall details
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $base_price_hourly = $_POST['base_price_hourly'];
        $base_price_half_day = $_POST['base_price_half_day'];
        $base_price_full_day = $_POST['base_price_full_day'];
        $capacity_theater = $_POST['capacity_theater'];
        $capacity_banquet = $_POST['capacity_banquet'];
        $capacity_classroom = $_POST['capacity_classroom'];
        $amenities = $_POST['amenities'];
        $status = $_POST['status'];
        
        $stmt = $pdo->prepare("UPDATE hall SET name=?, description=?, base_price_hourly=?, base_price_half_day=?, base_price_full_day=?, capacity_theater=?, capacity_banquet=?, capacity_classroom=?, amenities=?, status=? WHERE id=?");
        $stmt->execute([$name, $description, $base_price_hourly, $base_price_half_day, $base_price_full_day, $capacity_theater, $capacity_banquet, $capacity_classroom, $amenities, $status, $id]);
        
        $_SESSION['success'] = "Hall details updated successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['add_package'])) {
        // Add event package
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $amenities_included = $_POST['amenities_included'];
        
        $stmt = $pdo->prepare("INSERT INTO hall_packages (name, description, price, amenities_included) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $amenities_included]);
        
        $_SESSION['success'] = "Package added successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['edit_package'])) {
        // Edit event package
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $amenities_included = $_POST['amenities_included'];
        
        $stmt = $pdo->prepare("UPDATE hall_packages SET name=?, description=?, price=?, amenities_included=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $amenities_included, $id]);
        
        $_SESSION['success'] = "Package updated successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['delete_package'])) {
        // Delete event package
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM hall_packages WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Package deleted successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['add_amenity'])) {
        // Add hall amenity
        $name = $_POST['name'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO hall_amenities (name, price, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $description]);
        
        $_SESSION['success'] = "Amenity added successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['edit_amenity'])) {
        // Edit hall amenity
        $id = $_POST['id'];
        $name = $_POST['name'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE hall_amenities SET name=?, price=?, description=?, is_available=? WHERE id=?");
        $stmt->execute([$name, $price, $description, $is_available, $id]);
        
        $_SESSION['success'] = "Amenity updated successfully!";
        header("Location: hall.php");
        exit();
    }
    
    if (isset($_POST['delete_amenity'])) {
        // Delete hall amenity
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM hall_amenities WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Amenity deleted successfully!";
        header("Location: hall.php");
        exit();
    }
}

// Get hall details
$stmt = $pdo->query("SELECT * FROM hall WHERE id = 1");
$hall = $stmt->fetch();

// If no hall exists, create default
if (!$hall) {
    $stmt = $pdo->prepare("INSERT INTO hall (id, name, description, base_price_hourly, base_price_half_day, base_price_full_day, capacity_theater, capacity_banquet, capacity_classroom, amenities, status) VALUES (1, 'Grand Event Hall', 'Perfect for weddings, conferences, and special occasions', 5000, 15000, 25000, 200, 150, 120, 'Sound System, Projector, AC, Lighting', 'available')");
    $stmt->execute();
    
    // Fetch again
    $stmt = $pdo->query("SELECT * FROM hall WHERE id = 1");
    $hall = $stmt->fetch();
}

// Get hall packages
$stmt = $pdo->query("SELECT * FROM hall_packages ORDER BY price");
$packages = $stmt->fetchAll();

// Get hall amenities
$stmt = $pdo->query("SELECT * FROM hall_amenities ORDER BY name");
$amenities = $stmt->fetchAll();

// Get hall images
$stmt = $pdo->query("SELECT * FROM hall_images WHERE hall_id = 1 ORDER BY id DESC");
$images = $stmt->fetchAll();

// Get hall bookings
$stmt = $pdo->query("
    SELECT hb.*, h.name as hall_name, g.full_name, g.email, g.phone
    FROM hall_bookings hb
    JOIN hall h ON hb.hall_id = h.id
    JOIN guests g ON hb.guest_id = g.id
    ORDER BY hb.booking_date DESC, hb.start_time DESC
    LIMIT 20
");
$hall_bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Management - Fresh Home & Suite</title>
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
        .tab-btn.active {
            background: rgba(201, 164, 90, 0.2);
            color: #C9A45A;
            border-color: #C9A45A;
        }
        .image-preview {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #C9A45A;
        }
        .upload-area {
            border: 2px dashed rgba(201, 164, 90, 0.3);
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #C9A45A;
            background: rgba(201, 164, 90, 0.05);
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 text-white p-6 overflow-y-auto">
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
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-bed w-5"></i>
                    <span>Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 text-[#C9A45A]">
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
            <h1 class="text-3xl font-bold text-white mb-8">Event Hall Management</h1>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2 border-b border-[#C9A45A]/20">
                    <button onclick="showTab('details')" class="tab-btn active px-6 py-3 text-[#C9A45A] border-b-2 border-[#C9A45A] font-medium">
                        Hall Details
                    </button>
                    <button onclick="showTab('images')" class="tab-btn px-6 py-3 text-white/70 hover:text-[#C9A45A] font-medium">
                        Gallery
                    </button>
                    <button onclick="showTab('packages')" class="tab-btn px-6 py-3 text-white/70 hover:text-[#C9A45A] font-medium">
                        Event Packages
                    </button>
                    <button onclick="showTab('amenities')" class="tab-btn px-6 py-3 text-white/70 hover:text-[#C9A45A] font-medium">
                        Amenities
                    </button>
                    <button onclick="showTab('bookings')" class="tab-btn px-6 py-3 text-white/70 hover:text-[#C9A45A] font-medium">
                        Hall Bookings
                    </button>
                </div>
            </div>
            
            <!-- Hall Details Tab -->
            <div id="detailsTab" class="tab-content">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-[#C9A45A] mb-6">Hall Information</h2>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="id" value="<?php echo $hall['id']; ?>">
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-white/70 mb-2">Hall Name *</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($hall['name']); ?>" required
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white">
                                    <option value="available" <?php echo $hall['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="maintenance" <?php echo $hall['status'] == 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-white/70 mb-2">Description *</label>
                                <textarea name="description" rows="4" required
                                          class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"><?php echo htmlspecialchars($hall['description']); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Hourly Rate (₦) *</label>
                                <input type="number" name="base_price_hourly" value="<?php echo $hall['base_price_hourly']; ?>" required
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Half Day Rate (₦) *</label>
                                <input type="number" name="base_price_half_day" value="<?php echo $hall['base_price_half_day']; ?>" required
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Full Day Rate (₦) *</label>
                                <input type="number" name="base_price_full_day" value="<?php echo $hall['base_price_full_day']; ?>" required
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Theater Capacity</label>
                                <input type="number" name="capacity_theater" value="<?php echo $hall['capacity_theater']; ?>"
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Banquet Capacity</label>
                                <input type="number" name="capacity_banquet" value="<?php echo $hall['capacity_banquet']; ?>"
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div>
                                <label class="block text-white/70 mb-2">Classroom Capacity</label>
                                <input type="number" name="capacity_classroom" value="<?php echo $hall['capacity_classroom']; ?>"
                                       class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-white/70 mb-2">Amenities (comma separated)</label>
                                <textarea name="amenities" rows="3"
                                          class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"><?php echo htmlspecialchars($hall['amenities']); ?></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_hall"
                                class="bg-[#C9A45A] text-[#0F0F0F] px-8 py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                            Update Hall Details
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Gallery Tab -->
            <div id="imagesTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-[#C9A45A]">Hall Gallery</h2>
                        <button onclick="showUploadModal()" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-4 py-2 rounded-lg hover:bg-[#A8843F] transition font-medium">
                            <i class="fas fa-upload mr-2"></i> Upload Images
                        </button>
                    </div>
                    
                    <?php if (empty($images)): ?>
                    <div class="text-center py-12 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                        <i class="fas fa-images text-5xl text-[#C9A45A]/50 mb-3"></i>
                        <p class="text-white/70">No images uploaded yet</p>
                        <p class="text-white/50 text-sm mt-2">Click the Upload button to add hall images</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <?php foreach($images as $image): 
                            $image_path = '../' . str_replace(['../', './'], '', $image['image_path']);
                        ?>
                        <div class="relative group bg-black/20 rounded-lg overflow-hidden border border-[#C9A45A]/20">
                            <img src="<?php echo $image_path; ?>" alt="Hall Image" 
                                 class="w-full h-40 object-cover"
                                 onerror="this.src='../assets/images/no-image.jpg';">
                            <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                                <button onclick="showImageModal('<?php echo $image_path; ?>')" 
                                        class="bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Delete this image?')">
                                    <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                    <button type="submit" name="delete_hall_image" 
                                            class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Event Packages Tab -->
            <div id="packagesTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-[#C9A45A]">Event Packages</h2>
                        <button onclick="showPackageModal()" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-4 py-2 rounded-lg hover:bg-[#A8843F] transition font-medium">
                            <i class="fas fa-plus mr-2"></i> Add Package
                        </button>
                    </div>
                    
                    <?php if (empty($packages)): ?>
                    <div class="text-center py-12 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                        <i class="fas fa-gift text-5xl text-[#C9A45A]/50 mb-3"></i>
                        <p class="text-white/70">No packages added yet</p>
                        <p class="text-white/50 text-sm mt-2">Click the Add Package button to create event packages</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($packages as $package): ?>
                        <div class="bg-black/30 border border-[#C9A45A]/20 rounded-xl p-6 hover:border-[#C9A45A] transition">
                            <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($package['name']); ?></h3>
                            <p class="text-[#C9A45A] text-2xl font-bold mb-3"><?php echo formatCurrency($package['price']); ?></p>
                            <p class="text-white/70 mb-4 text-sm"><?php echo htmlspecialchars($package['description']); ?></p>
                            
                            <div class="mb-4">
                                <p class="text-white/90 font-semibold mb-2 text-sm">Includes:</p>
                                <ul class="space-y-1">
                                    <?php 
                                    $items = explode(',', $package['amenities_included']);
                                    foreach($items as $item): 
                                    ?>
                                    <li class="text-white/60 text-xs flex items-center">
                                        <i class="fas fa-check-circle text-[#C9A45A] mr-2 text-xs"></i>
                                        <?php echo trim($item); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="flex gap-2 mt-4">
                                <button onclick="showEditPackageModal(<?php echo htmlspecialchars(json_encode($package)); ?>)" 
                                        class="flex-1 bg-blue-500/20 text-blue-300 border border-blue-500/30 px-3 py-2 rounded hover:bg-blue-500/30 transition text-sm">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button onclick="showDeletePackageModal(<?php echo $package['id']; ?>)" 
                                        class="flex-1 bg-red-500/20 text-red-300 border border-red-500/30 px-3 py-2 rounded hover:bg-red-500/30 transition text-sm">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Amenities Tab -->
            <div id="amenitiesTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-[#C9A45A]">Hall Amenities</h2>
                        <button onclick="showAmenityModal()" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-4 py-2 rounded-lg hover:bg-[#A8843F] transition font-medium">
                            <i class="fas fa-plus mr-2"></i> Add Amenity
                        </button>
                    </div>
                    
                    <?php if (empty($amenities)): ?>
                    <div class="text-center py-12 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                        <i class="fas fa-couch text-5xl text-[#C9A45A]/50 mb-3"></i>
                        <p class="text-white/70">No amenities added yet</p>
                        <p class="text-white/50 text-sm mt-2">Click the Add Amenity button to add hall amenities</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A]">Name</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Price</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Description</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Status</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($amenities as $amenity): ?>
                                <tr class="border-b border-white/10 hover:bg-white/5">
                                    <td class="py-3"><?php echo htmlspecialchars($amenity['name']); ?></td>
                                    <td class="py-3 text-[#C9A45A]"><?php echo formatCurrency($amenity['price']); ?></td>
                                    <td class="py-3 text-white/70 text-sm"><?php echo htmlspecialchars($amenity['description']); ?></td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $amenity['is_available'] ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300'; ?>">
                                            <?php echo $amenity['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <button onclick="showEditAmenityModal(<?php echo htmlspecialchars(json_encode($amenity)); ?>)" 
                                                class="text-blue-400 hover:text-blue-300 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="showDeleteAmenityModal(<?php echo $amenity['id']; ?>)" 
                                                class="text-red-400 hover:text-red-300">
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
            
            <!-- Hall Bookings Tab -->
            <div id="bookingsTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-8">
                    <h2 class="text-2xl font-bold text-[#C9A45A] mb-6">Recent Hall Bookings</h2>
                    
                    <?php if (empty($hall_bookings)): ?>
                    <div class="text-center py-12 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                        <i class="fas fa-calendar-alt text-5xl text-[#C9A45A]/50 mb-3"></i>
                        <p class="text-white/70">No hall bookings yet</p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead>
                                <tr class="border-b border-[#C9A45A]/20">
                                    <th class="text-left py-3 text-[#C9A45A]">Booking #</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Guest</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Event</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Date</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Time</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Amount</th>
                                    <th class="text-left py-3 text-[#C9A45A]">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($hall_bookings as $booking): ?>
                                <tr class="border-b border-white/10 hover:bg-white/5">
                                    <td class="py-3 font-mono text-sm"><?php echo $booking['booking_number']; ?></td>
                                    <td class="py-3">
                                        <div><?php echo htmlspecialchars($booking['full_name']); ?></div>
                                        <div class="text-white/50 text-xs"><?php echo $booking['email']; ?></div>
                                    </td>
                                    <td class="py-3"><?php echo htmlspecialchars($booking['event_type']); ?></td>
                                    <td class="py-3"><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                    <td class="py-3 text-sm"><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></td>
                                    <td class="py-3 text-[#C9A45A] font-bold"><?php echo formatCurrency($booking['total_amount']); ?></td>
                                    <td class="py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            <?php 
                                            switch($booking['booking_status']) {
                                                case 'confirmed':
                                                    echo 'bg-green-500/20 text-green-300';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-500/20 text-yellow-300';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-500/20 text-red-300';
                                                    break;
                                                default:
                                                    echo 'bg-gray-500/20 text-gray-300';
                                            }
                                            ?>">
                                            <?php echo ucfirst($booking['booking_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Images Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Upload Hall Images</h3>
                <button onclick="hideUploadModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="hall_id" value="1">
                
                <div class="space-y-4">
                    <div class="upload-area border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-8 text-center cursor-pointer hover:border-[#C9A45A] transition" onclick="document.getElementById('hallImages').click()">
                        <i class="fas fa-cloud-upload-alt text-5xl text-[#C9A45A] mb-3"></i>
                        <p class="text-white/70 mb-1">Click to upload or drag and drop</p>
                        <p class="text-white/50 text-sm">JPG, PNG, GIF, WebP (Max 5MB each)</p>
                        <p class="text-white/50 text-xs mt-2">You can select multiple images</p>
                        <input type="file" id="hallImages" name="hall_images[]" multiple accept="image/*" class="hidden" onchange="previewUploadImages(this)">
                    </div>
                    
                    <!-- Image Preview Container -->
                    <div id="uploadPreviewContainer" class="grid grid-cols-3 gap-2 mt-4 hidden">
                        <p class="text-[#C9A45A] text-sm col-span-3 mb-2">Selected Images:</p>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="upload_hall_images"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Upload Images
                    </button>
                    <button type="button" onclick="hideUploadModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Image Preview Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center">
        <div class="relative max-w-4xl w-full mx-4">
            <button onclick="hideImageModal()" class="absolute top-4 right-4 text-white bg-black/50 rounded-full p-2 hover:bg-black/70 z-10">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <img id="modalImage" src="" alt="Hall Image" class="w-full h-auto rounded-lg">
        </div>
    </div>
    
    <!-- Add Package Modal -->
    <div id="packageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add Event Package</h3>
                <button onclick="hidePackageModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Package Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description *</label>
                        <textarea name="description" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Price (₦) *</label>
                        <input type="number" name="price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Amenities Included *</label>
                        <textarea name="amenities_included" rows="3" required 
                                  placeholder="Enter amenities separated by commas"
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                        <p class="text-white/40 text-xs mt-1">Example: Sound System, Projector, Tables, Chairs</p>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_package"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Add Package
                    </button>
                    <button type="button" onclick="hidePackageModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Package Modal -->
    <div id="editPackageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit Event Package</h3>
                <button onclick="hideEditPackageModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editPackageForm">
                <input type="hidden" name="id" id="edit_package_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Package Name *</label>
                        <input type="text" name="name" id="edit_package_name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description *</label>
                        <textarea name="description" id="edit_package_description" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Price (₦) *</label>
                        <input type="number" name="price" id="edit_package_price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Amenities Included *</label>
                        <textarea name="amenities_included" id="edit_package_amenities" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_package"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Update Package
                    </button>
                    <button type="button" onclick="hideEditPackageModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Amenity Modal -->
    <div id="amenityModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add Hall Amenity</h3>
                <button onclick="hideAmenityModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Amenity Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Price (₦) *</label>
                        <input type="number" name="price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description</label>
                        <textarea name="description" rows="2" 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_amenity"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Add Amenity
                    </button>
                    <button type="button" onclick="hideAmenityModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Amenity Modal -->
    <div id="editAmenityModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit Amenity</h3>
                <button onclick="hideEditAmenityModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editAmenityForm">
                <input type="hidden" name="id" id="edit_amenity_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Amenity Name *</label>
                        <input type="text" name="name" id="edit_amenity_name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Price (₦) *</label>
                        <input type="number" name="price" id="edit_amenity_price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description</label>
                        <textarea name="description" id="edit_amenity_description" rows="2" 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" id="edit_amenity_available" value="1" class="mr-2 accent-[#C9A45A]">
                            <span class="text-white/70">Available for booking</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_amenity"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Update Amenity
                    </button>
                    <button type="button" onclick="hideEditAmenityModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Package Modal -->
    <div id="deletePackageModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Delete Package?</h3>
                <p class="text-white/60 mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_package_id">
                    <button type="submit" name="delete_package"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeletePackageModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Amenity Modal -->
    <div id="deleteAmenityModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Delete Amenity?</h3>
                <p class="text-white/60 mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_amenity_id">
                    <button type="submit" name="delete_amenity"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteAmenityModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
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
    
    // Upload Modal Functions
    function showUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
        document.getElementById('uploadPreviewContainer').innerHTML = '<p class="text-[#C9A45A] text-sm col-span-3 mb-2">Selected Images:</p>';
        document.getElementById('uploadPreviewContainer').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Preview uploaded images
    function previewUploadImages(input) {
        const container = document.getElementById('uploadPreviewContainer');
        container.innerHTML = '<p class="text-[#C9A45A] text-sm col-span-3 mb-2">Selected Images:</p>';
        
        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-20 object-cover rounded-lg border border-[#C9A45A]">
                        <span class="absolute -top-2 -right-2 bg-[#C9A45A] text-[#0F0F0F] w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold">${i+1}</span>
                    `;
                    container.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }
    
    // Image Modal Functions
    function showImageModal(src) {
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideImageModal() {
        document.getElementById('imageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Package Modal Functions
    function showPackageModal() {
        document.getElementById('packageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hidePackageModal() {
        document.getElementById('packageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showEditPackageModal(pkg) {
        document.getElementById('edit_package_id').value = pkg.id;
        document.getElementById('edit_package_name').value = pkg.name;
        document.getElementById('edit_package_description').value = pkg.description;
        document.getElementById('edit_package_price').value = pkg.price;
        document.getElementById('edit_package_amenities').value = pkg.amenities_included;
        
        document.getElementById('editPackageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditPackageModal() {
        document.getElementById('editPackageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showDeletePackageModal(id) {
        document.getElementById('delete_package_id').value = id;
        document.getElementById('deletePackageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeletePackageModal() {
        document.getElementById('deletePackageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Amenity Modal Functions
    function showAmenityModal() {
        document.getElementById('amenityModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideAmenityModal() {
        document.getElementById('amenityModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showEditAmenityModal(amenity) {
        document.getElementById('edit_amenity_id').value = amenity.id;
        document.getElementById('edit_amenity_name').value = amenity.name;
        document.getElementById('edit_amenity_price').value = amenity.price;
        document.getElementById('edit_amenity_description').value = amenity.description;
        document.getElementById('edit_amenity_available').checked = amenity.is_available == 1;
        
        document.getElementById('editAmenityModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditAmenityModal() {
        document.getElementById('editAmenityModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showDeleteAmenityModal(id) {
        document.getElementById('delete_amenity_id').value = id;
        document.getElementById('deleteAmenityModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteAmenityModal() {
        document.getElementById('deleteAmenityModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('uploadModal').addEventListener('click', function(e) {
        if (e.target === this) hideUploadModal();
    });
    
    document.getElementById('imageModal').addEventListener('click', function(e) {
        if (e.target === this) hideImageModal();
    });
    
    document.getElementById('packageModal').addEventListener('click', function(e) {
        if (e.target === this) hidePackageModal();
    });
    
    document.getElementById('editPackageModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditPackageModal();
    });
    
    document.getElementById('deletePackageModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeletePackageModal();
    });
    
    document.getElementById('amenityModal').addEventListener('click', function(e) {
        if (e.target === this) hideAmenityModal();
    });
    
    document.getElementById('editAmenityModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditAmenityModal();
    });
    
    document.getElementById('deleteAmenityModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteAmenityModal();
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