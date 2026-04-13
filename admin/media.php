<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['files'])) {
    $category = $_POST['category'] ?? 'general';
    $files = $_FILES['files'];
    
    // Handle multiple file upload
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] == 0) {
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $upload_result = uploadFile($file, $category);
            
            if ($upload_result['success']) {
                $stmt = $pdo->prepare("
                    INSERT INTO media_library (file_name, file_path, file_type, file_size, category, uploaded_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $upload_result['file_name'],
                    $upload_result['file_path'],
                    $files['type'][$i],
                    $files['size'][$i],
                    $category,
                    $_SESSION['admin_id']
                ]);
            }
        }
    }
    
    $_SESSION['success'] = "Files uploaded successfully!";
    header("Location: media.php");
    exit();
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get file path
    $stmt = $pdo->prepare("SELECT file_path FROM media_library WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    
    if ($file) {
        // Delete physical file
        $file_path = '../' . $file['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete database record
        $stmt = $pdo->prepare("DELETE FROM media_library WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "File deleted successfully!";
    }
    
    header("Location: media.php");
    exit();
}

// Get filter category
$filter_category = $_GET['category'] ?? 'all';

// Get media files
$query = "SELECT * FROM media_library";
if ($filter_category != 'all') {
    $query .= " WHERE category = '" . $filter_category . "'";
}
$query .= " ORDER BY uploaded_at DESC";

$stmt = $pdo->query($query);
$media_files = $stmt->fetchAll();

// Get unique categories
$stmt = $pdo->query("SELECT DISTINCT category FROM media_library ORDER BY category");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Library - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(255, 255, 255, 0.1);
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
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-sm opacity-75">Welcome, <?php echo $_SESSION['admin_name']; ?></p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-dashboard"></i>
                    <span>Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings</span>
                </a>
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-receipt"></i>
                    <span>Receipts</span>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-building"></i>
                    <span>Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-utensils"></i>
                    <span>Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20">
                    <i class="fas fa-images"></i>
                    <span>Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <hr class="border-white/20 my-4">
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-white">Media Library</h1>
                <button onclick="showUploadModal()" 
                        class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition">
                    <i class="fas fa-upload mr-2"></i> Upload Files
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
            
            <!-- Category Filter -->
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 mb-8">
                <div class="flex flex-wrap gap-2">
                    <a href="?category=all" 
                       class="px-4 py-2 rounded-lg <?php echo $filter_category == 'all' ? 'bg-yellow-500 text-white' : 'bg-white/20 text-white hover:bg-white/30'; ?> transition">
                        All
                    </a>
                    <?php foreach($categories as $cat): ?>
                    <a href="?category=<?php echo $cat['category']; ?>" 
                       class="px-4 py-2 rounded-lg <?php echo $filter_category == $cat['category'] ? 'bg-yellow-500 text-white' : 'bg-white/20 text-white hover:bg-white/30'; ?> transition">
                        <?php echo ucfirst($cat['category']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Media Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($media_files as $file): ?>
                <div class="bg-white/10 backdrop-blur-lg rounded-xl overflow-hidden group relative" data-aos="zoom-in">
                    <?php if(strpos($file['file_type'], 'image') !== false): ?>
                    <img src="<?php echo '../' . $file['file_path']; ?>" alt="<?php echo $file['file_name']; ?>" 
                         class="w-full h-40 object-cover">
                    <?php else: ?>
                    <div class="w-full h-40 bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <i class="fas fa-file text-4xl text-white"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-3">
                        <p class="text-white text-sm truncate"><?php echo $file['file_name']; ?></p>
                        <p class="text-white/50 text-xs"><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?></p>
                    </div>
                    
                    <div class="absolute inset-0 bg-black/70 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                        <button onclick="copyToClipboard('<?php echo $file['file_path']; ?>')" 
                                class="bg-blue-500 text-white p-2 rounded-full hover:bg-blue-600">
                            <i class="fas fa-copy"></i>
                        </button>
                        <a href="../<?php echo $file['file_path']; ?>" target="_blank" 
                           class="bg-green-500 text-white p-2 rounded-full hover:bg-green-600">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="?delete=<?php echo $file['id']; ?>" 
                           onclick="return confirm('Are you sure you want to delete this file?')"
                           class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-white rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-gray-800">Upload Files</h3>
                <button onclick="hideUploadModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-4 py-2 border rounded-lg">
                            <option value="general">General</option>
                            <option value="rooms">Rooms</option>
                            <option value="hall">Event Hall</option>
                            <option value="food">Food</option>
                            <option value="gallery">Gallery</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 mb-2">Select Files *</label>
                        <input type="file" name="files[]" multiple accept="image/*" required
                               class="w-full px-4 py-2 border rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">You can select multiple files. Max size per file: 5MB</p>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                        Upload Files
                    </button>
                    <button type="button" onclick="hideUploadModal()"
                            class="flex-1 bg-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-400 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function showUploadModal() {
        document.getElementById('uploadModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideUploadModal() {
        document.getElementById('uploadModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function copyToClipboard(path) {
        navigator.clipboard.writeText('../' + path).then(function() {
            alert('Path copied to clipboard!');
        });
    }
    
    // Close modal when clicking outside
    document.getElementById('uploadModal').addEventListener('click', function(e) {
        if (e.target === this) hideUploadModal();
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