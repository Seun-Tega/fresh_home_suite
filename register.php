<?php
$page_title = 'Register';
require_once 'config/config.php';
require_once 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM guests WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Email already registered";
    } else {
        $stmt = $pdo->prepare("INSERT INTO guests (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$full_name, $email, $phone, $password])) {
            $_SESSION['guest_id'] = $pdo->lastInsertId();
            $_SESSION['guest_name'] = $full_name;
            $_SESSION['guest_email'] = $email;
            
            redirect('my-account.php');
        } else {
            $error = "Registration failed. Please try again.";
        }
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
    
    <!-- Decorative lines -->
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
                <h2 class="text-3xl font-bold text-[#F5F5F5]">Create Account</h2>
                <p class="text-[#F5F5F5]/70">Join Fresh Home & Suite Hotel for a better experience</p>
            </div>
            
            <?php if(isset($error)): ?>
            <div class="bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-lg mb-4 flex items-center">
                <i class="fas fa-exclamation-circle mr-2 text-red-300"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4" id="registerForm">
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-user text-[#C9A45A] mr-1"></i> Full Name *
                    </label>
                    <input type="text" name="full_name" required 
                           class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition"
                           placeholder="Enter your full name">
                </div>
                
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-envelope text-[#C9A45A] mr-1"></i> Email Address *
                    </label>
                    <input type="email" name="email" required 
                           class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition"
                           placeholder="your@email.com">
                </div>
                
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-phone text-[#C9A45A] mr-1"></i> Phone Number *
                    </label>
                    <input type="tel" name="phone" required 
                           class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition"
                           placeholder="+1234567890">
                </div>
                
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-lock text-[#C9A45A] mr-1"></i> Password *
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required minlength="6"
                               class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition pr-10"
                               placeholder="••••••">
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-3 text-[#C9A45A] hover:text-[#A8843F]">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <p class="text-[#F5F5F5]/50 text-xs mt-1">Minimum 6 characters</p>
                </div>
                
                <div>
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm font-medium">
                        <i class="fas fa-lock text-[#C9A45A] mr-1"></i> Confirm Password *
                    </label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none transition pr-10"
                               placeholder="••••••">
                        <button type="button" onclick="togglePassword('confirm_password')" class="absolute right-3 top-3 text-[#C9A45A] hover:text-[#A8843F]">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Password strength indicator -->
                <div id="passwordStrength" class="hidden">
                    <div class="flex gap-1 h-1 mt-1">
                        <div class="flex-1 h-full bg-gray-600 rounded" id="strength1"></div>
                        <div class="flex-1 h-full bg-gray-600 rounded" id="strength2"></div>
                        <div class="flex-1 h-full bg-gray-600 rounded" id="strength3"></div>
                    </div>
                    <p id="strengthText" class="text-xs text-[#F5F5F5]/50 mt-1"></p>
                </div>
                
                <div class="flex items-start">
                    <input type="checkbox" name="terms" id="terms" required 
                           class="mt-1 mr-2 accent-[#C9A45A]">
                    <label for="terms" class="text-[#F5F5F5]/70 text-sm">
                        I agree to the 
                        <a href="terms.php" class="text-[#C9A45A] hover:text-[#A8843F] transition font-medium">Terms of Service</a> 
                        and 
                        <a href="privacy.php" class="text-[#C9A45A] hover:text-[#A8843F] transition font-medium">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" 
                        class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-3 rounded-lg transition transform hover:scale-105 font-bold shadow-lg">
                    <i class="fas fa-user-plus mr-2"></i> Create Account
                </button>
            </form>
            
            <!-- Social Registration (Optional) -->
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
                    <button class="flex items-center justify-center px-4 py-2 border border-[#C9A45A]/20 rounded-lg hover:bg-[#F5F5F5]/5 transition">
                        <i class="fab fa-google text-[#C9A45A] mr-2"></i>
                        <span class="text-[#F5F5F5]/70 text-sm">Google</span>
                    </button>
                    <button class="flex items-center justify-center px-4 py-2 border border-[#C9A45A]/20 rounded-lg hover:bg-[#F5F5F5]/5 transition">
                        <i class="fab fa-facebook-f text-[#C9A45A] mr-2"></i>
                        <span class="text-[#F5F5F5]/70 text-sm">Facebook</span>
                    </button>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <p class="text-[#F5F5F5]/70">
                    Already have an account? 
                    <a href="login.php" class="text-[#C9A45A] hover:text-[#A8843F] font-semibold transition">
                        Login here <i class="fas fa-arrow-right ml-1 text-sm"></i>
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Benefits Cards -->
        <div class="grid grid-cols-3 gap-4 mt-6">
            <div class="text-center bg-[#F5F5F5]/5 rounded-lg p-3 border border-[#C9A45A]/20">
                <i class="fas fa-tag text-[#C9A45A] text-xl mb-1"></i>
                <p class="text-[#F5F5F5]/60 text-xs">Member Prices</p>
            </div>
            <div class="text-center bg-[#F5F5F5]/5 rounded-lg p-3 border border-[#C9A45A]/20">
                <i class="fas fa-gem text-[#C9A45A] text-xl mb-1"></i>
                <p class="text-[#F5F5F5]/60 text-xs">Rewards Points</p>
            </div>
            <div class="text-center bg-[#F5F5F5]/5 rounded-lg p-3 border border-[#C9A45A]/20">
                <i class="fas fa-bolt text-[#C9A45A] text-xl mb-1"></i>
                <p class="text-[#F5F5F5]/60 text-xs">Express Booking</p>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
    field.setAttribute('type', type);
}

// Password match validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirm = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('❌ Passwords do not match! Please check and try again.');
    }
});

// Password strength indicator (optional enhancement)
document.querySelector('input[name="password"]').addEventListener('input', function(e) {
    const password = e.target.value;
    const strengthIndicator = document.getElementById('passwordStrength');
    const strength1 = document.getElementById('strength1');
    const strength2 = document.getElementById('strength2');
    const strength3 = document.getElementById('strength3');
    const strengthText = document.getElementById('strengthText');
    
    if (password.length > 0) {
        strengthIndicator.classList.remove('hidden');
        
        // Calculate strength
        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/) && password.match(/[^a-zA-Z0-9]/)) strength++;
        
        // Update indicators
        const colors = ['#ef4444', '#f59e0b', '#10b981'];
        const texts = ['Weak', 'Medium', 'Strong'];
        
        strength1.style.backgroundColor = strength >= 1 ? colors[0] : '#4b5563';
        strength2.style.backgroundColor = strength >= 2 ? colors[1] : '#4b5563';
        strength3.style.backgroundColor = strength >= 3 ? colors[2] : '#4b5563';
        
        strengthText.textContent = strength > 0 ? `Password strength: ${texts[strength-1]}` : '';
        strengthText.style.color = strength > 0 ? colors[strength-1] : '#f5f5f580';
    } else {
        strengthIndicator.classList.add('hidden');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>