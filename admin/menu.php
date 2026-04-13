<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check role-based access
$can_edit = in_array($_SESSION['admin_role'], ['super_admin', 'kitchen']);

// Handle image upload for menu items
if (isset($_POST['upload_item_image']) && $can_edit) {
    $item_id = $_POST['item_id'];
    
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
        $upload_dir = '../uploads/food/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['item_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'food_' . $item_id . '_' . time() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        $db_path = 'uploads/food/' . $filename; // Clean path for database
        
        // Check file type
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old image if exists
                $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $old_image = $stmt->fetchColumn();
                
                if ($old_image && file_exists('../' . $old_image)) {
                    unlink('../' . $old_image);
                }
                
                // Update database with clean path
                $update = $pdo->prepare("UPDATE food_items SET image_path = ? WHERE id = ?");
                $update->execute([$db_path, $item_id]);
                
                $_SESSION['success'] = "Image uploaded successfully!";
            } else {
                $_SESSION['error'] = "Failed to upload file";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Allowed: " . implode(', ', $allowed);
        }
        
        header("Location: menu.php");
        exit();
    }
}

// Handle menu operations
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_edit) {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $display_order = $_POST['display_order'];
        
        $stmt = $pdo->prepare("INSERT INTO food_categories (name, description, display_order) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $display_order]);
        
        $_SESSION['success'] = "Category added successfully!";
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['edit_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $display_order = $_POST['display_order'];
        
        $stmt = $pdo->prepare("UPDATE food_categories SET name=?, description=?, display_order=? WHERE id=?");
        $stmt->execute([$name, $description, $display_order, $id]);
        
        $_SESSION['success'] = "Category updated successfully!";
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        
        // Check if category has items
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE category_id = ?");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete category with existing food items!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM food_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Category deleted successfully!";
        }
        
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['add_item'])) {
        $category_id = $_POST['category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $dietary_type = $_POST['dietary_type'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $display_order = $_POST['display_order'];
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/food/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'food_' . time() . '_' . uniqid() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $image_path = 'uploads/food/' . $filename;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO food_items (category_id, name, description, price, image_path, dietary_type, is_available, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$category_id, $name, $description, $price, $image_path, $dietary_type, $is_available, $display_order]);
        
        $_SESSION['success'] = "Menu item added successfully!";
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['edit_item'])) {
        $id = $_POST['id'];
        $category_id = $_POST['category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $dietary_type = $_POST['dietary_type'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        $display_order = $_POST['display_order'];
        
        // Get current image path
        $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
        $stmt->execute([$id]);
        $current_image = $stmt->fetchColumn();
        
        // Handle image upload
        $image_path = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = '../uploads/food/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = 'food_' . $id . '_' . time() . '.' . $ext;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old image
                if ($current_image && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                $image_path = 'uploads/food/' . $filename;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE food_items SET category_id=?, name=?, description=?, price=?, image_path=?, dietary_type=?, is_available=?, display_order=? WHERE id=?");
        $stmt->execute([$category_id, $name, $description, $price, $image_path, $dietary_type, $is_available, $display_order, $id]);
        
        $_SESSION['success'] = "Menu item updated successfully!";
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['delete_item'])) {
        $id = $_POST['id'];
        
        // Get image path to delete file
        $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
        $stmt->execute([$id]);
        $image_path = $stmt->fetchColumn();
        
        // Delete image file if exists
        if ($image_path && file_exists('../' . $image_path)) {
            unlink('../' . $image_path);
        }
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM food_items WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Menu item deleted successfully!";
        header("Location: menu.php");
        exit();
    }
    
    if (isset($_POST['delete_item_image'])) {
        $id = $_POST['item_id'];
        
        // Get image path
        $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
        $stmt->execute([$id]);
        $image_path = $stmt->fetchColumn();
        
        if ($image_path) {
            // Delete file
            if (file_exists('../' . $image_path)) {
                unlink('../' . $image_path);
            }
            
            // Update database
            $stmt = $pdo->prepare("UPDATE food_items SET image_path = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "Image deleted successfully!";
        }
        
        header("Location: menu.php");
        exit();
    }
}

// Get all categories
$stmt = $pdo->query("SELECT * FROM food_categories ORDER BY display_order");
$categories = $stmt->fetchAll();

// Get all food items with category names
$stmt = $pdo->query("
    SELECT fi.*, fc.name as category_name 
    FROM food_items fi 
    JOIN food_categories fc ON fi.category_id = fc.id 
    ORDER BY fc.display_order, fi.display_order
");
$food_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Fresh Home & Suite</title>
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
        .upload-area {
            border: 2px dashed rgba(201, 164, 90, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: #C9A45A;
            background: rgba(201, 164, 90, 0.05);
        }
        .image-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #C9A45A;
        }
        .modal {
            background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(10px);
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
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-building w-5"></i>
                    <span>Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 text-[#C9A45A]">
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
            <?php if(!$can_edit): ?>
            <div class="bg-yellow-500/20 border border-yellow-500/30 text-yellow-200 px-4 py-3 rounded mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                You are in read-only mode. Contact Super Admin to make changes.
            </div>
            <?php endif; ?>
            
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">Restaurant Menu Management</h1>
                <?php if($can_edit): ?>
                <div class="flex gap-4">
                    <button onclick="showCategoryModal()" 
                            class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        <i class="fas fa-folder-plus mr-2"></i> Add Category
                    </button>
                    <button onclick="showItemModal()" 
                            class="bg-[#C9A45A] text-[#0F0F0F] px-6 py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        <i class="fas fa-utensils mr-2"></i> Add Menu Item
                    </button>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded mb-4">
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Categories Section -->
            <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6 mb-8">
                <h2 class="text-2xl font-bold text-[#C9A45A] mb-6">Menu Categories</h2>
                
                <?php if (empty($categories)): ?>
                <div class="text-center py-8 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                    <i class="fas fa-folder-open text-4xl text-[#C9A45A]/50 mb-3"></i>
                    <p class="text-white/70">No categories added yet</p>
                    <?php if($can_edit): ?>
                    <p class="text-white/50 text-sm mt-2">Click "Add Category" to create your first category</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach($categories as $category): ?>
                    <div class="bg-black/30 border border-[#C9A45A]/20 rounded-xl p-4 hover:border-[#C9A45A] transition">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-white font-bold text-lg"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p class="text-white/60 text-sm"><?php echo htmlspecialchars($category['description']); ?></p>
                                <p class="text-[#C9A45A]/60 text-xs mt-2">Display Order: <?php echo $category['display_order']; ?></p>
                            </div>
                            <?php if($can_edit): ?>
                            <div class="flex gap-2">
                                <button onclick="showEditCategoryModal(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                        class="text-blue-400 hover:text-blue-300 p-1" title="Edit Category">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="showDeleteCategoryModal(<?php echo $category['id']; ?>)" 
                                        class="text-red-400 hover:text-red-300 p-1" title="Delete Category">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Menu Items Section -->
            <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                <h2 class="text-2xl font-bold text-[#C9A45A] mb-6">Menu Items</h2>
                
                <?php if (empty($food_items)): ?>
                <div class="text-center py-12 bg-black/20 rounded-lg border border-dashed border-[#C9A45A]/30">
                    <i class="fas fa-utensils text-5xl text-[#C9A45A]/50 mb-3"></i>
                    <p class="text-white/70">No menu items added yet</p>
                    <?php if($can_edit): ?>
                    <p class="text-white/50 text-sm mt-2">Click "Add Menu Item" to add your first dish</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-white">
                        <thead>
                            <tr class="border-b border-[#C9A45A]/20">
                                <th class="text-left py-3 text-[#C9A45A]">Image</th>
                                <th class="text-left py-3 text-[#C9A45A]">Name</th>
                                <th class="text-left py-3 text-[#C9A45A]">Category</th>
                                <th class="text-left py-3 text-[#C9A45A]">Description</th>
                                <th class="text-left py-3 text-[#C9A45A]">Price</th>
                                <th class="text-left py-3 text-[#C9A45A]">Type</th>
                                <th class="text-left py-3 text-[#C9A45A]">Status</th>
                                <th class="text-left py-3 text-[#C9A45A]">Order</th>
                                <?php if($can_edit): ?>
                                <th class="text-left py-3 text-[#C9A45A]">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($food_items as $item): 
                                // Prepare image path for display
                                $display_image = null;
                                if (!empty($item['image_path'])) {
                                    $clean_path = ltrim($item['image_path'], './');
                                    $clean_path = str_replace(['../', './', '\\'], '', $clean_path);
                                    $display_image = '../' . $clean_path;
                                    
                                    // Check if file exists
                                    if (!file_exists(str_replace('../', './', $display_image))) {
                                        $display_image = null;
                                    }
                                }
                            ?>
                            <tr class="border-b border-white/10 hover:bg-white/5">
                                <td class="py-3">
                                    <?php if ($display_image): ?>
                                    <div class="relative group">
                                        <img src="<?php echo $display_image; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="w-16 h-16 object-cover rounded-lg border-2 border-[#C9A45A]"
                                             onerror="this.onerror=null; this.src='../assets/images/no-image.jpg';">
                                        <?php if($can_edit): ?>
                                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1 rounded-lg">
                                            <button onclick="showImageUploadModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')" 
                                                    class="bg-blue-500 text-white p-1 rounded-full hover:bg-blue-600 text-xs" title="Upload Image">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                            <button onclick="deleteItemImage(<?php echo $item['id']; ?>)" 
                                                    class="bg-red-500 text-white p-1 rounded-full hover:bg-red-600 text-xs" title="Delete Image">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="relative group">
                                        <div class="w-16 h-16 bg-black/50 border border-[#C9A45A]/30 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-[#C9A45A]/50 text-2xl"></i>
                                        </div>
                                        <?php if($can_edit): ?>
                                        <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center rounded-lg">
                                            <button onclick="showImageUploadModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>')" 
                                                    class="bg-[#C9A45A] text-[#0F0F0F] p-1 rounded-full hover:bg-[#A8843F] text-xs" title="Upload Image">
                                                <i class="fas fa-upload"></i>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 font-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 bg-[#C9A45A]/10 text-[#C9A45A] rounded-full text-xs">
                                        <?php echo $item['category_name']; ?>
                                    </span>
                                </td>
                                <td class="py-3 max-w-xs">
                                    <p class="text-white/70 text-sm truncate"><?php echo htmlspecialchars($item['description']); ?></p>
                                </td>
                                <td class="py-3 text-[#C9A45A] font-bold"><?php echo formatCurrency($item['price']); ?></td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php 
                                        switch($item['dietary_type']) {
                                            case 'veg':
                                                echo 'bg-green-500/20 text-green-300 border border-green-500/30';
                                                break;
                                            case 'non_veg':
                                                echo 'bg-red-500/20 text-red-300 border border-red-500/30';
                                                break;
                                            case 'spicy':
                                                echo 'bg-orange-500/20 text-orange-300 border border-orange-500/30';
                                                break;
                                            case 'chef_special':
                                                echo 'bg-purple-500/20 text-purple-300 border border-purple-500/30';
                                                break;
                                            default:
                                                echo 'bg-gray-500/20 text-gray-300 border border-gray-500/30';
                                        }
                                        ?>">
                                        <?php 
                                        if($item['dietary_type'] == 'veg') echo '🌱 VEG';
                                        elseif($item['dietary_type'] == 'non_veg') echo '🍗 NON-VEG';
                                        elseif($item['dietary_type'] == 'spicy') echo '🌶️ SPICY';
                                        elseif($item['dietary_type'] == 'chef_special') echo '👨‍🍳 SPECIAL';
                                        else echo 'NEW';
                                        ?>
                                    </span>
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $item['is_available'] ? 'bg-green-500/20 text-green-300 border border-green-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30'; ?>">
                                        <?php echo $item['is_available'] ? 'Available' : 'Out of Stock'; ?>
                                    </span>
                                </td>
                                <td class="py-3 text-white/60"><?php echo $item['display_order']; ?></td>
                                <?php if($can_edit): ?>
                                <td class="py-3">
                                    <div class="flex gap-2">
                                        <button onclick="showEditItemModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                class="text-blue-400 hover:text-blue-300 p-1" title="Edit Item">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="showDeleteItemModal(<?php echo $item['id']; ?>)" 
                                                class="text-red-400 hover:text-red-300 p-1" title="Delete Item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add Category</h3>
                <button onclick="hideCategoryModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Category Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description</label>
                        <textarea name="description" rows="2" 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Display Order</label>
                        <input type="number" name="display_order" value="0" 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                        <p class="text-white/40 text-xs mt-1">Lower numbers appear first</p>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_category"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Add Category
                    </button>
                    <button type="button" onclick="hideCategoryModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit Category</h3>
                <button onclick="hideEditCategoryModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editCategoryForm">
                <input type="hidden" name="id" id="edit_category_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white/70 mb-2">Category Name *</label>
                        <input type="text" name="name" id="edit_category_name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Description</label>
                        <textarea name="description" id="edit_category_description" rows="2" 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="edit_category_order" 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_category"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Update Category
                    </button>
                    <button type="button" onclick="hideEditCategoryModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div id="deleteCategoryModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Delete Category?</h3>
                <p class="text-white/60 mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_category_id">
                    <button type="submit" name="delete_category"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteCategoryModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div id="itemModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add Menu Item</h3>
                <button onclick="hideItemModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Category *</label>
                        <select name="category_id" required class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Item Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
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
                        <label class="block text-white/70 mb-2">Dietary Type *</label>
                        <select name="dietary_type" required class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white">
                            <option value="veg">🌱 Vegetarian</option>
                            <option value="non_veg">🍗 Non-Vegetarian</option>
                            <option value="spicy">🌶️ Spicy</option>
                            <option value="chef_special">👨‍🍳 Chef's Special</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Display Order</label>
                        <input type="number" name="display_order" value="0" 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" value="1" checked class="mr-2 accent-[#C9A45A]">
                            <span class="text-white/70">Available for order</span>
                        </label>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Item Image</label>
                        <div class="upload-area border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-4 text-center" onclick="document.getElementById('itemImage').click()">
                            <i class="fas fa-cloud-upload-alt text-3xl text-[#C9A45A] mb-2"></i>
                            <p class="text-white/70 text-sm">Click to upload image</p>
                            <p class="text-white/50 text-xs">JPG, PNG, GIF, WEBP (Max 2MB)</p>
                            <input type="file" id="itemImage" name="image" accept="image/*" class="hidden" onchange="previewSingleImage(this, 'itemImagePreview')">
                        </div>
                        
                        <!-- Image Preview -->
                        <div id="itemImagePreview" class="mt-2 hidden">
                            <p class="text-[#C9A45A] text-sm mb-1">Preview:</p>
                            <img src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border-2 border-[#C9A45A]">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_item"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Add Menu Item
                    </button>
                    <button type="button" onclick="hideItemModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Item Modal -->
    <div id="editItemModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit Menu Item</h3>
                <button onclick="hideEditItemModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="editItemForm">
                <input type="hidden" name="id" id="edit_item_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Category *</label>
                        <select name="category_id" id="edit_category_id" required class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Item Name *</label>
                        <input type="text" name="name" id="edit_name" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Description *</label>
                        <textarea name="description" id="edit_description" rows="3" required 
                                  class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Price (₦) *</label>
                        <input type="number" name="price" id="edit_price" required 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Dietary Type *</label>
                        <select name="dietary_type" id="edit_dietary_type" required class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white">
                            <option value="veg">🌱 Vegetarian</option>
                            <option value="non_veg">🍗 Non-Vegetarian</option>
                            <option value="spicy">🌶️ Spicy</option>
                            <option value="chef_special">👨‍🍳 Chef's Special</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Display Order</label>
                        <input type="number" name="display_order" id="edit_display_order" 
                               class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white focus:border-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" id="edit_is_available" value="1" class="mr-2 accent-[#C9A45A]">
                            <span class="text-white/70">Available for order</span>
                        </label>
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-white/70 mb-2">Current Image</label>
                        <div id="currentImageContainer" class="mb-4">
                            <!-- Current image will be shown here via JavaScript -->
                        </div>
                        
                        <label class="block text-white/70 mb-2">Upload New Image (optional)</label>
                        <div class="upload-area border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-4 text-center" onclick="document.getElementById('editItemImage').click()">
                            <i class="fas fa-cloud-upload-alt text-3xl text-[#C9A45A] mb-2"></i>
                            <p class="text-white/70 text-sm">Click to upload new image</p>
                            <p class="text-white/50 text-xs">Leave empty to keep current image</p>
                            <input type="file" id="editItemImage" name="image" accept="image/*" class="hidden" onchange="previewSingleImage(this, 'editImagePreview')">
                        </div>
                        
                        <!-- New Image Preview -->
                        <div id="editImagePreview" class="mt-2 hidden">
                            <p class="text-[#C9A45A] text-sm mb-1">New Image Preview:</p>
                            <img src="" alt="Preview" class="w-24 h-24 object-cover rounded-lg border-2 border-[#C9A45A]">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_item"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Update Menu Item
                    </button>
                    <button type="button" onclick="hideEditItemModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Image Upload Modal -->
    <div id="imageUploadModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Upload Image</h3>
                <button onclick="hideImageUploadModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="item_id" id="upload_item_id">
                
                <div class="space-y-4">
                    <p class="text-white/70" id="upload_item_name"></p>
                    
                    <div class="upload-area border-2 border-dashed border-[#C9A45A]/30 rounded-lg p-6 text-center" onclick="document.getElementById('uploadImage').click()">
                        <i class="fas fa-cloud-upload-alt text-4xl text-[#C9A45A] mb-3"></i>
                        <p class="text-white/70">Click to select image</p>
                        <p class="text-white/50 text-sm mt-1">JPG, PNG, GIF, WEBP (Max 2MB)</p>
                        <input type="file" id="uploadImage" name="item_image" accept="image/*" class="hidden" onchange="previewSingleImage(this, 'uploadImagePreview')">
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="uploadImagePreview" class="mt-2 text-center hidden">
                        <img src="" alt="Preview" class="max-w-full h-32 object-cover rounded-lg border-2 border-[#C9A45A] mx-auto">
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="upload_item_image"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-medium">
                        Upload Image
                    </button>
                    <button type="button" onclick="hideImageUploadModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Item Modal -->
    <div id="deleteItemModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold text-white mb-2">Delete Menu Item?</h3>
                <p class="text-white/60 mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_item_id">
                    <button type="submit" name="delete_item"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteItemModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Image preview function
    function previewSingleImage(input, previewId) {
        const preview = document.getElementById(previewId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `
                    <p class="text-[#C9A45A] text-sm mb-1">Preview:</p>
                    <img src="${e.target.result}" class="w-24 h-24 object-cover rounded-lg border-2 border-[#C9A45A]">
                `;
                preview.classList.remove('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Category Modal Functions
    function showCategoryModal() {
        document.getElementById('categoryModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideCategoryModal() {
        document.getElementById('categoryModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showEditCategoryModal(category) {
        document.getElementById('edit_category_id').value = category.id;
        document.getElementById('edit_category_name').value = category.name;
        document.getElementById('edit_category_description').value = category.description;
        document.getElementById('edit_category_order').value = category.display_order;
        
        document.getElementById('editCategoryModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditCategoryModal() {
        document.getElementById('editCategoryModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showDeleteCategoryModal(id) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteCategoryModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteCategoryModal() {
        document.getElementById('deleteCategoryModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Item Modal Functions
    function showItemModal() {
        document.getElementById('itemModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideItemModal() {
        document.getElementById('itemModal').style.display = 'none';
        document.getElementById('itemImagePreview').innerHTML = '';
        document.getElementById('itemImagePreview').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    function showEditItemModal(item) {
        document.getElementById('edit_item_id').value = item.id;
        document.getElementById('edit_category_id').value = item.category_id;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_description').value = item.description;
        document.getElementById('edit_price').value = item.price;
        document.getElementById('edit_dietary_type').value = item.dietary_type;
        document.getElementById('edit_display_order').value = item.display_order;
        document.getElementById('edit_is_available').checked = item.is_available == 1;
        
        // Show current image
        const container = document.getElementById('currentImageContainer');
        if (item.image_path) {
            // Clean the path for display
            let cleanPath = item.image_path.replace(/^[.\/]+/, '');
            cleanPath = '../' + cleanPath;
            container.innerHTML = `
                <img src="${cleanPath}" alt="${item.name}" class="w-24 h-24 object-cover rounded-lg border-2 border-[#C9A45A]"
                     onerror="this.onerror=null; this.src='../assets/images/no-image.jpg';">
                <p class="text-white/50 text-xs mt-1">Current Image</p>
            `;
        } else {
            container.innerHTML = '<p class="text-white/50">No image currently</p>';
        }
        
        document.getElementById('editItemModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditItemModal() {
        document.getElementById('editItemModal').style.display = 'none';
        document.getElementById('editImagePreview').innerHTML = '';
        document.getElementById('editImagePreview').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    function showDeleteItemModal(id) {
        document.getElementById('delete_item_id').value = id;
        document.getElementById('deleteItemModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteItemModal() {
        document.getElementById('deleteItemModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Image Upload Modal Functions
    function showImageUploadModal(itemId, itemName) {
        document.getElementById('upload_item_id').value = itemId;
        document.getElementById('upload_item_name').innerHTML = `Uploading image for: <span class="text-[#C9A45A] font-bold">${itemName}</span>`;
        document.getElementById('imageUploadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideImageUploadModal() {
        document.getElementById('imageUploadModal').style.display = 'none';
        document.getElementById('uploadImagePreview').innerHTML = '';
        document.getElementById('uploadImagePreview').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    function deleteItemImage(itemId) {
        if (confirm('Are you sure you want to delete this image?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="item_id" value="${itemId}">`;
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_item_image';
            input.value = '1';
            form.appendChild(input);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Close modals when clicking outside
    document.getElementById('categoryModal').addEventListener('click', function(e) {
        if (e.target === this) hideCategoryModal();
    });
    
    document.getElementById('editCategoryModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditCategoryModal();
    });
    
    document.getElementById('deleteCategoryModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteCategoryModal();
    });
    
    document.getElementById('itemModal').addEventListener('click', function(e) {
        if (e.target === this) hideItemModal();
    });
    
    document.getElementById('editItemModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditItemModal();
    });
    
    document.getElementById('deleteItemModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteItemModal();
    });
    
    document.getElementById('imageUploadModal').addEventListener('click', function(e) {
        if (e.target === this) hideImageUploadModal();
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