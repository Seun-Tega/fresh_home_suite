<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['video'])) {
    $target_dir = "../assets/videos/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = "hotel-tour.mp4";
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($_FILES["video"]["name"], PATHINFO_EXTENSION));
    $file_size = $_FILES["video"]["size"];
    
    $allowed_types = array("mp4", "webm", "ogg", "mov");
    $max_size = 100 * 1024 * 1024;
    
    if ($file_size > $max_size) {
        $error_message = "File is too large. Maximum size is 100MB.";
    } elseif (!in_array($file_type, $allowed_types)) {
        $error_message = "Only MP4, WebM, OGG, and MOV files are allowed.";
    } else {
        if (move_uploaded_file($_FILES["video"]["tmp_name"], $target_file)) {
            $success_message = "Video uploaded successfully!";
            
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
                $thumb_dir = "../assets/images/video/";
                if (!file_exists($thumb_dir)) {
                    mkdir($thumb_dir, 0777, true);
                }
                $thumb_file = $thumb_dir . "video-thumbnail.jpg";
                move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumb_file);
            }
        } else {
            $error_message = "Error uploading video.";
        }
    }
}

$video_exists = file_exists("../assets/videos/hotel-tour.mp4");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #0F0F0F; }
        .card { background: linear-gradient(145deg, rgba(201,164,90,0.1) 0%, rgba(15,15,15,0.95) 100%); border: 1px solid rgba(201,164,90,0.2); border-radius: 1.5rem; }
        .heading-gold { color: #C9A45A; }
        .btn-gold { background: #C9A45A; color: #0F0F0F; font-weight: bold; padding: 0.75rem 1.5rem; border-radius: 0.75rem; }
        .btn-gold:hover { background: #A8843F; }
        .form-input { background: rgba(15,15,15,0.8); border: 1px solid rgba(201,164,90,0.2); color: #F5F5F5; padding: 0.75rem; border-radius: 0.75rem; width: 100%; }
    </style>
</head>
<body>
    <div class="container mx-auto p-8 max-w-4xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold heading-gold"><i class="fas fa-video mr-3"></i> Upload Video</h1>
            <a href="dashboard.php" class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] px-4 py-2 rounded-lg">← Back</a>
        </div>
        
        <?php if ($success_message): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-500 p-3 rounded-lg mb-4"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="bg-red-500/20 border border-red-500 text-red-500 p-3 rounded-lg mb-4"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card p-8 mb-8">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-[#F5F5F5]/80 mb-2">Select Video (MP4 recommended)</label>
                    <input type="file" name="video" accept="video/*" required class="form-input">
                </div>
                <div class="mb-4">
                    <label class="block text-[#F5F5F5]/80 mb-2">Thumbnail Image (Optional)</label>
                    <input type="file" name="thumbnail" accept="image/*" class="form-input">
                </div>
                <button type="submit" class="btn-gold w-full"><i class="fas fa-upload mr-2"></i> Upload Video</button>
            </form>
        </div>
        
        <div class="card p-8">
            <h2 class="text-xl font-bold heading-gold mb-4">Current Video</h2>
            <?php if ($video_exists): ?>
            <video controls class="w-full rounded-lg">
                <source src="../assets/videos/hotel-tour.mp4" type="video/mp4">
            </video>
            <?php else: ?>
            <p class="text-center text-[#F5F5F5]/60 py-8">No video uploaded yet</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>