<?php
$page_title = 'Contact Us';
require_once 'config/config.php';
require_once 'includes/header.php';

// Fetch site settings
$settings = getSiteSettings($pdo);

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    
    // Send email to admin using dynamic email
    $to = getSetting($settings, 'hotel_email', 'info@freshhomehotel.com');
    $subject = "New Contact Message from $name";
    $body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
    
    mail($to, $subject, $body);
    
    $success = "Thank you for contacting " . getSetting($settings, 'hotel_name', 'Fresh Home & Suite Hotel') . ". We'll get back to you soon!";
}

// Get bank accounts for display
$bank_accounts = getBankAccounts($pdo);
?>

<!-- Hero Section -->
<section class="relative py-32 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F] via-[#C9A45A]/20 to-[#0F0F0F]">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 left-0 w-96 h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-xl animate-float"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-xl animate-float animation-delay-2000"></div>
        </div>
    </div>
    
    <div class="relative z-10 text-center text-[#F5F5F5] px-4">
        <h1 class="text-5xl md:text-7xl font-bold mb-4 animate__animated animate__fadeInDown">
            Contact <span class="text-[#C9A45A]">Us</span>
        </h1>
        <p class="text-xl md:text-2xl mb-8 animate__animated animate__fadeInUp animate__delay-1s">
            We're here to help 24/7 at <?php echo htmlspecialchars(getSetting($settings, 'hotel_name', 'Fresh Home & Suite Hotel')); ?>
        </p>
    </div>
</section>

