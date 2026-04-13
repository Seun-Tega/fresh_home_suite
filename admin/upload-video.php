<?php
session_start();
require_once '../config/config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$page_title = 'Upload Video';
$success_message = '';
$error_message = '';

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $target_dir = "../assets/videos/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = "hotel-tour.mp4"; // Fixed name for easy reference
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($_FILES["video"]["name"], PATHINFO_EXTENSION));
    $file_size = $_FILES["video"]["size"];
    
    // Allow certain file formats
    $allowed_types = array("mp4", "webm", "ogg", "mov");
    $max_size = 100 * 1024 * 1024; // 100MB
    
    if ($file_size > $max_size) {
        $error_message = "File is too large. Maximum size is 100MB.";
    } elseif (!in_array($file_type, $allowed_types)) {
        $error_message = "Only MP4, WebM, OGG, and MOV files are allowed.";
    } else {
        if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
            $success_message = "Video uploaded successfully!";
            
            // Also save video info to database
            $video_path = "assets/videos/hotel-tour.mp4";
            $thumbnail = isset($_FILES['thumbnail']) ? handleThumbnailUpload() : null;
            
            // Check if video record exists
            $stmt = $pdo->prepare("SELECT id FROM media WHERE type = 'video' AND is_featured = 1");
            $stmt->execute();
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE media SET file_path = ?, thumbnail = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$video_path, $thumbnail, $existing['id']]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO media (title, file_path, thumbnail, type, is_featured, created_at) VALUES (?, ?, ?, 'video', 1, NOW())");
                $stmt->execute(['Hotel Tour Video', $video_path, $thumbnail]);
            }
        } else {
            $error_message = "Sorry, there was an error uploading your video.";
        }
    }
}

// Handle thumbnail upload
function handleThumbnailUpload() {
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $thumb_dir = "../assets/images/video/";
        if (!file_exists($thumb_dir)) {
            mkdir($thumb_dir, 0777, true);
        }
        
        $thumb_name = "video-thumbnail.jpg";
        $thumb_file = $thumb_dir . $thumb_name;
        
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_file)) {
            return "assets/images/video/video-thumbnail.jpg";
        }
    }
    return null;
}

// Handle video deletion
if (isset($_GET['delete'])) {
    $video_path = "../assets/videos/hotel-tour.mp4";
    if (file_exists($video_path)) {
        unlink($video_path);
        $success_message = "Video deleted successfully";
        
        // Update database
        $stmt = $pdo->prepare("UPDATE media SET file_path = NULL WHERE type = 'video' AND is_featured = 1");
        $stmt->execute();
    }
}

// Check if video exists
$video_exists = file_exists("../assets/videos/hotel-tour.mp4");
$video_info = null;
if ($video_exists) {
    $video_info = [
        'size' => filesize("../assets/videos/hotel-tour.mp4"),
        'modified' => date("F d, Y H:i:s", filemtime("../assets/videos/hotel-tour.mp4"))
    ];
}

