<?php
require_once 'config/config.php';

echo "<h1>🔄 Complete Admin Reset</h1>";

try {
    // Drop and recreate users table
    $pdo->exec("DROP TABLE IF EXISTS users");
    echo "✅ Dropped old users table<br>";
    
    // Create fresh users table
    $sql = "CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE,
        email VARCHAR(100) UNIQUE,
        password VARCHAR(255),
        full_name VARCHAR(100),
        role ENUM('super_admin', 'front_desk', 'kitchen', 'hall_manager') DEFAULT 'front_desk',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✅ Created new users table<br>";
    
    // Insert admin user
    $username = 'superadmin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $email = 'admin@freshhomehotel.com';
    $full_name = 'Super Admin';
    $role = 'super_admin';
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $full_name, $role]);
    
    echo "✅ Admin user created successfully!<br>";
    echo "<h2>Login Credentials:</h2>";
    echo "Username: <strong>superadmin</strong><br>";
    echo "Password: <strong>admin123</strong><br>";
    
    // Verify the password
    echo "<h3>Verification Test:</h3>";
    $check = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $check->execute(['superadmin']);
    $user = $check->fetch();
    
    if ($user && password_verify('admin123', $user['password'])) {
        echo "<span style='color:green'>✅ Password verification working!</span><br>";
    } else {
        echo "<span style='color:red'>❌ Password verification failed!</span><br>";
    }
    
    echo "<p><a href='admin/login.php' style='color: #C9A45A;'>Go to Admin Login</a></p>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>