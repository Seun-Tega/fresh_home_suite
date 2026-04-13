<?php
require_once 'config/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_image'])) {
    $item_id = $_POST['item_id'];
    
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
        $upload_dir = __DIR__ . '/uploads/food/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file = $_FILES['image_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'food_' . $item_id . '_' . time() . '.' . $ext;
        $target_path = $upload_dir . $filename;
        $db_path = 'uploads/food/' . $filename;
        
        // Check file type
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Delete old image if exists
                $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
                $stmt->execute([$item_id]);
                $old_image = $stmt->fetchColumn();
                
                if ($old_image && file_exists(__DIR__ . '/' . $old_image)) {
                    unlink(__DIR__ . '/' . $old_image);
                }
                
                // Update database
                $update = $pdo->prepare("UPDATE food_items SET image_path = ? WHERE id = ?");
                $update->execute([$db_path, $item_id]);
                
                $success = "Image uploaded successfully for item ID: $item_id";
            } else {
                $error = "Failed to upload file";
            }
        } else {
            $error = "Invalid file type. Allowed: " . implode(', ', $allowed);
        }
    }
}

// Handle path fix
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fix_path'])) {
    $item_id = $_POST['item_id'];
    $new_path = $_POST['new_path'];
    
    $update = $pdo->prepare("UPDATE food_items SET image_path = ? WHERE id = ?");
    $update->execute([$new_path, $item_id]);
    
    $success = "Path updated for item ID: $item_id";
}

// Handle delete image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_image'])) {
    $item_id = $_POST['item_id'];
    
    $stmt = $pdo->prepare("SELECT image_path FROM food_items WHERE id = ?");
    $stmt->execute([$item_id]);
    $image_path = $stmt->fetchColumn();
    
    if ($image_path && file_exists(__DIR__ . '/' . $image_path)) {
        unlink(__DIR__ . '/' . $image_path);
    }
    
    $update = $pdo->prepare("UPDATE food_items SET image_path = NULL WHERE id = ?");
    $update->execute([$item_id]);
    
    $success = "Image deleted for item ID: $item_id";
}

