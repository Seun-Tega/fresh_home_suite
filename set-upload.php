<?php
// Create necessary directories
$directories = [
    'uploads',
    'uploads/rooms',
    'uploads/halls',
    'uploads/foods',
    'assets/images/rooms',
    'assets/images/halls',
    'assets/images/foods'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        echo "Created directory: $dir<br>";
    }
}

echo "<br>Setup complete! Your upload directories are ready.";
echo "<br><br>Please delete this file after running.";
?>