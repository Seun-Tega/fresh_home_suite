<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$boardroom_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$boardroom_id) {
    header("Location: boardrooms.php");
    exit();
}

// Get board room info
$stmt = $pdo->prepare("SELECT * FROM boardrooms WHERE id = ?");
$stmt->execute([$boardroom_id]);
$boardroom = $stmt->fetch();

if (!$boardroom) {
    header("Location: boardrooms.php");
    exit();
}

$page_title = 'Manage Images - ' . $boardroom['name'];
$success_message = '';
$error_message = '';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
    $upload_dir = '../uploads/boardrooms/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded = 0;
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] == 0) {
            $file_name = time() . '_' . $boardroom_id . '_' . $_FILES['images']['name'][$key];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($tmp_name, $file_path)) {
                $db_path = 'uploads/boardrooms/' . $file_name;
                
                // Check if this is the first image (set as primary)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM boardroom_images WHERE boardroom_id = ?");
                $stmt->execute([$boardroom_id]);
                $image_count = $stmt->fetchColumn();
                
                $is_primary = ($image_count == 0) ? 1 : 0;
                
                $img_stmt = $pdo->prepare("INSERT INTO boardroom_images (boardroom_id, image_path, is_primary) VALUES (?, ?, ?)");
                $img_stmt->execute([$boardroom_id, $db_path, $is_primary]);
                $uploaded++;
            }
        }
    }
    
    if ($uploaded > 0) {
        $success_message = "$uploaded image(s) uploaded successfully!";
    } else {
        $error_message = "No images were uploaded.";
    }
}

// Handle set primary
if (isset($_GET['set_primary'])) {
    $image_id = (int)$_GET['set_primary'];
    
    // Reset all primary flags
    $stmt = $pdo->prepare("UPDATE boardroom_images SET is_primary = 0 WHERE boardroom_id = ?");
    $stmt->execute([$boardroom_id]);
    
    // Set new primary
    $stmt = $pdo->prepare("UPDATE boardroom_images SET is_primary = 1 WHERE id = ?");
    $stmt->execute([$image_id]);
    
    $success_message = "Primary image updated!";
}

// Handle delete image
if (isset($_GET['delete_image'])) {
    $image_id = (int)$_GET['delete_image'];
    
    // Get image path
    $stmt = $pdo->prepare("SELECT image_path FROM boardroom_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image = $stmt->fetch();
    
    if ($image) {
        $file_path = '../' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        $stmt = $pdo->prepare("DELETE FROM boardroom_images WHERE id = ?");
        $stmt->execute([$image_id]);
        
        // If deleted image was primary, set another as primary
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM boardroom_images WHERE boardroom_id = ?");
        $stmt->execute([$boardroom_id]);
        $remaining = $stmt->fetchColumn();
        
        if ($remaining > 0) {
            $stmt = $pdo->prepare("UPDATE boardroom_images SET is_primary = 1 WHERE boardroom_id = ? LIMIT 1");
            $stmt->execute([$boardroom_id]);
        }
        
        $success_message = "Image deleted successfully!";
    }
}

// Get all images for this board room
$stmt = $pdo->prepare("SELECT * FROM boardroom_images WHERE boardroom_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$boardroom_id]);
$images = $stmt->fetchAll();
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
        .image-card {
            transition: transform 0.3s ease;
        }
        .image-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar (same as before) -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4" onerror="this.style.display='none'">
                <h2 class="text-xl font-bold heading-gold">Admin Panel</h2>
                <p class="text-sm text-[#F5F5F5]/60">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-dashboard text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Dashboard</span>
                </a>
                <a href="boardrooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20">
                    <i class="fas fa-door-open text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Board Rooms</span>
                </a>
                <!-- Add other links as needed -->
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold heading-gold">Manage Images</h1>
                    <p class="text-[#F5F5F5]/60 mt-1">Board Room: <?php echo htmlspecialchars($boardroom['name']); ?></p>
                </div>
                <a href="boardrooms.php" class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] px-4 py-2 rounded-lg transition-all">
                    <i class="fas fa-arrow-left mr-2"></i> Back
                </a>
            </div>
            
            <?php if ($success_message): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-500 px-4 py-3 rounded-lg mb-4">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Upload Form -->
            <div class="form-card rounded-2xl p-6 mb-8">
                <h2 class="text-xl font-bold heading-gold mb-4">
                    <i class="fas fa-upload mr-2"></i> Upload New Images
                </h2>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-[#F5F5F5]/80 mb-2">Select Images (Multiple allowed)</label>
                        <input type="file" name="images[]" multiple accept="image/*" required
                               class="form-input w-full px-4 py-3 rounded-lg bg-[#0F0F0F] border border-[#C9A45A]/30">
                        <p class="text-sm text-[#F5F5F5]/60 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            First image uploaded will be set as primary. JPG, PNG, GIF allowed.
                        </p>
                    </div>
                    <button type="submit" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-6 py-2 rounded-lg transition-all">
                        <i class="fas fa-cloud-upload-alt mr-2"></i> Upload Images
                    </button>
                </form>
            </div>
            
            <!-- Image Gallery -->
            <div class="form-card rounded-2xl p-6">
                <h2 class="text-xl font-bold heading-gold mb-4">
                    <i class="fas fa-images mr-2"></i> Image Gallery
                    <span class="text-sm text-[#F5F5F5]/60 ml-2">(<?php echo count($images); ?> images)</span>
                </h2>
                
                <?php if (empty($images)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-camera text-6xl text-[#C9A45A]/30 mb-4"></i>
                    <p class="text-[#F5F5F5]/60">No images uploaded yet</p>
                    <p class="text-sm text-[#F5F5F5]/40 mt-2">Use the form above to add images</p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php foreach($images as $image): ?>
                    <div class="image-card bg-[#0F0F0F] rounded-xl overflow-hidden border border-[#C9A45A]/20">
                        <div class="relative h-40">
                            <img src="../<?php echo $image['image_path']; ?>" 
                                 alt="Board Room Image"
                                 class="w-full h-full object-cover">
                            <?php if ($image['is_primary']): ?>
                            <span class="absolute top-2 left-2 bg-[#C9A45A] text-[#0F0F0F] text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-star mr-1"></i> Primary
                            </span>
                            <?php endif; ?>
                            <div class="absolute bottom-2 right-2 flex space-x-1">
                                <?php if (!$image['is_primary']): ?>
                                <a href="?id=<?php echo $boardroom_id; ?>&set_primary=<?php echo $image['id']; ?>" 
                                   class="bg-black/70 hover:bg-[#C9A45A] text-[#F5F5F5] hover:text-[#0F0F0F] p-1.5 rounded transition"
                                   title="Set as Primary">
                                    <i class="fas fa-star text-xs"></i>
                                </a>
                                <?php endif; ?>
                                <a href="?id=<?php echo $boardroom_id; ?>&delete_image=<?php echo $image['id']; ?>" 
                                   class="bg-black/70 hover:bg-red-500 text-[#F5F5F5] p-1.5 rounded transition"
                                   title="Delete"
                                   onclick="return confirm('Delete this image?')">
                                    <i class="fas fa-trash text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>