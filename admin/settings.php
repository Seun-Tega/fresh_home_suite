<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Only Super Admin can change settings
if ($_SESSION['admin_role'] != 'super_admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: dashboard.php");
    exit();
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_general'])) {
        $settings = [
            'hotel_name' => $_POST['hotel_name'],
            'hotel_email' => $_POST['hotel_email'],
            'hotel_phone' => $_POST['hotel_phone'],
            'hotel_address' => $_POST['hotel_address'],
            'whatsapp_number' => $_POST['whatsapp_number'],
            'currency' => $_POST['currency'],
            'timezone' => $_POST['timezone']
        ];
        
        foreach($settings as $key => $value) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        $_SESSION['success'] = "General settings updated successfully!";
        header("Location: settings.php");
        exit();
    }
    
    if (isset($_POST['add_bank_account'])) {
        $bank_name = $_POST['bank_name'];
        $account_name = $_POST['account_name'];
        $account_number = $_POST['account_number'];
        $branch_details = $_POST['branch_details'];
        
        $stmt = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_name, account_number, branch_details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$bank_name, $account_name, $account_number, $branch_details]);
        
        $_SESSION['success'] = "Bank account added successfully!";
        header("Location: settings.php");
        exit();
    }
    
    if (isset($_POST['edit_bank_account'])) {
        $id = $_POST['id'];
        $bank_name = $_POST['bank_name'];
        $account_name = $_POST['account_name'];
        $account_number = $_POST['account_number'];
        $branch_details = $_POST['branch_details'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE bank_accounts SET bank_name=?, account_name=?, account_number=?, branch_details=?, is_active=? WHERE id=?");
        $stmt->execute([$bank_name, $account_name, $account_number, $branch_details, $is_active, $id]);
        
        $_SESSION['success'] = "Bank account updated successfully!";
        header("Location: settings.php");
        exit();
    }
    
    if (isset($_POST['delete_bank_account'])) {
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Bank account deleted successfully!";
        header("Location: settings.php");
        exit();
    }
    
    if (isset($_POST['update_security'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch();
        
        if (password_verify($current_password, $user['password'])) {
            if ($new_password == $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                
                $_SESSION['success'] = "Password updated successfully!";
            } else {
                $_SESSION['error'] = "New passwords do not match!";
            }
        } else {
            $_SESSION['error'] = "Current password is incorrect!";
        }
        
        header("Location: settings.php");
        exit();
    }
}

// Get site settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get bank accounts
$stmt = $pdo->query("SELECT * FROM bank_accounts ORDER BY is_active DESC, bank_name");
$bank_accounts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Fresh Home & Suite</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        body {
            background: #0F0F0F;
            min-height: 100vh;
        }
        .sidebar {
            background: rgba(15, 15, 15, 0.95);
            border-right: 1px solid rgba(201, 164, 90, 0.2);
        }
        .card-gradient {
            background: linear-gradient(145deg, rgba(201, 164, 90, 0.1) 0%, rgba(15, 15, 15, 0.95) 100%);
            border: 1px solid rgba(201, 164, 90, 0.2);
        }
        .heading-gold {
            color: #C9A45A;
        }
        .text-gold {
            color: #C9A45A;
        }
        .text-gold-hover:hover {
            color: #A8843F;
        }
        .border-gold {
            border-color: #C9A45A;
        }
        .bg-gold {
            background-color: #C9A45A;
        }
        .bg-gold-hover:hover {
            background-color: #A8843F;
        }
        .tab-btn {
            transition: all 0.3s ease;
        }
        .tab-btn.active {
            background: #C9A45A;
            color: #0F0F0F;
        }
        .input-field {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
        }
        .input-field:focus {
            border-color: #C9A45A;
            outline: none;
            ring: 2px solid #C9A45A;
        }
        .input-field option {
            background: #0F0F0F;
            color: #F5F5F5;
        }
        .bank-card {
            background: rgba(201, 164, 90, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            transition: all 0.3s ease;
        }
        .bank-card:hover {
            border-color: #C9A45A;
            transform: translateY(-2px);
        }
        .modal-content {
            background: #0F0F0F;
            border: 1px solid rgba(201, 164, 90, 0.3);
        }
        .modal-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(201, 164, 90, 0.2);
            color: #F5F5F5;
        }
        .modal-input:focus {
            border-color: #C9A45A;
            outline: none;
        }
    </style>
</head>
<body class="text-[#F5F5F5]">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 p-6 overflow-y-auto">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo" class="h-16 mx-auto mb-4">
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
                <a href="menu.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-utensils text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Restaurant Menu</span>
                </a>
                <a href="media.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-images text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Media Library</span>
                </a>
                <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-chart-bar text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Reports</span>
                </a>
                <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-[#C9A45A]/10 transition-colors">
                    <i class="fas fa-users text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Users</span>
                </a>
                <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-[#C9A45A]/20 border-l-4 border-[#C9A45A]">
                    <i class="fas fa-cog text-[#C9A45A]"></i>
                    <span class="text-[#F5F5F5]">Settings</span>
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
            <h1 class="text-3xl font-bold heading-gold mb-8">System Settings</h1>
            
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-[#C9A45A]/20 border border-[#C9A45A] text-[#F5F5F5] px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle text-[#C9A45A] mr-2"></i>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Tabs -->
            <div class="mb-6">
                <div class="flex flex-wrap gap-2">
                    <button onclick="showTab('general')" class="tab-btn active px-6 py-3 bg-[#C9A45A] text-[#0F0F0F] rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        General Settings
                    </button>
                    <button onclick="showTab('bank')" class="tab-btn px-6 py-3 bg-[#C9A45A]/10 text-[#F5F5F5]/70 rounded-lg hover:bg-[#C9A45A]/20 transition">
                        Bank Accounts
                    </button>
                    <button onclick="showTab('security')" class="tab-btn px-6 py-3 bg-[#C9A45A]/10 text-[#F5F5F5]/70 rounded-lg hover:bg-[#C9A45A]/20 transition">
                        Security
                    </button>
                    <button onclick="showTab('backup')" class="tab-btn px-6 py-3 bg-[#C9A45A]/10 text-[#F5F5F5]/70 rounded-lg hover:bg-[#C9A45A]/20 transition">
                        Backup
                    </button>
                </div>
            </div>
            
            <!-- General Settings Tab -->
            <div id="generalTab" class="tab-content">
                <div class="card-gradient rounded-2xl p-8">
                    <h2 class="text-2xl font-bold heading-gold mb-6">General Settings</h2>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">Hotel Name *</label>
                                <input type="text" name="hotel_name" value="<?php echo htmlspecialchars($settings['hotel_name'] ?? 'Fresh Home and Suite Hotel'); ?>" required
                                       class="w-full px-4 py-3 rounded-lg input-field">
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">Hotel Email *</label>
                                <input type="email" name="hotel_email" value="<?php echo htmlspecialchars($settings['hotel_email'] ?? ''); ?>" required
                                       class="w-full px-4 py-3 rounded-lg input-field">
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">Hotel Phone *</label>
                                <input type="tel" name="hotel_phone" value="<?php echo htmlspecialchars($settings['hotel_phone'] ?? ''); ?>" required
                                       class="w-full px-4 py-3 rounded-lg input-field">
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">WhatsApp Number</label>
                                <input type="tel" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>"
                                       class="w-full px-4 py-3 rounded-lg input-field">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-[#F5F5F5]/70 mb-2">Hotel Address *</label>
                                <textarea name="hotel_address" rows="3" required
                                          class="w-full px-4 py-3 rounded-lg input-field"><?php echo htmlspecialchars($settings['hotel_address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">Currency</label>
                                <select name="currency" class="w-full px-4 py-3 rounded-lg input-field">
                                    <option value="₦" <?php echo ($settings['currency'] ?? '₦') == '₦' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Nigerian Naira (₦)</option>
                                    <option value="$" <?php echo ($settings['currency'] ?? '') == '$' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">US Dollar ($)</option>
                                    <option value="€" <?php echo ($settings['currency'] ?? '') == '€' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Euro (€)</option>
                                    <option value="£" <?php echo ($settings['currency'] ?? '') == '£' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">British Pound (£)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2">Timezone</label>
                                <select name="timezone" class="w-full px-4 py-3 rounded-lg input-field">
                                    <option value="Africa/Lagos" <?php echo ($settings['timezone'] ?? 'Africa/Lagos') == 'Africa/Lagos' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Africa/Lagos (WAT)</option>
                                    <option value="Africa/Nairobi" <?php echo ($settings['timezone'] ?? '') == 'Africa/Nairobi' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Africa/Nairobi (EAT)</option>
                                    <option value="Africa/Cairo" <?php echo ($settings['timezone'] ?? '') == 'Africa/Cairo' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Africa/Cairo (EET)</option>
                                    <option value="Africa/Johannesburg" <?php echo ($settings['timezone'] ?? '') == 'Africa/Johannesburg' ? 'selected' : ''; ?> class="bg-[#0F0F0F]">Africa/Johannesburg (SAST)</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_general"
                                class="bg-[#C9A45A] text-[#0F0F0F] px-8 py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                            Save General Settings
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Bank Accounts Tab -->
            <div id="bankTab" class="tab-content hidden">
                <div class="card-gradient rounded-2xl p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold heading-gold">Bank Accounts</h2>
                        <button onclick="showBankModal()" 
                                class="bg-[#C9A45A] text-[#0F0F0F] px-4 py-2 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                            <i class="fas fa-plus mr-2"></i> Add Bank Account
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($bank_accounts as $account): ?>
                        <div class="bank-card rounded-xl p-6 <?php echo !$account['is_active'] ? 'opacity-60' : ''; ?>">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-xl font-bold heading-gold"><?php echo htmlspecialchars($account['bank_name']); ?></h3>
                                <?php if($account['is_active']): ?>
                                <span class="px-2 py-1 bg-[#C9A45A]/20 text-[#C9A45A] rounded-full text-xs font-semibold">Active</span>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-red-500/20 text-red-400 rounded-full text-xs">Inactive</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-[#F5F5F5]/70 mb-2">Account Name: <span class="text-[#F5F5F5]"><?php echo htmlspecialchars($account['account_name']); ?></span></p>
                            <p class="text-[#F5F5F5]/70 mb-2">Account Number: <span class="text-[#F5F5F5] font-mono"><?php echo htmlspecialchars($account['account_number']); ?></span></p>
                            <?php if($account['branch_details']): ?>
                            <p class="text-[#F5F5F5]/50 text-sm"><?php echo htmlspecialchars($account['branch_details']); ?></p>
                            <?php endif; ?>
                            
                            <div class="flex gap-2 mt-4">
                                <button onclick="showEditBankModal(<?php echo htmlspecialchars(json_encode($account)); ?>)" 
                                        class="flex-1 bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-3 py-2 rounded hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                                <button onclick="showDeleteBankModal(<?php echo $account['id']; ?>)" 
                                        class="flex-1 bg-red-500/20 text-red-400 border border-red-500 px-3 py-2 rounded hover:bg-red-500 hover:text-white transition">
                                    <i class="fas fa-trash mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div id="securityTab" class="tab-content hidden">
                <div class="card-gradient rounded-2xl p-8">
                    <h2 class="text-2xl font-bold heading-gold mb-6">Change Password</h2>
                    
                    <form method="POST" class="max-w-lg space-y-6">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Current Password *</label>
                            <input type="password" name="current_password" required 
                                   class="w-full px-4 py-3 rounded-lg input-field">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">New Password *</label>
                            <input type="password" name="new_password" required minlength="6"
                                   class="w-full px-4 py-3 rounded-lg input-field">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="6"
                                   class="w-full px-4 py-3 rounded-lg input-field">
                        </div>
                        
                        <button type="submit" name="update_security"
                                class="bg-[#C9A45A] text-[#0F0F0F] px-8 py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                            Update Password
                        </button>
                    </form>
                    
                    <div class="mt-8 pt-8 border-t border-[#C9A45A]/20">
                        <h3 class="text-xl font-bold heading-gold mb-4">Two-Factor Authentication</h3>
                        <p class="text-[#F5F5F5]/70 mb-4">Enhance your account security with 2FA.</p>
                        <button class="bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-6 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                            Enable 2FA
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Backup Tab -->
            <div id="backupTab" class="tab-content hidden">
                <div class="card-gradient rounded-2xl p-8">
                    <h2 class="text-2xl font-bold heading-gold mb-6">Database Backup</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bank-card rounded-xl p-6 text-center">
                            <i class="fas fa-database text-5xl text-[#C9A45A] mb-4"></i>
                            <h3 class="text-xl font-bold heading-gold mb-2">Manual Backup</h3>
                            <p class="text-[#F5F5F5]/70 mb-4">Create a backup of your database now</p>
                            <a href="backup-database.php" 
                               class="inline-block bg-[#C9A45A] text-[#0F0F0F] px-6 py-2 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                                <i class="fas fa-download mr-2"></i> Download Backup
                            </a>
                        </div>
                        
                        <div class="bank-card rounded-xl p-6 text-center">
                            <i class="fas fa-clock text-5xl text-[#C9A45A] mb-4"></i>
                            <h3 class="text-xl font-bold heading-gold mb-2">Auto Backup Settings</h3>
                            <p class="text-[#F5F5F5]/70 mb-4">Configure automatic backups</p>
                            <button class="bg-[#C9A45A]/20 text-[#C9A45A] border border-[#C9A45A] px-6 py-2 rounded-lg hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition">
                                Configure
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <h3 class="text-xl font-bold heading-gold mb-4">Recent Backups</h3>
                        <div class="bg-[#C9A45A]/5 rounded-xl p-4">
                            <p class="text-[#F5F5F5]/70 text-center py-4">No backups available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Bank Account Modal -->
    <div id="bankModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Add Bank Account</h3>
                <button onclick="hideBankModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Bank Name *</label>
                        <input type="text" name="bank_name" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Account Name *</label>
                        <input type="text" name="account_name" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Account Number *</label>
                        <input type="text" name="account_number" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Branch Details (Optional)</label>
                        <textarea name="branch_details" rows="2" 
                                  class="w-full px-4 py-2 rounded-lg modal-input"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="add_bank_account"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Add Account
                    </button>
                    <button type="button" onclick="hideBankModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Bank Account Modal -->
    <div id="editBankModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-lg w-full mx-4 p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold heading-gold">Edit Bank Account</h3>
                <button onclick="hideEditBankModal()" class="text-[#F5F5F5]/60 hover:text-[#C9A45A] transition-colors">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form method="POST" id="editBankForm">
                <input type="hidden" name="id" id="edit_bank_id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Bank Name *</label>
                        <input type="text" name="bank_name" id="edit_bank_name" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Account Name *</label>
                        <input type="text" name="account_name" id="edit_account_name" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Account Number *</label>
                        <input type="text" name="account_number" id="edit_account_number" required 
                               class="w-full px-4 py-2 rounded-lg modal-input">
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Branch Details</label>
                        <textarea name="branch_details" id="edit_branch_details" rows="2" 
                                  class="w-full px-4 py-2 rounded-lg modal-input"></textarea>
                    </div>
                    
                    <div>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="mr-2 accent-[#C9A45A]">
                            <span class="text-[#F5F5F5]">Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" name="edit_bank_account"
                            class="flex-1 bg-[#C9A45A] text-[#0F0F0F] py-3 rounded-lg hover:bg-[#A8843F] transition font-semibold">
                        Update Account
                    </button>
                    <button type="button" onclick="hideEditBankModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Bank Account Modal -->
    <div id="deleteBankModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="modal-content rounded-2xl max-w-md w-full mx-4 p-6">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle text-6xl text-[#C9A45A] mb-4"></i>
                <h3 class="text-2xl font-bold heading-gold mb-2">Delete Bank Account?</h3>
                <p class="text-[#F5F5F5]/60 mb-6">This action cannot be undone.</p>
                
                <form method="POST" class="flex gap-4">
                    <input type="hidden" name="id" id="delete_bank_id">
                    <button type="submit" name="delete_bank_account"
                            class="flex-1 bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition font-semibold">
                        Delete
                    </button>
                    <button type="button" onclick="hideDeleteBankModal()"
                            class="flex-1 bg-[#F5F5F5]/10 text-[#F5F5F5] py-3 rounded-lg hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
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
            btn.classList.remove('active', 'bg-[#C9A45A]', 'text-[#0F0F0F]');
            btn.classList.add('bg-[#C9A45A]/10', 'text-[#F5F5F5]/70');
        });
        
        event.target.classList.add('active', 'bg-[#C9A45A]', 'text-[#0F0F0F]');
        event.target.classList.remove('bg-[#C9A45A]/10', 'text-[#F5F5F5]/70');
        
        // Show selected tab
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        document.getElementById(tabName + 'Tab').classList.remove('hidden');
    }
    
    function showBankModal() {
        document.getElementById('bankModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideBankModal() {
        document.getElementById('bankModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showEditBankModal(account) {
        document.getElementById('edit_bank_id').value = account.id;
        document.getElementById('edit_bank_name').value = account.bank_name;
        document.getElementById('edit_account_name').value = account.account_name;
        document.getElementById('edit_account_number').value = account.account_number;
        document.getElementById('edit_branch_details').value = account.branch_details || '';
        document.getElementById('edit_is_active').checked = account.is_active == 1;
        
        document.getElementById('editBankModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideEditBankModal() {
        document.getElementById('editBankModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    function showDeleteBankModal(id) {
        document.getElementById('delete_bank_id').value = id;
        document.getElementById('deleteBankModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function hideDeleteBankModal() {
        document.getElementById('deleteBankModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Close modals when clicking outside
    document.getElementById('bankModal').addEventListener('click', function(e) {
        if (e.target === this) hideBankModal();
    });
    
    document.getElementById('editBankModal').addEventListener('click', function(e) {
        if (e.target === this) hideEditBankModal();
    });
    
    document.getElementById('deleteBankModal').addEventListener('click', function(e) {
        if (e.target === this) hideDeleteBankModal();
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