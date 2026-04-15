<?php
$page_title = 'About Us';
require_once 'config/config.php';
require_once 'includes/header.php';
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
            About <span class="text-[#C9A45A]">Us</span>
        </h1>
        <p class="text-xl md:text-2xl mb-8 animate__animated animate__fadeInUp animate__delay-1s">
            Your home away from home at Fresh Home & Suite Hotel since 2025        </p>
    </div>
</section>

<!-- Our Story -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div data-aos="fade-right">
                <h2 class="text-4xl font-bold text-[#F5F5F5] mb-6">Our Story</h2>
                <p class="text-[#F5F5F5]/70 mb-4 leading-relaxed">
                    Fresh Home and Suite Hotel began with a simple vision: to create a haven where travelers could experience the perfect blend of luxury and comfort. Since opening our doors in 2010, we've been dedicated to providing exceptional hospitality and creating unforgettable memories for our guests.
                </p>
                <p class="text-[#F5F5F5]/70 mb-4 leading-relaxed">
                    Located in the heart of the city, our hotel offers 23 beautifully appointed rooms, a grand event hall, and a restaurant that serves culinary delights from around the world. Every detail has been carefully considered to ensure your stay with us is nothing short of extraordinary.
                </p>
                <p class="text-[#F5F5F5]/70 leading-relaxed">
                    Our team of dedicated professionals works tirelessly to anticipate your needs and exceed your expectations. Whether you're here for business or pleasure, we promise to make your stay memorable.
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-4" data-aos="fade-left">
                <?php 
                // Check if hero images exist, use local images instead of Unsplash
                $hero1_path = 'assets/images/hero/hero1.jpg';
                $hero2_path = 'assets/images/hero/hero2.jpg';
                $hero3_path = 'assets/images/hero/hero3.jpg';
                $hero4_path = 'assets/images/hero/hero4.jpg';
                
                // Use hero1.jpg for first image
                if (file_exists($hero1_path)): 
                ?>
                    <img src="<?php echo SITE_URL; ?>assets/images/hero/hero1.jpg" 
                         alt="Hotel Exterior" 
                         class="rounded-2xl h-64 object-cover w-full border-2 border-[#C9A45A]/30">
                <?php else: ?>
                    <div class="rounded-2xl h-64 w-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center border-2 border-[#C9A45A]/30">
                        <i class="fas fa-hotel text-5xl text-[#C9A45A]/50"></i>
                    </div>
                <?php endif; ?>
                
                <?php 
                // Use hero2.jpg for second image
                if (file_exists($hero2_path)): 
                ?>
                    <img src="<?php echo SITE_URL; ?>assets/images/hero/hero2.jpg" 
                         alt="Hotel Lobby" 
                         class="rounded-2xl h-64 object-cover w-full mt-8 border-2 border-[#C9A45A]/30">
                <?php else: ?>
                    <div class="rounded-2xl h-64 w-full mt-8 bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center border-2 border-[#C9A45A]/30">
                        <i class="fas fa-concierge-bell text-5xl text-[#C9A45A]/50"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section class="py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative overflow-hidden">
    <div class="absolute inset-0 bg-[#C9A45A]/5"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20" data-aos="fade-right">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-6">
                    <i class="fas fa-bullseye text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-2xl font-bold text-[#F5F5F5] mb-4">Our Mission</h3>
                <p class="text-[#F5F5F5]/70 leading-relaxed">
                    To provide exceptional hospitality services that exceed our guests' expectations, creating memorable experiences through personalized service, attention to detail, and a commitment to excellence in everything we do.
                </p>
            </div>
            
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20" data-aos="fade-left">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-6">
                    <i class="fas fa-eye text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-2xl font-bold text-[#F5F5F5] mb-4">Our Vision</h3>
                <p class="text-[#F5F5F5]/70 leading-relaxed">
                    To be the preferred choice for discerning travelers, renowned for our warm hospitality, innovative services, and unwavering dedication to creating a home away from home for every guest who walks through our doors.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-bold text-center text-[#F5F5F5] mb-12" data-aos="fade-up">Our Core Values</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="text-center" data-aos="fade-up" data-aos-delay="0">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-heart text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Hospitality</h3>
                <p class="text-[#F5F5F5]/70">Warm welcomes and genuine care for every guest</p>
            </div>
            
            <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-star text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Excellence</h3>
                <p class="text-[#F5F5F5]/70">Striving for perfection in every detail</p>
            </div>
            
            <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-handshake text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Integrity</h3>
                <p class="text-[#F5F5F5]/70">Honest and transparent in all our dealings</p>
            </div>
            
            <div class="text-center" data-aos="fade-up" data-aos-delay="300">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-leaf text-3xl text-[#F5F5F5]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Sustainability</h3>
                <p class="text-[#F5F5F5]/70">Committed to responsible tourism</p>
            </div>
        </div>
    </div>
</section>



<!-- Stats -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div class="text-center" data-aos="zoom-in">
                <div class="text-5xl font-bold text-[#C9A45A] mb-2" data-counter="10">10</div>
                <p class="text-[#F5F5F5]/70">Years of Service</p>
            </div>
            
            <div class="text-center" data-aos="zoom-in" data-aos-delay="100">
                <div class="text-5xl font-bold text-[#C9A45A] mb-2" data-counter="5000">5000+</div>
                <p class="text-[#F5F5F5]/70">Happy Guests</p>
            </div>
            
            <div class="text-center" data-aos="zoom-in" data-aos-delay="200">
                <div class="text-5xl font-bold text-[#C9A45A] mb-2" data-counter="23">23</div>
                <p class="text-[#F5F5F5]/70">Luxury Rooms</p>
            </div>
            
            <div class="text-center" data-aos="zoom-in" data-aos-delay="300">
                <div class="text-5xl font-bold text-[#C9A45A] mb-2" data-counter="50">50+</div>
                <p class="text-[#F5F5F5]/70">Staff Members</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative">
    <div class="absolute inset-0 bg-[#C9A45A]/10"></div>
    <div class="container mx-auto px-4 text-center relative z-10">
        <h2 class="text-4xl font-bold text-[#F5F5F5] mb-4" data-aos="fade-up">Experience Fresh Home & Suite Hotel</h2>
        <p class="text-xl text-[#F5F5F5]/70 mb-8 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Come and experience the perfect blend of luxury, comfort, and exceptional hospitality
        </p>
        <div class="flex flex-wrap gap-4 justify-center" data-aos="fade-up" data-aos-delay="200">
            <a href="rooms.php" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-8 py-3 rounded-lg transition transform hover:scale-105">
                View Our Rooms
            </a>
            <a href="contact.php" class="bg-[#F5F5F5]/20 hover:bg-[#F5F5F5]/30 text-[#F5F5F5] px-8 py-3 rounded-lg transition border border-[#C9A45A]/20 transform hover:scale-105">
                Contact Us
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>