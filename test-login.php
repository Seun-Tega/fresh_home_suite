<?php
require_once 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    echo "<h2>Login Test Results:</h2>";
    
    if ($user) {
        echo "✅ User found: " . $user['username'] . "<br>";
        echo "Stored hash: " . $user['password'] . "<br>";
        
        if (password_verify($password, $user['password'])) {
            echo "<span style='color:green; font-weight:bold'>✅ Password CORRECT!</span><br>";
            echo "Would redirect to dashboard...";
        } else {
            echo "<span style='color:red; font-weight:bold'>❌ Password INCORRECT!</span><br>";
            
            // Test what the hash should be
            $correct_hash = password_hash('admin123', PASSWORD_DEFAULT);
            echo "Correct hash for 'admin123' should be: " . $correct_hash . "<br>";
        }
    } else {
        echo "❌ User not found<br>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Login</title>
    <style>
        body { background: #0F0F0F; color: #F5F5F5; font-family: Arial; padding: 20px; }
        input, button { padding: 10px; margin: 5px; }
        button { background: #C9A45A; color: #0F0F0F; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Test Login Form</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" value="superadmin"><br>
        <input type="password" name="password" placeholder="Password" value="admin123"><br>
        <button type="submit">Test Login</button>
    </form>
</body>
</html>