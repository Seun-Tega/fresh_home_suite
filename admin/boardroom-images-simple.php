<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get all board rooms
$stmt = $pdo->query("SELECT id, name FROM boardrooms ORDER BY name");
$boardrooms = $stmt->fetchAll();

$selected = isset($_GET['boardroom_id']) ? (int)$_GET['boardroom_id'] : 0;
$boardroom = null;
$images = [];

if ($selected) {
    $stmt = $pdo->prepare("SELECT * FROM boardrooms WHERE id = ?");
    $stmt->execute([$selected]);
    $boardroom = $stmt->fetch();
    
    if ($boardroom) {
        $stmt = $pdo->prepare("SELECT * FROM boardroom_images WHERE boardroom_id = ? ORDER BY is_primary DESC");
        $stmt->execute([$selected]);
        $images = $stmt->fetchAll();
    }
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images']) && $selected) {
    $upload_dir = '../uploads/boardrooms/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
        if ($_FILES['images']['error'][$key] == 0) {
            $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
            $name = time() . '_' . $selected . '_' . $key . '.' . $ext;
            $path = $upload_dir . $name;
            
            if (move_uploaded_file($tmp, $path)) {
                $count = $pdo->prepare("SELECT COUNT(*) FROM boardroom_images WHERE boardroom_id = ?");
                $count->execute([$selected]);
                $is_primary = $count->fetchColumn() == 0 ? 1 : 0;
                
                $stmt = $pdo->prepare("INSERT INTO boardroom_images (boardroom_id, image_path, is_primary) VALUES (?, ?, ?)");
                $stmt->execute([$selected, 'uploads/boardrooms/' . $name, $is_primary]);
            }
        }
    }
    header("Location: boardroom-images-simple.php?boardroom_id=$selected");
    exit();
}

// Handle delete
if (isset($_GET['delete']) && $selected) {
    $stmt = $pdo->prepare("SELECT image_path FROM boardroom_images WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $img = $stmt->fetch();
    if ($img && file_exists('../' . $img['image_path'])) unlink('../' . $img['image_path']);
    $pdo->prepare("DELETE FROM boardroom_images WHERE id = ?")->execute([$_GET['delete']]);
    header("Location: boardroom-images-simple.php?boardroom_id=$selected");
    exit();
}

// Handle set primary
if (isset($_GET['primary']) && $selected) {
    $pdo->prepare("UPDATE boardroom_images SET is_primary = 0 WHERE boardroom_id = ?")->execute([$selected]);
    $pdo->prepare("UPDATE boardroom_images SET is_primary = 1 WHERE id = ?")->execute([$_GET['primary']]);
    header("Location: boardroom-images-simple.php?boardroom_id=$selected");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Board Room Images</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #0F0F0F; }
        .card { background: linear-gradient(145deg, rgba(201,164,90,0.1) 0%, rgba(15,15,15,0.95) 100%); border: 1px solid rgba(201,164,90,0.2); border-radius: 1.5rem; }
        .heading-gold { color: #C9A45A; }
    </style>
</head>
<body>
    <div class="container mx-auto p-8 max-w-6xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold heading-gold"><i class="fas fa-images mr-3"></i> Board Room Images</h1>
            <a href="dashboard.php" class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] px-4 py-2 rounded-lg">← Back</a>
        </div>
        
        <div class="card p-8 mb-8">
            <label class="block text-[#F5F5F5]/80 mb-2">Select Board Room</label>
            <select onchange="location.href='?boardroom_id='+this.value" class="w-full bg-[#0F0F0F] border border-[#C9A45A]/30 rounded-lg p-3">
                <option value="">-- Choose Board Room --</option>
                <?php foreach($boardrooms as $br): ?>
                <option value="<?php echo $br['id']; ?>" <?php echo $selected == $br['id'] ? 'selected' : ''; ?>><?php echo $br['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($boardroom): ?>
        <div class="card p-8 mb-8">
            <h2 class="text-xl font-bold heading-gold mb-4">Upload Images for <?php echo $boardroom['name']; ?></h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="file" name="images[]" multiple accept="image/*" required class="w-full bg-[#0F0F0F] border border-[#C9A45A]/30 rounded-lg p-3 mb-4">
                <button type="submit" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-6 py-2 rounded-lg"><i class="fas fa-upload mr-2"></i> Upload</button>
            </form>
        </div>
        
        <div class="card p-8">
            <h2 class="text-xl font-bold heading-gold mb-4">Image Gallery (<?php echo count($images); ?> images)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach($images as $img): ?>
                <div class="relative bg-black rounded-lg overflow-hidden">
                    <img src="../<?php echo $img['image_path']; ?>" class="w-full h-40 object-cover">
                    <?php if($img['is_primary']): ?>
                    <span class="absolute top-2 left-2 bg-[#C9A45A] text-black text-xs px-2 py-1 rounded">Primary</span>
                    <?php endif; ?>
                    <div class="absolute bottom-2 right-2 flex gap-1">
                        <?php if(!$img['is_primary']): ?>
                        <a href="?boardroom_id=<?php echo $selected; ?>&primary=<?php echo $img['id']; ?>" class="bg-black/70 p-1.5 rounded hover:bg-[#C9A45A]" title="Set Primary"><i class="fas fa-star text-xs"></i></a>
                        <?php endif; ?>
                        <a href="?boardroom_id=<?php echo $selected; ?>&delete=<?php echo $img['id']; ?>" class="bg-black/70 p-1.5 rounded hover:bg-red-500" onclick="return confirm('Delete?')" title="Delete"><i class="fas fa-trash text-xs"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>