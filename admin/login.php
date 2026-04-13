<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8" data-aos="fade-up">
        <div class="text-center mb-8">
            <img src="../assets/images/logo.png" alt="Logo" class="h-20 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">Admin Login</h1>
            <p class="text-gray-600">Enter your credentials to access dashboard</p>
        </div>
        
        <?php if(isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-6">
                <label class="block text-gray-700 mb-2">Username</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" name="username" required 
                           class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-600">
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <span class="absolute left-3 top-3 text-gray-400">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                           class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-600">
                </div>
            </div>
            
            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg hover:from-purple-700 hover:to-pink-700 transition">
                <i class="fas fa-sign-in-alt mr-2"></i> Login
            </button>
        </form>
        
        <div class="mt-6 text-center text-gray-600">
            <a href="../index.php" class="text-purple-600 hover:text-purple-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Website
            </a>
        </div>
    </div>
</body>
</html>