<!-- Contact Information -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <?php if(isset($success)): ?>
        <div class="bg-green-500/20 border border-green-500 text-green-100 px-6 py-4 rounded-lg mb-8 animate__animated animate__fadeIn" data-aos="fade-up">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Form -->
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20" data-aos="fade-right">
                <h2 class="text-2xl font-bold text-[#F5F5F5] mb-6">Send us a Message</h2>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Your Name *</label>
                        <input type="text" name="name" required 
                               class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Email *</label>
                            <input type="email" name="email" required 
                                   class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2">Phone *</label>
                            <input type="tel" name="phone" required 
                                   class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[#F5F5F5]/70 mb-2">Message *</label>
                        <textarea name="message" rows="5" required 
                                  class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none"></textarea>
                    </div>
                    
                    <button type="submit" 
                            class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-3 rounded-lg transition transform hover:scale-105 font-bold">
                        <i class="fas fa-paper-plane mr-2"></i> Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="space-y-6" data-aos="fade-left">
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20">
                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-6">Get in Touch</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start group hover:translate-x-2 transition-transform">
                            <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center mr-4 group-hover:bg-[#C9A45A]/30 transition-colors">
                                <i class="fas fa-map-marker-alt text-[#C9A45A] text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-[#F5F5F5] font-bold mb-1">Address</h3>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hotel_address', '123 Hotel Street, City, Country')); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start group hover:translate-x-2 transition-transform">
                            <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center mr-4 group-hover:bg-[#C9A45A]/30 transition-colors">
                                <i class="fas fa-phone text-[#C9A45A] text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-[#F5F5F5] font-bold mb-1">Phone</h3>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hotel_phone', '+123 456 7890')); ?></p>
                                <?php 
                                $phone2 = getSetting($settings, 'hotel_phone_alt', '');
                                if(!empty($phone2)): 
                                ?>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars($phone2); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-start group hover:translate-x-2 transition-transform">
                            <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center mr-4 group-hover:bg-[#C9A45A]/30 transition-colors">
                                <i class="fas fa-envelope text-[#C9A45A] text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-[#F5F5F5] font-bold mb-1">Email</h3>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hotel_email', 'info@freshhomehotel.com')); ?></p>
                                <?php 
                                $email2 = getSetting($settings, 'hotel_email_alt', '');
                                if(!empty($email2)): 
                                ?>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars($email2); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php 
                        $whatsapp = getSetting($settings, 'whatsapp_number', '');
                        if(!empty($whatsapp)): 
                        ?>
                        <div class="flex items-start group hover:translate-x-2 transition-transform">
                            <div class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center mr-4 group-hover:bg-[#C9A45A]/30 transition-colors">
                                <i class="fab fa-whatsapp text-[#C9A45A] text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-[#F5F5F5] font-bold mb-1">WhatsApp</h3>
                                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars($whatsapp); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Bank Account Details -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20">
                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-6">Bank Details</h2>
                    
                    <?php if(!empty($bank_accounts)): ?>
                        <?php foreach($bank_accounts as $account): ?>
                        <div class="mb-4 last:mb-0 p-4 bg-[#F5F5F5]/5 rounded-lg border border-[#C9A45A]/10 hover:border-[#C9A45A]/30 transition-colors">
                            <p class="text-[#C9A45A] font-bold mb-2"><?php echo htmlspecialchars($account['bank_name']); ?></p>
                            <p class="text-[#F5F5F5]/70">Account Name: <?php echo htmlspecialchars($account['account_name']); ?></p>
                            <p class="text-[#F5F5F5]/70">Account Number: <span class="font-mono text-[#F5F5F5]"><?php echo htmlspecialchars($account['account_number']); ?></span></p>
                            <?php if(!empty($account['branch_details'])): ?>
                            <p class="text-[#F5F5F5]/60 text-sm mt-1"><?php echo htmlspecialchars($account['branch_details']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-[#F5F5F5]/60 text-center py-4">No bank accounts available</p>
                    <?php endif; ?>
                </div>
                
                <!-- Social Media Links -->
                <!-- Social Media Links -->
<div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20">
    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-6">Follow Us</h2>
    
    <div class="flex justify-center gap-4">
        <?php 
        $social_links = [
            'facebook' => ['icon' => 'fab fa-facebook-f', 'default' => '#'],
            'twitter' => ['icon' => 'fab fa-twitter', 'default' => '#'],
            'instagram' => ['icon' => 'fab fa-instagram', 'default' => '#'],
            'linkedin' => ['icon' => 'fab fa-linkedin-in', 'default' => '#']
        ];
        
        foreach($social_links as $social => $data):
            $link = getSetting($settings, 'social_' . $social, '');
            $href = !empty($link) ? $link : $data['default'];
        ?>
        <a href="<?php echo htmlspecialchars($href); ?>" target="_blank" rel="noopener" class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center hover:bg-[#C9A45A] transition-colors group">
            <i class="<?php echo $data['icon']; ?> text-[#C9A45A] group-hover:text-[#F5F5F5] text-xl"></i>
        </a>
        <?php endforeach; ?>
        
        <?php 
        // Add WhatsApp if available
        $whatsapp = getSetting($settings, 'whatsapp_number', '');
        if(!empty($whatsapp)):
        ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>" target="_blank" rel="noopener" class="w-12 h-12 bg-[#C9A45A]/20 rounded-full flex items-center justify-center hover:bg-[#C9A45A] transition-colors group">
            <i class="fab fa-whatsapp text-[#C9A45A] group-hover:text-[#F5F5F5] text-xl"></i>
        </a>
        <?php endif; ?>
    </div>
</div>
            </div>
        </div>
    </div>
</section>

<!-- Google Map -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-4 border border-[#C9A45A]/20" data-aos="zoom-in">
            <?php 
            $map_embed = getSetting($settings, 'map_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.123456789!2d3.3792057!3d6.5243793!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwMzEnMjcuOCJOIDPCsDIyJzQ1LjEiRQ!5e0!3m2!1sen!2sng!4v1234567890');
            ?>
            <iframe 
                src="<?php echo htmlspecialchars($map_embed); ?>"
                width="100%" 
                height="450" 
                style="border:0; border-radius: 1rem;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</section>

<!-- Business Hours -->
<section class="py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative overflow-hidden">
    <div class="absolute inset-0 bg-[#C9A45A]/5"></div>
    <div class="container mx-auto px-4 relative z-10">
        <h2 class="text-4xl font-bold text-center text-[#F5F5F5] mb-12" data-aos="fade-up">Business Hours</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <div class="text-center bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl p-6 border border-[#C9A45A]/20" data-aos="fade-up">
                <div class="inline-block p-3 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-concierge-bell text-2xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Front Desk</h3>
                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hours_front_desk', '24 Hours')); ?></p>
                <p class="text-[#F5F5F5]/60 text-sm">Always open</p>
            </div>
            
            <div class="text-center bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl p-6 border border-[#C9A45A]/20" data-aos="fade-up" data-aos-delay="100">
                <div class="inline-block p-3 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-utensils text-2xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Restaurant</h3>
                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hours_restaurant', '7:00 AM - 11:00 PM')); ?></p>
                <p class="text-[#F5F5F5]/60 text-sm">Daily</p>
            </div>
            
            <div class="text-center bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl p-6 border border-[#C9A45A]/20" data-aos="fade-up" data-aos-delay="200">
                <div class="inline-block p-3 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-dumbbell text-2xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Fitness Center</h3>
                <p class="text-[#F5F5F5]/70"><?php echo htmlspecialchars(getSetting($settings, 'hours_fitness', '6:00 AM - 10:00 PM')); ?></p>
                <p class="text-[#F5F5F5]/60 text-sm">Daily</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>