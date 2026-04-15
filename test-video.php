<?php
$video_file = 'assets/videos/hotel-tour.mp4';
$full_path = __DIR__ . '/' . $video_file;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Test</title>
</head>
<body>
    <h1>Video Test</h1>
    <p>Looking for video at: <?php echo $full_path; ?></p>
    <p>File exists: <?php echo file_exists($full_path) ? 'YES' : 'NO'; ?></p>
    <p>File size: <?php echo file_exists($full_path) ? filesize($full_path) . ' bytes' : 'N/A'; ?></p>
    
    <?php if (file_exists($full_path)): ?>
        <video controls width="800">
            <source src="<?php echo $video_file; ?>" type="video/mp4">
        </video>
        
        <h2>Direct link:</h2>
        <a href="<?php echo $video_file; ?>" target="_blank">Click here to open video directly</a>
    <?php endif; ?>
</body>
</html>