<?php
// Make sure we don't have any output buffering issues
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

// Fetch site settings (function is already defined in functions.php)
$settings = getSiteSettings($pdo);
?>

<!-- Main Content End (this closes the main tag from header.php) -->
</main>

<!-- Footer -->
<footer class="bg-[#0F0F0F] border-t border-[#C9A45A]/20 py-12 mt-16">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- About Section -->
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <img src="<?php echo SITE_URL; ?>assets/images/logo.png" 
                         alt="<?php echo htmlspecialchars(getSetting($settings, 'hotel_name', 'Fresh Home & Suite')); ?>" 
                         class="h-12 w-auto"
                         onerror="this.style.display='none'">
                    <span class="text-xl font-bold text-[#C9A45A]"><?php echo htmlspecialchars(getSetting($settings, 'hotel_name', 'Fresh Home & Suite')); ?></span>
                </div>
                <p class="text-[#F5F5F5]/70 text-sm leading-relaxed">
                    <?php echo htmlspecialchars(getSetting($settings, 'hotel_description', 'Experience luxury and comfort at Fresh Home and Suite Hotel. Your perfect getaway destination for business and leisure.')); ?>
                </p>
                <div class="flex space-x-4 mt-4">
    <?php 
    $social_links = [
        'facebook' => ['icon' => 'fab fa-facebook-f', 'default' => '#'],
        'twitter' => ['icon' => 'fab fa-twitter', 'default' => '#'],
        'instagram' => ['icon' => 'fab fa-instagram', 'default' => '#'],
        'whatsapp' => ['icon' => 'fab fa-whatsapp', 'default' => '#']
    ];
    
    foreach($social_links as $social => $data):
        $link = getSetting($settings, 'social_' . $social, '');
        // If no link in database, use default #
        $href = !empty($link) ? $link : $data['default'];
    ?>
    <a href="<?php echo htmlspecialchars($href); ?>" target="_blank" rel="noopener" class="text-[#F5F5F5] hover:text-[#C9A45A] transition">
        <i class="<?php echo $data['icon']; ?>"></i>
    </a>
    <?php endforeach; ?>
