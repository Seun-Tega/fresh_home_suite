<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-[#0F0F0F]/95 backdrop-blur-lg fixed w-full z-50 top-0 left-0 border-b border-[#C9A45A]/20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-20">
            <!-- Logo - Made responsive with gold animated text and bouncing animation -->
            <a href="<?php echo SITE_URL; ?>" class="flex items-center space-x-2 sm:space-x-3 group">
                <div class="flex items-center space-x-2 sm:space-x-3 animate-bounce-logo">
                    <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="Fresh Home & Suite Hotel" 
                         class="h-8 sm:h-10 lg:h-12 w-auto transition-transform duration-300 group-hover:scale-110">
                    <span class="text-lg sm:text-xl lg:text-2xl font-bold truncate max-w-[150px] sm:max-w-[200px] lg:max-w-none relative overflow-hidden">
                        <span class="text-[#C9A45A] inline-block">Fresh Home & Suite Hotel</span>
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-[#C9A45A] transition-all duration-300 group-hover:w-full"></span>
                    </span>
                </div>
            </a>
            
            <!-- Desktop Menu - Hidden on mobile -->
            <div class="hidden lg:flex items-center space-x-6 xl:space-x-8">
                <a href="<?php echo SITE_URL; ?>" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo ($current_page == 'index.php' || $current_page == '') ? 'text-[#C9A45A]' : ''; ?>">
                    Home
                </a>
                <a href="<?php echo SITE_URL; ?>rooms.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo (strpos($current_page, 'room') !== false) ? 'text-[#C9A45A]' : ''; ?>">
                    Rooms
                </a>
                <a href="<?php echo SITE_URL; ?>boardroom.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo (strpos($current_page, 'boardroom') !== false) ? 'text-[#C9A45A]' : ''; ?>">
                    Board Rooms
                </a>
                <a href="<?php echo SITE_URL; ?>halls.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo (strpos($current_page, 'hall') !== false) ? 'text-[#C9A45A]' : ''; ?>">
                    Event Hall
                </a>
                <a href="<?php echo SITE_URL; ?>eatery.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo ($current_page == 'eatery.php') ? 'text-[#C9A45A]' : ''; ?>">
                    Restaurant
                </a>
                <a href="<?php echo SITE_URL; ?>gallery.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo ($current_page == 'gallery.php') ? 'text-[#C9A45A]' : ''; ?>">
                    Gallery
                </a>
                <a href="<?php echo SITE_URL; ?>about.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo ($current_page == 'about.php') ? 'text-[#C9A45A]' : ''; ?>">
                    About
                </a>
                <a href="<?php echo SITE_URL; ?>contact.php" 
                   class="text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm xl:text-base <?php echo ($current_page == 'contact.php') ? 'text-[#C9A45A]' : ''; ?>">
                    Contact
                </a>
            </div>
            
            <!-- Right Menu - Responsive -->
            <div class="hidden md:flex items-center space-x-2 sm:space-x-4">
                <?php if(isset($_SESSION['guest_id'])): ?>
                    <!-- User Dropdown - Desktop -->
                    <div class="relative group hidden lg:block">
                        <button class="flex items-center space-x-1 sm:space-x-2 text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm sm:text-base">
                            <i class="fas fa-user-circle text-xl sm:text-2xl"></i>
                            <span class="hidden xl:inline max-w-[100px] truncate"><?php echo $_SESSION['guest_name']; ?></span>
                            <i class="fas fa-chevron-down text-xs sm:text-sm"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <a href="<?php echo SITE_URL; ?>my-account.php" 
                               class="block px-4 py-2 text-sm text-[#F5F5F5] hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] rounded-t-lg">
                                <i class="fas fa-calendar-alt mr-2"></i> My Bookings
                            </a>
                            <a href="<?php echo SITE_URL; ?>profile.php" 
                               class="block px-4 py-2 text-sm text-[#F5F5F5] hover:bg-[#C9A45A]/10 hover:text-[#C9A45A]">
                                <i class="fas fa-user mr-2"></i> Profile
                            </a>
                            <a href="<?php echo SITE_URL; ?>logout.php" 
                               class="block px-4 py-2 text-sm text-[#F5F5F5] hover:bg-[#C9A45A]/10 hover:text-[#C9A45A] rounded-b-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i> Logout
                            </a>
                        </div>
                    </div>
                    
                    <!-- User Icon for Tablet -->
                    <div class="lg:hidden">
                        <a href="<?php echo SITE_URL; ?>my-account.php" class="text-[#F5F5F5] hover:text-[#C9A45A]">
                            <i class="fas fa-user-circle text-2xl"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Login/Register for larger screens -->
                    <a href="<?php echo SITE_URL; ?>login.php" 
                       class="hidden sm:inline-block text-[#F5F5F5] hover:text-[#C9A45A] transition duration-300 text-sm lg:text-base <?php echo ($current_page == 'login.php') ? 'text-[#C9A45A]' : ''; ?>">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="<?php echo SITE_URL; ?>register.php" 
                       class="hidden sm:inline-block bg-[#C9A45A] text-[#0F0F0F] px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-[#A8843F] transition duration-300 font-medium text-sm lg:text-base whitespace-nowrap <?php echo ($current_page == 'register.php') ? 'bg-[#A8843F]' : ''; ?>">
                        <i class="fas fa-user-plus mr-1"></i> Register
                    </a>
                    
                    <!-- Login icon for very small screens -->
                    <a href="<?php echo SITE_URL; ?>login.php" class="sm:hidden text-[#F5F5F5] hover:text-[#C9A45A]">
                        <i class="fas fa-sign-in-alt text-xl"></i>
                    </a>
                <?php endif; ?>
                
                <!-- Mobile Menu Button (shows on tablet and below) -->
                <button class="lg:hidden text-[#C9A45A] text-xl sm:text-2xl focus:outline-none ml-2" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Mobile Menu Button for very small screens (when right menu is hidden) -->
            <button class="md:hidden text-[#C9A45A] text-2xl focus:outline-none" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Mobile Menu - Improved for all screen sizes -->
        <div id="mobileMenu" class="hidden md:hidden py-2 max-h-[calc(100vh-80px)] overflow-y-auto">
            <div class="space-y-1">
                <a href="<?php echo SITE_URL; ?>" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'index.php' || $current_page == '') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-home w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Home</span>
                </a>
                <a href="<?php echo SITE_URL; ?>rooms.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo (strpos($current_page, 'room') !== false) ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-bed w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Rooms</span>
                </a>
                <a href="<?php echo SITE_URL; ?>boardroom.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo (strpos($current_page, 'boardroom') !== false) ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-door-open w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Board Rooms</span>
                </a>
                <a href="<?php echo SITE_URL; ?>halls.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo (strpos($current_page, 'hall') !== false) ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-building w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Event Hall</span>
                </a>
                <a href="<?php echo SITE_URL; ?>eatery.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'eatery.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-utensils w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Restaurant</span>
                </a>
                <a href="<?php echo SITE_URL; ?>gallery.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'gallery.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-images w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Gallery</span>
                </a>
                <a href="<?php echo SITE_URL; ?>about.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'about.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-info-circle w-6 mr-3 text-[#C9A45A]"></i>
                    <span>About</span>
                </a>
                <a href="<?php echo SITE_URL; ?>contact.php" 
                   class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'contact.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                    <i class="fas fa-envelope w-6 mr-3 text-[#C9A45A]"></i>
                    <span>Contact</span>
                </a>
                
                <?php if(!isset($_SESSION['guest_id'])): ?>
                    <!-- Divider -->
                    <div class="border-t border-[#C9A45A]/20 my-4"></div>
                    
                    <a href="<?php echo SITE_URL; ?>login.php" 
                       class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'login.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                        <i class="fas fa-sign-in-alt w-6 mr-3 text-[#C9A45A]"></i>
                        <span>Login</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>register.php" 
                       class="flex items-center py-3 px-2 bg-[#C9A45A] text-[#0F0F0F] rounded-lg hover:bg-[#A8843F] transition duration-300 font-medium mt-2 <?php echo ($current_page == 'register.php') ? 'bg-[#A8843F]' : ''; ?>">
                        <i class="fas fa-user-plus w-6 mr-3"></i>
                        <span>Register</span>
                    </a>
                <?php else: ?>
                    <!-- Divider -->
                    <div class="border-t border-[#C9A45A]/20 my-4"></div>
                    
                    <div class="px-2 mb-2">
                        <p class="text-[#F5F5F5]/60 text-sm">Logged in as:</p>
                        <p class="text-[#C9A45A] font-medium truncate"><?php echo $_SESSION['guest_name']; ?></p>
                    </div>
                    
                    <a href="<?php echo SITE_URL; ?>my-account.php" 
                       class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'my-account.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                        <i class="fas fa-calendar-alt w-6 mr-3 text-[#C9A45A]"></i>
                        <span>My Bookings</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>profile.php" 
                       class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg <?php echo ($current_page == 'profile.php') ? 'text-[#C9A45A] bg-[#C9A45A]/10' : ''; ?>">
                        <i class="fas fa-user w-6 mr-3 text-[#C9A45A]"></i>
                        <span>Profile</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>logout.php" 
                       class="flex items-center py-3 px-2 text-[#F5F5F5] hover:text-[#C9A45A] hover:bg-[#C9A45A]/5 transition duration-300 rounded-lg">
                        <i class="fas fa-sign-out-alt w-6 mr-3 text-[#C9A45A]"></i>
                        <span>Logout</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Add padding to body and animations -->
<style>
    body {
        padding-top: 64px; /* Default for mobile */
        background-color: #0F0F0F;
        min-height: 100vh;
    }
    
    @media (min-width: 640px) {
        body {
            padding-top: 72px; /* Tablet */
        }
    }
    
    @media (min-width: 1024px) {
        body {
            padding-top: 80px; /* Desktop */
        }
    }
    
    /* Smooth scrolling for anchor links */
    html {
        scroll-behavior: smooth;
    }
    
    /* Bouncing animation for logo and text */
    @keyframes bounce-logo {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-5px);
        }
    }
    
    .animate-bounce-logo {
        animation: bounce-logo 2s ease-in-out infinite;
    }
    
    /* Mobile menu animation */
    #mobileMenu {
        transition: all 0.3s ease-in-out;
        max-height: calc(100vh - 80px);
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    #mobileMenu.hidden {
        display: none;
    }
    
    /* Hide scrollbar for mobile menu but keep functionality */
    #mobileMenu::-webkit-scrollbar {
        width: 4px;
    }
    
    #mobileMenu::-webkit-scrollbar-track {
        background: rgba(201, 164, 90, 0.1);
    }
    
    #mobileMenu::-webkit-scrollbar-thumb {
        background: rgba(201, 164, 90, 0.3);
        border-radius: 4px;
    }
    
    #mobileMenu::-webkit-scrollbar-thumb:hover {
        background: rgba(201, 164, 90, 0.5);
    }
    
    /* Active link indicator */
    .nav-link-active {
        position: relative;
    }
    
    .nav-link-active::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #C9A45A;
        border-radius: 2px;
    }
    
    /* Hover effects */
    @media (min-width: 1024px) {
        .hover-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 164, 90, 0.2);
        }
    }
    
    /* Prevent body scroll when mobile menu is open */
    body.menu-open {
        overflow: hidden;
    }
    
    @media (min-width: 768px) {
        body.menu-open {
            overflow: auto;
        }
    }