// Check if thumbnail exists
$thumbnail_exists = file_exists("../assets/images/video/video-thumbnail.jpg");
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
        .video-preview {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 1rem;
            overflow: hidden;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar -->
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
                <a href="boardrooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
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
                <a href="upload-video.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-video text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Upload Video</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-chart-bar text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-users text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Users</span>
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
            <div class="mb-8">
                <h1 class="text-3xl font-bold heading-gold">Upload Hotel Video</h1>
                <p class="text-[#F5F5F5]/60 mt-2">Upload a promotional video for your hotel website</p>
            </div>
            
            <?php if ($success_message): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-500 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-500 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Upload Form -->
                <div class="form-card rounded-2xl p-6">
                    <h2 class="text-xl font-bold heading-gold mb-4">
                        <i class="fas fa-upload mr-2"></i> Upload New Video
                    </h2>
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label class="block text-[#F5F5F5]/80 mb-2">Select Video File *</label>
                            <input type="file" name="video" accept="video/mp4,video/webm,video/ogg,video/quicktime" required
                                   class="form-input w-full px-4 py-3 rounded-lg">
                            <p class="text-sm text-[#F5F5F5]/60 mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                Accepted formats: MP4, WebM, OGG, MOV. Max size: 100MB
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/80 mb-2">Video Thumbnail (Optional)</label>
                            <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/jpg"
                                   class="form-input w-full px-4 py-3 rounded-lg">
                            <p class="text-sm text-[#F5F5F5]/60 mt-2">
                                <i class="fas fa-image mr-1"></i>
                                Recommended size: 1280x720px (JPG or PNG)
                            </p>
                        </div>
                        
                        <div class="bg-[#C9A45A]/10 rounded-lg p-4">
                            <h3 class="font-bold text-[#C9A45A] mb-2">Video Tips:</h3>
                            <ul class="text-sm text-[#F5F5F5]/80 space-y-1">
                                <li>• <i class="fas fa-check-circle text-[#C9A45A] mr-1"></i> Keep video under 3 minutes for best engagement</li>
                                <li>• <i class="fas fa-check-circle text-[#C9A45A] mr-1"></i> Showcase your best facilities (rooms, hall, boardroom, restaurant)</li>
                                <li>• <i class="fas fa-check-circle text-[#C9A45A] mr-1"></i> Use landscape orientation (16:9 ratio)</li>
                                <li>• <i class="fas fa-check-circle text-[#C9A45A] mr-1"></i> Ensure good lighting and stable camera work</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold py-3 rounded-lg transition-all">
                            <i class="fas fa-cloud-upload-alt mr-2"></i> Upload Video
                        </button>
                    </form>
                </div>
                
                <!-- Current Video Preview -->
                <div class="form-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold heading-gold">
                            <i class="fas fa-video mr-2"></i> Current Video
                        </h2>
                        <?php if ($video_exists): ?>
                        <a href="?delete=1" class="text-red-500 hover:text-red-400 transition" 
                           onclick="return confirm('Are you sure you want to delete this video?')">
                            <i class="fas fa-trash mr-1"></i> Delete
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($video_exists): ?>
                    <div class="video-preview">
                        <video controls class="w-full rounded-lg">
                            <source src="../assets/videos/hotel-tour.mp4" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        
                        <div class="mt-4 space-y-2 text-sm">
                            <p><i class="fas fa-file-video text-[#C9A45A] w-6"></i> <strong>File:</strong> hotel-tour.mp4</p>
                            <p><i class="fas fa-database text-[#C9A45A] w-6"></i> <strong>Size:</strong> <?php echo round($video_info['size'] / (1024 * 1024), 2); ?> MB</p>
                            <p><i class="fas fa-clock text-[#C9A45A] w-6"></i> <strong>Last Modified:</strong> <?php echo $video_info['modified']; ?></p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-video-slash text-6xl text-[#C9A45A]/30 mb-4"></i>
                        <p class="text-[#F5F5F5]/60">No video uploaded yet</p>
                        <p class="text-sm text-[#F5F5F5]/40 mt-2">Use the form on the left to upload a video</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($thumbnail_exists): ?>
                    <div class="mt-6 pt-6 border-t border-[#C9A45A]/20">
                        <h3 class="font-bold text-[#C9A45A] mb-2">Current Thumbnail:</h3>
                        <img src="../assets/images/video/video-thumbnail.jpg" alt="Video Thumbnail" 
                             class="w-full max-h-48 object-cover rounded-lg border border-[#C9A45A]/30">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="mt-8 bg-[#0F0F0F] rounded-2xl p-6 border border-[#C9A45A]/20">
                <h3 class="text-lg font-bold heading-gold mb-3">
                    <i class="fas fa-question-circle mr-2"></i> How to Upload Video
                </h3>
                <ol class="space-y-2 text-[#F5F5F5]/80">
                    <li>1. Click the "Choose File" button above</li>
                    <li>2. Select your video file (MP4 format recommended)</li>
                    <li>3. Optionally upload a thumbnail image for the video</li>
                    <li>4. Click "Upload Video" to save it to your website</li>
                    <li>5. The video will automatically appear on your homepage</li>
                </ol>
                <div class="mt-4 p-3 bg-[#C9A45A]/10 rounded-lg">
                    <i class="fas fa-lightbulb text-[#C9A45A] mr-2"></i>
                    <span class="text-sm">Tip: After uploading, visit your website's homepage to see the video in action!</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    // Display file name when selected
    document.querySelector('input[name="video"]').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            const label = document.querySelector('label[for="video"]');
            if (label) {
                label.innerHTML = `<i class="fas fa-check-circle text-green-500 mr-2"></i> Selected: ${fileName}`;
            }
        }
    });
    </script>
</body>
</html>