// Get all food items
$items = $pdo->query("
    SELECT fi.*, fc.name as category_name 
    FROM food_items fi 
    LEFT JOIN food_categories fc ON fi.category_id = fc.id 
    ORDER BY fi.id
")->fetchAll();

// Get all categories for dropdown
$categories = $pdo->query("SELECT * FROM food_categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Food Images - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #0F0F0F; color: #F5F5F5; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(201,164,90,0.2); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; border: 2px solid #C9A45A; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #C9A45A; color: #0F0F0F; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid rgba(201,164,90,0.2); }
        code { background: #1a1a1a; padding: 2px 5px; border-radius: 3px; color: #C9A45A; }
        .btn { padding: 8px 16px; border-radius: 5px; border: none; cursor: pointer; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background: #C9A45A; color: #0F0F0F; }
        .btn-primary:hover { background: #A8843F; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .upload-area { border: 2px dashed rgba(201,164,90,0.3); padding: 20px; text-align: center; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .upload-area:hover { border-color: #C9A45A; background: rgba(201,164,90,0.05); }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-3xl font-bold text-[#C9A45A] mb-6">🍽️ Food Image Fix Tool</h1>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <?php
            $total_items = count($items);
            $with_images = 0;
            $valid_images = 0;
            
            foreach ($items as $item) {
                if (!empty($item['image_path'])) {
                    $with_images++;
                    if (file_exists(__DIR__ . '/' . $item['image_path'])) {
                        $valid_images++;
                    }
                }
            }
            ?>
            <div class="card text-center">
                <div class="text-3xl font-bold text-[#C9A45A]"><?php echo $total_items; ?></div>
                <div class="text-white/70">Total Items</div>
            </div>
            <div class="card text-center">
                <div class="text-3xl font-bold text-[#C9A45A]"><?php echo $with_images; ?></div>
                <div class="text-white/70">Have Image Path</div>
            </div>
            <div class="card text-center">
                <div class="text-3xl font-bold text-[#C9A45A]"><?php echo $valid_images; ?></div>
                <div class="text-white/70">Valid Images</div>
            </div>
            <div class="card text-center">
                <div class="text-3xl font-bold text-[#C9A45A]"><?php echo $with_images - $valid_images; ?></div>
                <div class="text-white/70">Broken Images</div>
            </div>
        </div>
        
        <!-- Upload Directory Info -->
        <div class="card">
            <h2 class="text-xl font-bold text-[#C9A45A] mb-4">📁 Upload Directory</h2>
            <?php
            $upload_dir = __DIR__ . '/uploads/food/';
            if (file_exists($upload_dir)) {
                echo "<p class='text-green-400'>✅ Directory exists: $upload_dir</p>";
                
                $files = glob($upload_dir . '*');
                $image_files = array_filter($files, function($f) {
                    return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
                });
                
                echo "<p>Found " . count($image_files) . " image files</p>";
                
                if (count($image_files) > 0) {
                    echo "<div class='grid grid-cols-6 gap-2 mt-4'>";
                    $count = 0;
                    foreach ($image_files as $file) {
                        if ($count++ > 12) break; // Show first 12
                        $filename = basename($file);
                        echo "<div class='text-center'>";
                        echo "<img src='uploads/food/$filename' class='w-16 h-16 object-cover rounded border border-[#C9A45A]' onerror='this.style.display=\"none\"'>";
                        echo "<div class='text-xs text-white/50 truncate'>$filename</div>";
                        echo "</div>";
                    }
                    echo "</div>";
                }
            } else {
                echo "<p class='text-red-400'>❌ Directory does not exist. Creating...</p>";
                mkdir($upload_dir, 0777, true);
                echo "<p class='text-green-400'>✅ Created directory</p>";
            }
            ?>
        </div>
        
        <!-- Food Items Table -->
        <div class="card">
            <h2 class="text-xl font-bold text-[#C9A45A] mb-4">📋 Food Items</h2>
            
            <div class="overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Current Image</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): 
                            $image_path = $item['image_path'] ?? '';
                            $full_path = !empty($image_path) ? __DIR__ . '/' . $image_path : '';
                            $file_exists = !empty($image_path) && file_exists($full_path);
                        ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td class="font-bold"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['category_name'] ?? 'Uncategorized'; ?></td>
                            <td class="text-[#C9A45A]">₦<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <?php if ($file_exists): ?>
                                    <img src="<?php echo $image_path; ?>" class="preview-img" onerror="this.style.display='none'">
                                <?php elseif (!empty($image_path)): ?>
                                    <div class="text-red-400">File missing</div>
                                    <code class="text-xs"><?php echo $image_path; ?></code>
                                <?php else: ?>
                                    <div class="text-white/50">No image</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($file_exists): ?>
                                    <span class="bg-green-500/20 text-green-300 px-2 py-1 rounded-full text-xs">✅ Valid</span>
                                <?php elseif (!empty($image_path)): ?>
                                    <span class="bg-red-500/20 text-red-300 px-2 py-1 rounded-full text-xs">❌ Broken</span>
                                <?php else: ?>
                                    <span class="bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded-full text-xs">⚠️ No Image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <!-- Upload New Image -->
                                    <form method="POST" enctype="multipart/form-data" class="inline">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="file" name="image_file" accept="image/*" class="hidden" id="upload_<?php echo $item['id']; ?>" onchange="this.form.submit()">
                                        <button type="button" onclick="document.getElementById('upload_<?php echo $item['id']; ?>').click()" 
                                                class="btn btn-success text-sm" title="Upload Image">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                        <input type="hidden" name="upload_image" value="1">
                                    </form>
                                    
                                    <!-- Fix Path Form -->
                                    <?php if (!empty($image_path) && !$file_exists): ?>
                                    <form method="POST" class="inline" onsubmit="return prompt('Enter correct path (e.g., uploads/food/filename.jpg):')">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="new_path" id="new_path_<?php echo $item['id']; ?>">
                                        <button type="submit" name="fix_path" onclick="document.getElementById('new_path_<?php echo $item['id']; ?>').value = prompt('Enter correct path (e.g., uploads/food/filename.jpg):', 'uploads/food/')" 
                                                class="btn btn-primary text-sm" title="Fix Path">
                                            <i class="fas fa-wrench"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- Delete Image -->
                                    <?php if (!empty($image_path)): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this image?')">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="delete_image" class="btn btn-danger text-sm" title="Delete Image">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Fix All -->
        <div class="card">
            <h2 class="text-xl font-bold text-[#C9A45A] mb-4">⚡ Quick Fix All</h2>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white/70 mb-2">Set all paths to use 'uploads/food/' prefix</label>
                        <button type="submit" name="fix_all_paths" class="btn btn-primary w-full"
                                onclick="return confirm('This will update all image paths to use uploads/food/ prefix. Continue?')">
                            <i class="fas fa-wrench mr-2"></i> Fix All Paths
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-white/70 mb-2">Clear all invalid image references</label>
                        <button type="submit" name="clear_invalid" class="btn btn-danger w-full"
                                onclick="return confirm('This will remove all invalid image references. Continue?')">
                            <i class="fas fa-trash mr-2"></i> Clear Invalid
                        </button>
                    </div>
                </div>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['fix_all_paths'])) {
                $stmt = $pdo->query("SELECT id, image_path FROM food_items WHERE image_path IS NOT NULL");
                $items_to_fix = $stmt->fetchAll();
                $fixed = 0;
                
                foreach ($items_to_fix as $item) {
                    if (!empty($item['image_path'])) {
                        $new_path = 'uploads/food/' . basename($item['image_path']);
                        $update = $pdo->prepare("UPDATE food_items SET image_path = ? WHERE id = ?");
                        $update->execute([$new_path, $item['id']]);
                        $fixed++;
                    }
                }
                
                echo "<p class='text-green-400 mt-4'>✅ Fixed $fixed image paths</p>";
                echo "<script>setTimeout(function(){ window.location.href = 'fix-food-images-final.php'; }, 2000);</script>";
            }
            
            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_invalid'])) {
                $stmt = $pdo->query("SELECT id, image_path FROM food_items WHERE image_path IS NOT NULL");
                $items_to_check = $stmt->fetchAll();
                $cleared = 0;
                
                foreach ($items_to_check as $item) {
                    $full_path = __DIR__ . '/' . $item['image_path'];
                    if (!file_exists($full_path)) {
                        $update = $pdo->prepare("UPDATE food_items SET image_path = NULL WHERE id = ?");
                        $update->execute([$item['id']]);
                        $cleared++;
                    }
                }
                
                echo "<p class='text-green-400 mt-4'>✅ Cleared $cleared invalid image references</p>";
                echo "<script>setTimeout(function(){ window.location.href = 'fix-food-images-final.php'; }, 2000);</script>";
            }
            ?>
        </div>
        
        <!-- Manual Upload Test -->
        <div class="card">
            <h2 class="text-xl font-bold text-[#C9A45A] mb-4">📤 Manual Upload Test</h2>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-white/70 mb-2">Select Food Item</label>
                    <select name="item_id" required class="w-full px-4 py-2 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-white">
                        <option value="">Choose an item</option>
                        <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo $item['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="upload-area" onclick="document.getElementById('test_upload').click()">
                    <i class="fas fa-cloud-upload-alt text-4xl text-[#C9A45A] mb-2"></i>
                    <p class="text-white/70">Click to select an image</p>
                    <p class="text-white/50 text-sm">JPG, PNG, GIF, WEBP (Max 2MB)</p>
                    <input type="file" id="test_upload" name="image_file" accept="image/*" class="hidden" onchange="this.form.submit()">
                </div>
                
                <input type="hidden" name="upload_image" value="1">
            </form>
        </div>
        
        <!-- Navigation -->
        <div class="flex gap-4 mt-6">
            <a href="admin/menu.php" class="btn btn-primary">Go to Menu Admin</a>
            <a href="eatery.php" class="btn btn-primary">View Restaurant Page</a>
        </div>
    </div>
    
    <script>
    function fixPath(itemId) {
        let newPath = prompt('Enter the correct image path (e.g., uploads/food/filename.jpg):');
        if (newPath) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="item_id" value="${itemId}">
                <input type="hidden" name="new_path" value="${newPath}">
                <input type="hidden" name="fix_path" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>