</style>

<script>
// Mobile menu toggle with improved functionality
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const body = document.body;
    
    menu.classList.toggle('hidden');
    
    // Prevent body scroll when menu is open on mobile
    if (!menu.classList.contains('hidden')) {
        body.classList.add('menu-open');
    } else {
        body.classList.remove('menu-open');
    }
}

// Close mobile menu when clicking outside with improved detection
document.addEventListener('click', function(event) {
    const menu = document.getElementById('mobileMenu');
    const button = event.target.closest('button');
    
    // Check if clicked element is or is inside the menu button
    const isMenuButton = button && (button.querySelector('.fa-bars') !== null || button.innerHTML.includes('fa-bars'));
    
    // If menu is open and click is outside menu and not on menu button
    if (!menu.classList.contains('hidden') && !menu.contains(event.target) && !isMenuButton) {
        menu.classList.add('hidden');
        document.body.classList.remove('menu-open');
    }
});

// Close mobile menu on window resize
window.addEventListener('resize', function() {
    const menu = document.getElementById('mobileMenu');
    if (window.innerWidth >= 768) {
        menu.classList.add('hidden');
        document.body.classList.remove('menu-open');
    }
});

// Add active class to current page links
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const navLinks = document.querySelectorAll('nav a');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href').split('/').pop();
        
        if (href === currentPage) {
            link.classList.add('text-[#C9A45A]');
            
            // If it's a mobile menu link, add background
            if (link.closest('#mobileMenu')) {
                link.classList.add('bg-[#C9A45A]/10');
            }
        }
        
        // Special case for index.php when currentPage is empty
        if ((currentPage === '' || currentPage === 'index.php') && (href === '' || href === 'index.php' || href === SITE_URL)) {
            link.classList.add('text-[#C9A45A]');
        }
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            
            // Close mobile menu if open
            const menu = document.getElementById('mobileMenu');
            if (!menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                document.body.classList.remove('menu-open');
            }
        }
    });
});

// Add scroll effect to navbar
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('nav');
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        navbar.classList.remove('shadow-lg');
        return;
    }
    
    if (currentScroll > lastScroll && currentScroll > 100) {
        // Scrolling down - add shadow
        navbar.classList.add('shadow-lg');
    } else if (currentScroll < lastScroll) {
        // Scrolling up - remove shadow
        navbar.classList.remove('shadow-lg');
    }
    
    lastScroll = currentScroll;
});
</script>