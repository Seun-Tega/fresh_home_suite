<?php
require_once 'config/config.php';

echo "<h1>🔧 Fixing Admin Password</h1>";

try {
    // Generate correct hash for 'admin123'
    $correct_password = 'admin123';
    $correct_hash = password_hash($correct_password, PASSWORD_DEFAULT);
    
    echo "New password hash created: " . $correct_hash . "<br>";
    echo "Testing new hash: ";
    
    if (password_verify('admin123', $correct_hash)) {
        echo "<span style='color:green'>✅ New hash works!</span><br>";
    } else {
        echo "<span style='color:red'>❌ New hash failed test!</span><br>";
    }
    
    // Update the database with correct hash
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'superadmin'");
    $stmt->execute([$correct_hash]);
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Password updated successfully for superadmin!</p>";
    } else {
        echo "<p style='color:orange'>⚠️ No user found with username 'superadmin' or password already correct?</p>";
        
        // Let's check if user exists
        $check = $pdo->prepare("SELECT * FROM users WHERE username = 'superadmin'");
        $check->execute();
        $user = $check->fetch();
        
        if ($user) {
            echo "<p>User found: " . $user['username'] . "</p>";
            echo "<p>Current hash in DB: " . $user['password'] . "</p>";
            
            // Force update regardless
            $force = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'superadmin'");
            $force->execute([$correct_hash]);
            echo "<p style='color:green'>✅ Password forcefully updated!</p>";
        } else {
            echo "<p style='color:red'>❌ User 'superadmin' not found! Creating new user...</p>";
            
            // Create the user
            $insert = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $insert->execute(['superadmin', 'admin@freshhomehotel.com', $correct_hash, 'Super Admin', 'super_admin']);
            echo "<p style='color:green'>✅ New superadmin user created!</p>";
        }
    }
    
    // Verify the update worked
    $verify = $pdo->prepare("SELECT password FROM users WHERE username = 'superadmin'");
    $verify->execute();
    $new_hash = $verify->fetchColumn();
    
    echo "<h3>Verification:</h3>";
    if (password_verify('admin123', $new_hash)) {
        echo "<p style='color:green; font-weight:bold'>✅ FIXED! Password 'admin123' now works!</p>";
    } else {
        echo "<p style='color:red; font-weight:bold'>❌ Still not working! Something is wrong.</p>";
    }
    
    echo "<h3>Login Now:</h3>";
    echo "<p><a href='admin/login.php' style='background: #C9A45A; color: #0F0F0F; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
?>