</div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-bold text-[#C9A45A] mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="<?php echo SITE_URL; ?>rooms.php" 
                           class="text-[#F5F5F5]/70 hover:text-[#C9A45A] transition text-sm">
                            <i class="fas fa-chevron-right text-[#C9A45A] text-xs mr-2"></i>
                            Rooms
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>halls.php" 
                           class="text-[#F5F5F5]/70 hover:text-[#C9A45A] transition text-sm">
                            <i class="fas fa-chevron-right text-[#C9A45A] text-xs mr-2"></i>
                            Event Hall
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>eatery.php" 
                           class="text-[#F5F5F5]/70 hover:text-[#C9A45A] transition text-sm">
                            <i class="fas fa-chevron-right text-[#C9A45A] text-xs mr-2"></i>
                            Restaurant
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>about.php" 
                           class="text-[#F5F5F5]/70 hover:text-[#C9A45A] transition text-sm">
                            <i class="fas fa-chevron-right text-[#C9A45A] text-xs mr-2"></i>
                            About Us
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>contact.php" 
                           class="text-[#F5F5F5]/70 hover:text-[#C9A45A] transition text-sm">
                            <i class="fas fa-chevron-right text-[#C9A45A] text-xs mr-2"></i>
                            Contact
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div>
                <h3 class="text-lg font-bold text-[#C9A45A] mb-4">Contact Info</h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt text-[#C9A45A] mt-1 mr-3"></i>
                        <span class="text-[#F5F5F5]/70 text-sm"><?php echo htmlspecialchars(getSetting($settings, 'hotel_address', '123 Hotel Street, City, Country')); ?></span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-phone text-[#C9A45A] mr-3"></i>
                        <span class="text-[#F5F5F5]/70 text-sm"><?php echo htmlspecialchars(getSetting($settings, 'hotel_phone', '+123 456 7890')); ?></span>
                    </li>
                    <li class="flex items-center">
                        <i class="fas fa-envelope text-[#C9A45A] mr-3"></i>
                        <span class="text-[#F5F5F5]/70 text-sm"><?php echo htmlspecialchars(getSetting($settings, 'hotel_email', 'info@freshhomehotel.com')); ?></span>
                    </li>
                    <?php 
                    $whatsapp = getSetting($settings, 'whatsapp_number', '');
                    if(!empty($whatsapp)):
                    ?>
                    <li class="flex items-center">
                        <i class="fab fa-whatsapp text-[#C9A45A] mr-3"></i>
                        <span class="text-[#F5F5F5]/70 text-sm"><?php echo htmlspecialchars($whatsapp); ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Business Hours -->
            <div>
                <h3 class="text-lg font-bold text-[#C9A45A] mb-4">Business Hours</h3>
                <ul class="space-y-2">
                    <li class="flex justify-between text-sm">
                        <span class="text-[#F5F5F5]/60">Monday - Friday</span>
                        <span class="text-[#C9A45A]"><?php echo htmlspecialchars(getSetting($settings, 'hours_weekdays', '24 Hours')); ?></span>
                    </li>
                    <li class="flex justify-between text-sm">
                        <span class="text-[#F5F5F5]/60">Saturday</span>
                        <span class="text-[#C9A45A]"><?php echo htmlspecialchars(getSetting($settings, 'hours_saturday', '24 Hours')); ?></span>
                    </li>
                    <li class="flex justify-between text-sm">
                        <span class="text-[#F5F5F5]/60">Sunday</span>
                        <span class="text-[#C9A45A]"><?php echo htmlspecialchars(getSetting($settings, 'hours_sunday', '24 Hours')); ?></span>
                    </li>
                </ul>
                
                <!-- Newsletter -->
                <div class="mt-6">
                    <h4 class="text-sm font-bold text-[#C9A45A] mb-2">Newsletter</h4>
                    <form action="<?php echo SITE_URL; ?>subscribe.php" method="POST" class="flex">
                        <input type="email" name="email" placeholder="Your email" required
                               class="flex-1 px-3 py-2 text-sm bg-[#0F0F0F] border border-[#C9A45A]/20 rounded-l-lg text-[#F5F5F5] focus:outline-none focus:border-[#C9A45A]">
                        <button type="submit" 
                                class="px-3 py-2 bg-[#C9A45A] text-[#0F0F0F] rounded-r-lg hover:bg-[#A8843F] transition text-sm">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="border-t border-[#C9A45A]/20 mt-10 pt-6">
            <div class="flex flex-col md:flex-row justify-between items-center text-sm text-[#F5F5F5]/60">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting($settings, 'hotel_name', 'Fresh Home and Suite Hotel')); ?>. All rights reserved.</p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    <a href="<?php echo SITE_URL; ?>privacy.php" class="hover:text-[#C9A45A] transition">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>terms.php" class="hover:text-[#C9A45A] transition">Terms of Service</a>
                    <a href="<?php echo SITE_URL; ?>sitemap.php" class="hover:text-[#C9A45A] transition">Sitemap</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button id="backToTop" 
        class="fixed bottom-8 right-8 bg-[#C9A45A] text-[#0F0F0F] w-12 h-12 rounded-full shadow-lg opacity-0 invisible transition-all duration-300 hover:bg-[#A8843F] focus:outline-none z-50 flex items-center justify-center">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/main.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/animations.js"></script>

<script>
// Initialize AOS
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 1000,
            once: true,
            offset: 50
        });
    }
    
    // Back to top button
    const backToTop = document.getElementById('backToTop');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTop.classList.remove('opacity-0', 'invisible');
                backToTop.classList.add('opacity-100', 'visible');
            } else {
                backToTop.classList.add('opacity-0', 'invisible');
                backToTop.classList.remove('opacity-100', 'visible');
            }
        });
        
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});

// Simple mobile menu toggle if not already defined
if (typeof toggleMobileMenu === 'undefined') {
    window.toggleMobileMenu = function() {
        const menu = document.getElementById('mobileMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    };
}
</script>

<!-- Ensure body styles are correct -->
<style>
    /* Make sure body has proper padding for fixed navbar */
    body {
        padding-top: 80px;
        margin: 0;
        background-color: #0F0F0F;
        color: #F5F5F5;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    
    /* Ensure footer sticks to bottom */
    footer {
        margin-top: auto;
    }
    
    /* Back to top button styles */
    #backToTop {
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
    }
    
    #backToTop:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(201, 164, 90, 0.3);
    }
    
    /* Gold focus ring for accessibility */
    *:focus {
        outline: 2px solid #C9A45A;
        outline-offset: 2px;
    }
</style>

</body>
</html>