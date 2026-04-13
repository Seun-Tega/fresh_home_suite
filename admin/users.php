<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Only Super Admin can manage users
if ($_SESSION['admin_role'] != 'super_admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: dashboard.php");
    exit();
}

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        $role = $_POST['role'];
        
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already exists!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $full_name, $role]);
            
            $_SESSION['success'] = "User added successfully!";
        }
        
        header("Location: users.php");
        exit();
    }
    
    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $full_name = $_POST['full_name'];
        $role = $_POST['role'];
        
        // Check if username/email exists for other users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already exists!";
        } else {
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $password, $full_name, $role, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
                $stmt->execute([$username, $email, $full_name, $role, $id]);
            }
            
            $_SESSION['success'] = "User updated successfully!";
        }
        
        header("Location: users.php");
        exit();
    }
    
    if (isset($_POST['delete_user'])) {
        $id = $_POST['id'];
        
        // Don't allow deleting own account
        if ($id == $_SESSION['admin_id']) {
            $_SESSION['error'] = "You cannot delete your own account!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = "User deleted successfully!";
        }
        
        header("Location: users.php");
        exit();
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY role, full_name");
$users = $stmt->fetchAll();

// Get guests with booking counts
$stmt = $pdo->query("
    SELECT g.*, 
           (SELECT COUNT(*) FROM room_bookings WHERE guest_id = g.id) as room_bookings,
           (SELECT COUNT(*) FROM hall_bookings WHERE guest_id = g.id) as hall_bookings
    FROM guests g 
    ORDER BY g.created_at DESC 
    LIMIT 50
");
$guests = $stmt->fetchAll();

// Get statistics
$stats = [
    'total_admins' => count($users),
    'total_guests' => $pdo->query("SELECT COUNT(*) FROM guests")->fetchColumn(),
    'super_admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(201, 164, 90, 0.2);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: #C9A45A;
            transform: translateY(-2px);
        }
        .tab-btn {
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: rgba(201, 164, 90, 0.2);
            color: #C9A45A;
            border: 1px solid #C9A45A;
        }
        .table-header {
            background: rgba(201, 164, 90, 0.1);
            color: #C9A45A;
        }
        .table-row {
            transition: all 0.3s ease;
        }
        .table-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        .modal-content {
            background: #0F0F0F;
            border: 1px solid rgba(201, 164, 90, 0.3);
        }
        .form-input {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
        }
        .form-input:focus {
            border-color: #C9A45A;
            outline: none;
            ring: 2px solid rgba(201, 164, 90, 0.3);
        }
        .form-label {
            color: #F5F5F5;
            opacity: 0.7;
        }
        .btn-primary {
            background: #C9A45A;
            color: #0F0F0F;
        }
        .btn-primary:hover {
            background: #A8843F;
        }
    </style>
</head>
<body>
    <div class="flex h-screen">
        <!-- Sidebar with branding -->
        <div class="sidebar w-64 text-white p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
                <h2 class="text-xl font-bold text-[#C9A45A]">Admin Panel</h2>
                <p class="text-sm text-white/60">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-dashboard w-5"></i>
                    <span>Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-calendar-check w-5"></i>
                    <span>Bookings</span>
                </a>
                <a href="receipts.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-receipt w-5"></i>
                    <span>Receipts</span>
                </a>
                <a href="rooms.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-bed w-5"></i>
                    <span>Rooms</span>
                </a>
                <a href="hall.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-building w-5"></i>
                    <span>Event Hall</span>
                </a>
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-utensils w-5"></i>
                    <span>Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-images w-5"></i>
                    <span>Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-chart-bar w-5"></i>
                    <span>Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 text-[#C9A45A]">
                    <i class="fas fa-users w-5"></i>
                    <span>Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] transition">
                    <i class="fas fa-cog w-5"></i>
                    <span>Settings</span>
                </a>
                <hr class="border-white/10 my-4">
                <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-red-500/10 hover:text-red-500 transition">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span>Logout</span>
                </a>
            </nav>
            
            <!-- User Role Badge -->
            <div class="mt-6 pt-6 border-t border-[#C9A45A]/20">
                <div class="bg-[#C9A45A]/10 rounded-lg p-3">
                    <p class="text-xs text-[#C9A45A]/60">Logged in as</p>
                    <p class="font-semibold text-[#C9A45A]"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-white">User Management</h1>
                    <p class="text-white/60 mt-1">Manage administrators and view registered guests</p>
                </div>
                <button onclick="showAddUserModal()" 
                        class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] px-6 py-3 rounded-lg transition font-medium flex items-center">
                    <i class="fas fa-user-plus mr-2"></i> Add New Admin
                </button>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Total Admins</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['total_admins']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-user-shield text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Registered Guests</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['total_guests']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-users text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card rounded-2xl p-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white/60 text-sm">Super Admins</p>
                            <p class="text-3xl font-bold text-white mt-1"><?php echo $stats['super_admins']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center">
                            <i class="fas fa-crown text-2xl text-[#C9A45A]"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-lg mb-6 flex items-center" data-aos="fade-in">
                <i class="fas fa-check-circle mr-2 text-green-400"></i>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-lg mb-6 flex items-center" data-aos="fade-in">
                <i class="fas fa-exclamation-circle mr-2 text-red-400"></i>
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6 border-b border-[#C9A45A]/20">
                <div class="flex space-x-4">
                    <button onclick="showTab('admins')" class="tab-btn active px-6 py-3 rounded-t-lg font-medium">
                        <i class="fas fa-user-shield mr-2"></i> Admin Users
                    </button>
                    <button onclick="showTab('guests')" class="tab-btn px-6 py-3 rounded-t-lg font-medium text-white/70 hover:text-[#C9A45A]">
                        <i class="fas fa-user-friends mr-2"></i> Registered Guests
                    </button>
                </div>
            </div>
            
            <!-- Admin Users Tab -->
            <div id="adminsTab" class="tab-content">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead class="table-header">
                                <tr>
                                    <th class="text-left py-3 px-4 rounded-l-lg">Username</th>
                                    <th class="text-left py-3 px-4">Full Name</th>
                                    <th class="text-left py-3 px-4">Email</th>
                                    <th class="text-left py-3 px-4">Role</th>
                                    <th class="text-left py-3 px-4">Created</th>
                                    <th class="text-left py-3 px-4 rounded-r-lg">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr class="table-row border-b border-white/10">
                                    <td class="py-4 px-4">
                                        <span class="font-mono text-sm text-[#C9A45A]">@<?php echo htmlspecialchars($user['username']); ?></span>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    </td>
                                    <td class="py-4 px-4 text-white/70"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-4 px-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            switch($user['role']) {
                                                case 'super_admin':
                                                    echo 'bg-purple-500/20 text-purple-300 border border-purple-500/30';
                                                    break;
                                                case 'front_desk':
                                                    echo 'bg-blue-500/20 text-blue-300 border border-blue-500/30';
                                                    break;
                                                case 'kitchen':
                                                    echo 'bg-green-500/20 text-green-300 border border-green-500/30';
                                                    break;
                                                case 'hall_manager':
                                                    echo 'bg-orange-500/20 text-orange-300 border border-orange-500/30';
                                                    break;
                                            }
                                            ?>">
                                            <?php 
                                            switch($user['role']) {
                                                case 'super_admin':
                                                    echo '<i class="fas fa-crown mr-1"></i> Super Admin';
                                                    break;
                                                case 'front_desk':
                                                    echo '<i class="fas fa-headset mr-1"></i> Front Desk';
                                                    break;
                                                case 'kitchen':
                                                    echo '<i class="fas fa-utensils mr-1"></i> Kitchen';
                                                    break;
                                                case 'hall_manager':
                                                    echo '<i class="fas fa-building mr-1"></i> Hall Manager';
                                                    break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-white/60 text-sm">
                                        <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <?php if($user['id'] != $_SESSION['admin_id']): ?>
                                        <div class="flex space-x-3">
                                            <button onclick="showEditUserModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-blue-400 hover:text-blue-300 transition" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="showDeleteUserModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')" 
                                                    class="text-red-400 hover:text-red-300 transition" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-[#C9A45A] text-sm flex items-center">
                                            <i class="fas fa-user-check mr-1"></i> Current User
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Guests Tab -->
            <div id="guestsTab" class="tab-content hidden">
                <div class="bg-white/5 backdrop-blur-lg border border-[#C9A45A]/20 rounded-2xl p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-white">
                            <thead class="table-header">
                                <tr>
                                    <th class="text-left py-3 px-4 rounded-l-lg">Full Name</th>
                                    <th class="text-left py-3 px-4">Contact</th>
                                    <th class="text-left py-3 px-4">Address</th>
                                    <th class="text-left py-3 px-4">Registered</th>
                                    <th class="text-left py-3 px-4">Bookings</th>
                                    <th class="text-left py-3 px-4 rounded-r-lg">Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($guests as $guest): 
                                    $total_bookings = $guest['room_bookings'] + $guest['hall_bookings'];
                                ?>
                                <tr class="table-row border-b border-white/10">
                                    <td class="py-4 px-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($guest['full_name']); ?></div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="text-white/80 text-sm"><?php echo htmlspecialchars($guest['email']); ?></div>
                                        <div class="text-white/60 text-xs"><?php echo htmlspecialchars($guest['phone']); ?></div>
                                    </td>
                                    <td class="py-4 px-4 max-w-xs">
                                        <div class="truncate text-white/70 text-sm">
                                            <?php echo htmlspecialchars($guest['address'] ?? 'N/A'); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-white/60 text-sm">
                                        <?php echo date('M d, Y', strtotime($guest['created_at'])); ?>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex space-x-2">
                                            <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded-full text-xs border border-blue-500/30">
                                                <i class="fas fa-bed mr-1"></i> <?php echo $guest['room_bookings']; ?>
                                            </span>
                                            <span class="px-2 py-1 bg-orange-500/20 text-orange-300 rounded-full text-xs border border-orange-500/30">
                                                <i class="fas fa-building mr-1"></i> <?php echo $guest['hall_bookings']; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-white/60 text-sm">
                                        <?php echo $guest['updated_at'] ? date('M d, Y', strtotime($guest['updated_at'])) : 'Never'; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if(empty($guests)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-user-friends text-5xl text-white/20 mb-3"></i>
                            <p class="text-white/60">No guests registered yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-lg w-full mx-4 p-6" data-aos="zoom-in">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Add New Admin User</h3>
                <button onclick="hideAddUserModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block form-label mb-2 text-sm">Username *</label>
                        <input type="text" name="username" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Full Name *</label>
                        <input type="text" name="full_name" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Email *</label>
                        <input type="email" name="email" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Password *</label>
                        <input type="password" name="password" required minlength="6"
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                        <p class="text-white/40 text-xs mt-1">Minimum 6 characters</p>
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Role *</label>
                        <select name="role" required class="w-full form-input px-4 py-3 rounded-lg">
                            <option value="front_desk">Front Desk Manager</option>
                            <option value="kitchen">Kitchen Manager</option>
                            <option value="hall_manager">Hall Manager</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_user"
                            class="flex-1 btn-primary py-3 rounded-lg transition font-medium">
                        <i class="fas fa-user-plus mr-2"></i> Add User
                    </button>
                    <button type="button" onclick="hideAddUserModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-lg w-full mx-4 p-6" data-aos="zoom-in">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#C9A45A]">Edit User</h3>
                <button onclick="hideEditUserModal()" class="text-white/60 hover:text-white">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editUserForm">
                <input type="hidden" name="id" id="edit_user_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block form-label mb-2 text-sm">Username *</label>
                        <input type="text" name="username" id="edit_username" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Full Name *</label>
                        <input type="text" name="full_name" id="edit_full_name" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Email *</label>
                        <input type="email" name="email" id="edit_email" required 
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">New Password</label>
                        <input type="password" name="password" minlength="6"
                               class="w-full form-input px-4 py-3 rounded-lg focus:ring-2 focus:ring-[#C9A45A]">
                        <p class="text-white/40 text-xs mt-1">Leave blank to keep current password</p>
                    </div>
                    
                    <div>
                        <label class="block form-label mb-2 text-sm">Role *</label>
                        <select name="role" id="edit_role" required class="w-full form-input px-4 py-3 rounded-lg">
                            <option value="front_desk">Front Desk Manager</option>
                            <option value="kitchen">Kitchen Manager</option>
                            <option value="hall_manager">Hall Manager</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_user"
                            class="flex-1 btn-primary py-3 rounded-lg transition font-medium">
                        <i class="fas fa-save mr-2"></i> Update User
                    </button>
                    <button type="button" onclick="hideEditUserModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <div class="w-20 h-20 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-500"></i>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">Delete User?</h3>
                <p class="text-white/60 mb-2" id="deleteUserName"></p>
                <p class="text-white/40 text-sm mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_user_id">
                    <button type="submit" name="delete_user"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition font-medium">
                        <i class="fas fa-trash mr-2"></i> Delete
                    </button>
                    <button type="button" onclick="hideDeleteUserModal()"
                            class="flex-1 bg-white/10 text-white py-3 rounded-lg hover:bg-white/20 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function showTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'text-[#C9A45A]', 'border-[#C9A45A]');
            btn.classList.add('text-white/70');
        });
        
        event.target.classList.add('active', 'text-[#C9A45A]');
        event.target.classList.remove('text-white/70');
        
        // Show selected tab
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        document.getElementById(tabName + 'Tab').classList.remove('hidden');
    }
    
    function showAddUserModal() {
        document.getElementById('addUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showEditUserModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_email').value = user.email;
        document.getElementById('edit_role').value = user.role;
        
        document.getElementById('editUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showDeleteUserModal(id, name) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('deleteUserName').innerHTML = `Are you sure you want to delete <span class="text-[#C9A45A] font-bold">${name}</span>?`;
        document.getElementById('deleteUserModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteUserModal() {
        document.getElementById('deleteUserModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('addUserModal').addEventListener('click', function(e) {
        if (e.target === this) hideAddUserModal();
    });
    
    document.getElementById('editUserModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditUserModal();
    });
    
    document.getElementById('deleteUserModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteUserModal();
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