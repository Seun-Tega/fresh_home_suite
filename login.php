<?php
$page_title = 'Login';
require_once 'config/config.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE email = ?");
    $stmt->execute([$email]);
    $guest = $stmt->fetch();
    
    if ($guest && password_verify($password, $guest['password'])) {
        $_SESSION['guest_id'] = $guest['id'];
        $_SESSION['guest_name'] = $guest['full_name'];
        $_SESSION['guest_email'] = $guest['email'];
        
        redirect('my-account.php');
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!-- Background with animated elements -->
<div class="min-h-screen flex items-center justify-center py-20 relative overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-[#0F0F0F] via-[#0F0F0F] to-[#0F0F0F]">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-3xl animate-float"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-3xl animate-float animation-delay-2000"></div>
        </div>
    </div>
    
    <!-- Decorative gold lines -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-px h-40 bg-gradient-to-b from-[#C9A45A] to-transparent"></div>
        <div class="absolute bottom-20 right-10 w-px h-40 bg-gradient-to-t from-[#C9A45A] to-transparent"></div>
        <div class="absolute top-40 right-20 w-20 h-px bg-gradient-to-r from-[#C9A45A] to-transparent"></div>
        <div class="absolute bottom-40 left-20 w-20 h-px bg-gradient-to-l from-[#C9A45A] to-transparent"></div>
    </div>
    
    <div class="max-w-md w-full relative z-10">
        <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20 shadow-2xl" data-aos="fade-up">
            <div class="text-center mb-8">
                <!-- Logo with gold effect -->
                <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-gradient-to-br from-[#C9A45A] to-[#A8843F] p-1">
                    <div class="w-full h-full rounded-full bg-[#0F0F0F] flex items-center justify-center">
                        <span class="text-3xl font-bold text-[#C9A45A]">FHS</span>
                    </div>
                </div>
                <h2 class="text-3xl font-bold text-[#F5F5F5]">Welcome Back</h2>
                <p class="text-[#F5F5F5]/70">Login to your Fresh Home & Suite Hotel account</p>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-lg mb-4 flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-300"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <!-- Success message for registration redirect -->
            <?php if(isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
            <div class="bg-green-500/20 border border-green-500 text-green-100 px-4 py-3 rounded-lg mb-4 flex items-center">
                <i class="fas fa-check-circle mr-2 text-green-300"></i>
                Registration successful! Please login with your credentials.
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6" id="loginForm">
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-envelope text-[#C9A45A] mr-1"></i> Email Address
                    </label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition"
                           placeholder="your@email.com"
                           value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                </div>
                
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-lock text-[#C9A45A] mr-1"></i> Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required 
                               class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition pr-10"
                               placeholder="••••••">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-3 text-[#C9A45A] hover:text-[#A8843F]">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" class="mr-2 accent-[#C9A45A]">
                        <span class="text-[#F5F5F5]/70 group-hover:text-[#C9A45A] transition text-sm">Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="text-[#C9A45A] hover:text-[#A8843F] transition text-sm flex items-center">
                        Forgot Password?
                        <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
                
                <button type="submit" 
                        class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-3 rounded-lg transition transform hover:scale-105 font-bold shadow-lg flex items-center justify-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login to Account
                </button>
            </form>
            
            <!-- Social Login Options -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-[#C9A45A]/20"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-[#0F0F0F] text-[#F5F5F5]/50">Or continue with</span>
                    </div>
                </div>
                
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button class="flex items-center justify-center px-4 py-2 border border-[#C9A45A]/20 rounded-lg hover:bg-[#F5F5F5]/5 transition group">
                        <i class="fab fa-google text-[#C9A45A] mr-2 group-hover:scale-110 transition"></i>
                        <span class="text-[#F5F5F5]/70 text-sm">Google</span>
                    </button>
                    <button class="flex items-center justify-center px-4 py-2 border border-[#C9A45A]/20 rounded-lg hover:bg-[#F5F5F5]/5 transition group">
                        <i class="fab fa-facebook-f text-[#C9A45A] mr-2 group-hover:scale-110 transition"></i>
                        <span class="text-[#F5F5F5]/70 text-sm">Facebook</span>
                    </button>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-[#F5F5F5]/70">
                    Don't have an account? 
                    <a href="register.php" class="text-[#C9A45A] hover:text-[#A8843F] font-semibold transition group">
                        Register here 
                        <i class="fas fa-arrow-right ml-1 text-sm group-hover:translate-x-1 transition-transform"></i>
                    </a>
                </p>
            </div>
            
            <!-- Guest Access -->
            <div class="mt-4 text-center">
                <a href="index.php" class="text-[#F5F5F5]/50 hover:text-[#C9A45A] transition text-sm">
                    <i class="fas fa-user mr-1"></i> Continue as Guest
                </a>
            </div>
        </div>
        
        <!-- Trust Badges -->
        <div class="flex justify-center gap-6 mt-6">
            <div class="text-center">
                <i class="fas fa-shield-alt text-[#C9A45A] text-xl"></i>
                <p class="text-[#F5F5F5]/50 text-xs mt-1">Secure Login</p>
            </div>
            <div class="text-center">
                <i class="fas fa-lock text-[#C9A45A] text-xl"></i>
                <p class="text-[#F5F5F5]/50 text-xs mt-1">Encrypted</p>
            </div>
            <div class="text-center">
                <i class="fas fa-clock text-[#C9A45A] text-xl"></i>
                <p class="text-[#F5F5F5]/50 text-xs mt-1">24/7 Support</p>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
function togglePassword() {
    const passwordField = document.getElementById('password');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    
    // Toggle icon
    const icon = event.currentTarget.querySelector('i');
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}

// Form validation with animation
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value;
    const password = document.querySelector('input[name="password"]').value;
    
    if (!email || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
    }
});

// Auto-focus email field
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('input[name="email"]').focus();
});

// Demo login credentials (for testing - remove in production)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'd') {
        document.querySelector('input[name="email"]').value = 'guest@freshhome.com';
        document.querySelector('input[name="password"]').value = 'demo123';
    }
});
</script>

<style>
/* Smooth hover effects */
.hover-scale:hover {
    transform: scale(1.02);
    transition: transform 0.3s ease;
}

/* Custom checkbox styling */
input[type="checkbox"] {
    width: 16px;
    height: 16px;
    cursor: pointer;
}
</style>

<?php require_once 'includes/footer.php'; ?>