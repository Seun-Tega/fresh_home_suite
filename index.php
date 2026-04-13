<?php
$page_title = 'Home';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get featured rooms with their primary images
$stmt = $pdo->query("
    SELECT r.*, 
           (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM rooms r 
    WHERE r.status = 'available' 
    LIMIT 3
");
$featured_rooms = $stmt->fetchAll();

// Get hall images
$stmt = $pdo->query("
    SELECT h.*, 
           (SELECT image_path FROM hall_images WHERE hall_id = h.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM hall h 
    LIMIT 1
");
$hall = $stmt->fetch();

// Get hall images gallery
$stmt = $pdo->prepare("SELECT * FROM hall_images WHERE hall_id = ? LIMIT 2");
$stmt->execute([$hall['id'] ?? 0]);
$hall_images = $stmt->fetchAll();

// Get menu items with their images
$stmt = $pdo->query("
    SELECT fi.*, fc.name as category_name
    FROM food_items fi 
    JOIN food_categories fc ON fi.category_id = fc.id 
    WHERE fi.is_available = 1 
    ORDER BY fi.display_order LIMIT 6
");
$menu_items = $stmt->fetchAll();

// Get video from database - FIXED PATH CHECK
$video = null;
try {
    $stmt = $pdo->query("SELECT * FROM videos ORDER BY id DESC LIMIT 1");
    $video = $stmt->fetch();
    
    // Check if video file actually exists
    if ($video && !empty($video['video_path'])) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $video['video_path'];
        if (!file_exists($full_path)) {
            $video = null; // Video file doesn't exist
        }
    }
} catch (Exception $e) {
    // Table might not exist yet
    $video = null;
}

// Get board rooms with images - FIXED
$board_room = null;
$board_room_images = [];
try {
    $stmt = $pdo->query("
        SELECT br.*,
               (SELECT image_path FROM boardroom_images WHERE boardroom_id = br.id AND is_primary = 1 LIMIT 1) as primary_image
        FROM boardrooms br 
        WHERE br.is_available = 1 
        ORDER BY br.display_order 
        LIMIT 1
    ");
    $board_room = $stmt->fetch();
    
    if ($board_room) {
        $stmt = $pdo->prepare("SELECT image_path FROM boardroom_images WHERE boardroom_id = ?");
        $stmt->execute([$board_room['id']]);
        $board_room_images = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // Table might not exist yet
    $board_room = null;
}
?>

<style>
    /* Fix for hero section going under navbar */
    .hero-section {
        margin-top: -80px;
        padding-top: 80px;
    }
    
    /* Responsive text utilities */
    @media (max-width: 640px) {
        .hero-title {
            font-size: 2.5rem !important;
            line-height: 1.2 !important;
        }
        .hero-subtitle {
            font-size: 1.25rem !important;
        }
        button, a, .btn {
            min-height: 44px;
            min-width: 44px;
        }
        .booking-widget {
            padding: 1rem !important;
        }
        .booking-widget select,
        .booking-widget input,
        .booking-widget button {
            font-size: 16px !important;
            padding: 0.75rem !important;
        }
    }
    
    @media (min-width: 641px) and (max-width: 768px) {
        .hero-title {
            font-size: 3.5rem !important;
        }
    }
    
    /* Ensure images don't overflow */
    img {
        max-width: 100%;
        height: auto;
    }
    
    /* Room card hover effect */
    .hover-scale {
        transition: transform 0.3s ease;
    }
    .hover-scale:hover {
        transform: scale(1.02);
    }
    
    /* Hero Background Transitions */
    .hero-bg {
        transition: opacity 2s ease-in-out;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    
    /* Enhanced Animations */
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(2deg); }
    }
    .animate-float {
        animation: float 8s ease-in-out infinite;
    }
    
    .animation-delay-1000 {
        animation-delay: 1s;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    
    /* Glowing effect for CTA */
    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(201, 164, 90, 0.3); }
        50% { box-shadow: 0 0 40px rgba(201, 164, 90, 0.6); }
    }
    .booking-widget {
        animation: glow 3s ease-in-out infinite;
    }
    
    /* Particle animations */
    @keyframes ping {
        75%, 100% {
            transform: scale(2);
            opacity: 0;
        }
    }
    .animate-ping {
        animation: ping 3s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    
    /* Video container */
    .video-container video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Board room image */
    .board-room-img {
        transition: transform 0.3s ease;
    }
    .board-room-img:hover {
        transform: scale(1.02);
    }
</style>

<!-- Hero Section with Rotating Background Images -->
<section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden">
    <div class="absolute inset-0">
        <!-- Image 1 -->
        <div class="hero-bg absolute inset-0 bg-cover bg-center opacity-100 transition-opacity duration-1000" 
             style="background-image: url('<?php echo SITE_URL; ?>assets/images/hero/hero1.jpg');">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F]/80 via-[#0F0F0F]/60 to-[#0F0F0F]/80"></div>
        </div>
        
        <!-- Image 2 -->
        <div class="hero-bg absolute inset-0 bg-cover bg-center opacity-0 transition-opacity duration-1000" 
             style="background-image: url('<?php echo SITE_URL; ?>assets/images/hero/hero2.jpg');">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F]/80 via-[#0F0F0F]/60 to-[#0F0F0F]/80"></div>
        </div>
        
        <!-- Image 3 -->
        <div class="hero-bg absolute inset-0 bg-cover bg-center opacity-0 transition-opacity duration-1000" 
             style="background-image: url('<?php echo SITE_URL; ?>assets/images/hero/hero3.jpg');">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F]/80 via-[#0F0F0F]/60 to-[#0F0F0F]/80"></div>
        </div>
        
        <!-- Image 4 -->
        <div class="hero-bg absolute inset-0 bg-cover bg-center opacity-0 transition-opacity duration-1000" 
             style="background-image: url('<?php echo SITE_URL; ?>assets/images/hero/hero4.jpg');">
            <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F]/80 via-[#0F0F0F]/60 to-[#0F0F0F]/80"></div>
        </div>
    </div>
    
    <!-- Animated Gold Overlay Elements -->
    <div class="absolute inset-0 opacity-30 pointer-events-none">
        <div class="absolute top-0 left-0 w-48 sm:w-64 md:w-96 h-48 sm:h-64 md:h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-xl animate-float"></div>
        <div class="absolute bottom-0 right-0 w-48 sm:w-64 md:w-96 h-48 sm:h-64 md:h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-xl animate-float animation-delay-2000"></div>
    </div>
    
    <!-- Floating Particles -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-1/4 left-1/4 w-1 h-1 bg-[#C9A45A] rounded-full animate-ping"></div>
        <div class="absolute top-3/4 right-1/4 w-1.5 h-1.5 bg-[#C9A45A] rounded-full animate-ping animation-delay-1000"></div>
        <div class="absolute bottom-1/4 right-1/3 w-1 h-1 bg-[#C9A45A] rounded-full animate-ping animation-delay-2000"></div>
        <div class="absolute top-2/3 left-1/3 w-2 h-2 bg-[#C9A45A]/50 rounded-full animate-pulse"></div>
    </div>
    
    <!-- Content -->
    <div class="relative z-10 text-center text-[#F5F5F5] px-4 w-full max-w-7xl mx-auto">
        <!-- Welcome Badge -->
        <div class="inline-flex items-center bg-[#C9A45A]/10 backdrop-blur-sm border border-[#C9A45A]/30 rounded-full px-4 py-1 mb-6">
            <span class="w-2 h-2 bg-[#C9A45A] rounded-full animate-pulse mr-2"></span>
            <span class="text-sm font-medium text-[#C9A45A]">LUXURY REDEFINED</span>
        </div>
        
        <!-- Main Title -->
        <h1 class="hero-title text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-bold mb-4">
            <span>Welcome to</span>
            <br>
            <span class="text-[#C9A45A] relative inline-block mt-2">
                <span class="relative z-10">Fresh Home & Suite</span>
                <span class="absolute -bottom-2 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#C9A45A] to-transparent"></span>
            </span>
        </h1>
        
        <!-- Description -->
        <p class="hero-subtitle text-lg sm:text-xl md:text-2xl lg:text-3xl mb-8 text-[#F5F5F5]/90 max-w-4xl mx-auto px-4">
            Where Every Stay Feels Like Home
        </p>
        
        <!-- Booking Widget -->
        <div class="booking-widget max-w-4xl mx-auto bg-[#F5F5F5]/10 backdrop-blur-xl rounded-2xl p-4 sm:p-6 md:p-8 border border-[#C9A45A]/30 shadow-2xl mx-4 sm:mx-auto hover:border-[#C9A45A] transition-all duration-500">
            <form action="booking.php" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <div class="lg:col-span-2 relative group">
                    <i class="fas fa-calendar-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-[#C9A45A] text-sm sm:text-base z-10"></i>
                    <input type="text" name="dates" placeholder="Check-in - Check-out" 
                           class="w-full pl-10 pr-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-[#C9A45A] transition-all" 
                           id="dateRange">
                </div>
                <div class="relative group">
                    <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-[#C9A45A] text-sm sm:text-base z-10"></i>
                    <select name="adults" class="w-full pl-10 pr-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-[#C9A45A] appearance-none">
                        <option value="1">1 Adult</option>
                        <option value="2">2 Adults</option>
                        <option value="3">3 Adults</option>
                        <option value="4">4 Adults</option>
                    </select>
                </div>
                <div class="relative group">
                    <i class="fas fa-child absolute left-3 top-1/2 transform -translate-y-1/2 text-[#C9A45A] text-sm sm:text-base z-10"></i>
                    <select name="children" class="w-full pl-10 pr-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:outline-none focus:ring-2 focus:ring-[#C9A45A] appearance-none">
                        <option value="0">0 Children</option>
                        <option value="1">1 Child</option>
                        <option value="2">2 Children</option>
                        <option value="3">3 Children</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] font-bold py-3 px-4 rounded-lg transition-all transform hover:scale-105 text-sm sm:text-base">
                        Check Availability
                    </button>
                </div>
            </form>
            
            <!-- Quick Stats -->
            <div class="flex justify-center gap-6 mt-4 text-xs sm:text-sm text-[#F5F5F5]/60">
                <span><i class="fas fa-star text-[#C9A45A] mr-1"></i> 5-Star Service</span>
                <span><i class="fas fa-wifi text-[#C9A45A] mr-1"></i> Free WiFi</span>
                <span><i class="fas fa-parking text-[#C9A45A] mr-1"></i> Free Parking</span>
            </div>
        </div>
        
        <!-- Scroll Down Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#featured" class="flex flex-col items-center text-[#C9A45A] hover:text-[#A8843F] transition">
                <span class="text-xs uppercase tracking-wider mb-2 opacity-70">Explore</span>
                <i class="fas fa-chevron-down text-2xl animate-pulse"></i>
            </a>
        </div>
    </div>
</section>

<!-- Hero Image Rotator Script -->
<script>
let currentHeroImage = 0;
const heroImages = document.querySelectorAll('.hero-bg');
if (heroImages.length > 1) {
    setInterval(() => {
        heroImages.forEach(img => img.style.opacity = '0');
        heroImages[currentHeroImage].style.opacity = '1';
        currentHeroImage = (currentHeroImage + 1) % heroImages.length;
    }, 5000);
}
</script>

<!-- Featured Rooms Section -->
<section id="featured" class="py-12 sm:py-16 md:py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-[#F5F5F5] mb-4">Our Luxurious Rooms</h2>
        <p class="text-base sm:text-lg md:text-xl text-center text-[#F5F5F5]/80 mb-8 sm:mb-12">
            Experience comfort and elegance at Fresh Home & Suite Hotel
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
            <?php foreach($featured_rooms as $room): ?>
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl overflow-hidden hover-scale group border border-[#C9A45A]/20 h-full flex flex-col">
                <div class="relative h-48 sm:h-56 md:h-64 overflow-hidden">
                    <?php 
                    $img_path = '';
                    if(!empty($room['primary_image'])) {
                        $img_path = SITE_URL . $room['primary_image'];
                    } else {
                        $img_stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? LIMIT 1");
                        $img_stmt->execute([$room['id']]);
                        $first_image = $img_stmt->fetch();
                        if($first_image) $img_path = SITE_URL . $first_image['image_path'];
                    }
                    
                    if($img_path):
                    ?>
                        <img src="<?php echo $img_path; ?>" 
                             alt="<?php echo $room['room_type']; ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center">
                            <i class="fas fa-hotel text-4xl sm:text-5xl md:text-6xl text-[#C9A45A]/50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="absolute top-3 right-3 bg-[#C9A45A] text-[#F5F5F5] px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-semibold">
                        ₦<?php echo number_format($room['base_price'], 0); ?>/night
                    </div>
                </div>
                <div class="p-4 sm:p-6 flex-grow flex flex-col">
                    <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-[#F5F5F5] mb-2"><?php echo $room['room_type']; ?></h3>
                    <p class="text-[#F5F5F5]/70 text-sm sm:text-base mb-4"><?php echo substr($room['description'], 0, 100); ?>...</p>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mt-auto">
                        <div class="flex flex-wrap gap-3 sm:gap-4 text-[#F5F5F5]/60 text-xs sm:text-sm">
                            <span><i class="fas fa-bed text-[#C9A45A] mr-1"></i> <?php echo $room['bed_type']; ?></span>
                            <span><i class="fas fa-users text-[#C9A45A] mr-1"></i> Max <?php echo $room['max_occupancy']; ?></span>
                        </div>
                        <a href="room-detail.php?id=<?php echo $room['id']; ?>" 
                           class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-4 py-2 rounded-lg transition text-sm sm:text-base w-full sm:w-auto text-center">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8 sm:mt-12">
            <a href="rooms.php" class="inline-block bg-[#F5F5F5]/10 hover:bg-[#F5F5F5]/20 text-[#F5F5F5] border border-[#C9A45A]/30 px-6 sm:px-8 py-3 rounded-lg transition">
                View All Rooms <i class="fas fa-arrow-right ml-2 text-[#C9A45A]"></i>
            </a>
        </div>
    </div>
</section>

<!-- Event Hall Preview -->
<section class="py-12 sm:py-16 md:py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12">
            <div class="lg:w-1/2 text-center lg:text-left">
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#F5F5F5] mb-4">Grand Event Hall</h2>
                <p class="text-base sm:text-lg md:text-xl text-[#F5F5F5]/80 mb-6">Perfect for weddings, conferences, and special occasions</p>
                <ul class="space-y-2 sm:space-y-3 text-[#F5F5F5]/70 mb-8 text-left max-w-md mx-auto lg:mx-0">
                    <li class="flex items-center"><i class="fas fa-check-circle text-[#C9A45A] mr-2"></i> Capacity up to 200 guests</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-[#C9A45A] mr-2"></i> Flexible seating arrangements</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-[#C9A45A] mr-2"></i> State-of-the-art sound system</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-[#C9A45A] mr-2"></i> Catering services available</li>
                </ul>
                <a href="halls.php" class="inline-block bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-6 sm:px-8 py-3 rounded-lg transition">
                    Explore Hall <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="lg:w-1/2">
                <div class="grid grid-cols-2 gap-3 sm:gap-4">
                    <?php 
                    if(!empty($hall_images)):
                        $count = 0;
                        foreach($hall_images as $hall_image): 
                            if($count < 2):
                    ?>
                        <img src="<?php echo SITE_URL . $hall_image['image_path']; ?>" 
                             alt="Hall Setup" 
                             class="rounded-xl sm:rounded-2xl h-40 sm:h-48 md:h-56 lg:h-64 object-cover w-full <?php echo $count == 1 ? 'mt-4 sm:mt-6 md:mt-8' : ''; ?> border-2 border-[#C9A45A]/30">
                    <?php 
                            endif;
                            $count++;
                        endforeach; 
                    else:
                    ?>
                        <div class="rounded-xl sm:rounded-2xl h-40 sm:h-48 md:h-56 lg:h-64 w-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center border-2 border-[#C9A45A]/30">
                            <i class="fas fa-building text-3xl sm:text-4xl md:text-5xl text-[#C9A45A]/50"></i>
                        </div>
                        <div class="rounded-xl sm:rounded-2xl h-40 sm:h-48 md:h-56 lg:h-64 w-full mt-4 sm:mt-6 md:mt-8 bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center border-2 border-[#C9A45A]/30">
                            <i class="fas fa-building text-3xl sm:text-4xl md:text-5xl text-[#C9A45A]/50"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Restaurant Preview -->
<section class="py-12 sm:py-16 md:py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-[#F5F5F5] mb-4">Fresh Home & Suite Hotel Restaurant</h2>
        <p class="text-base sm:text-lg md:text-xl text-center text-[#F5F5F5]/80 mb-8 sm:mb-12">Delicious meals prepared by expert chefs</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach($menu_items as $item): ?>
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl overflow-hidden hover-scale border border-[#C9A45A]/20 h-full flex flex-col">
                <div class="relative h-36 sm:h-40 md:h-48">
                    <?php if(!empty($item['image_path'])): ?>
                        <img src="<?php echo SITE_URL . $item['image_path']; ?>" 
                             alt="<?php echo $item['name']; ?>" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center">
                            <i class="fas fa-utensils text-3xl sm:text-4xl md:text-5xl text-[#C9A45A]/50"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 sm:p-4 flex-grow flex flex-col">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="text-base sm:text-lg md:text-xl font-bold text-[#F5F5F5]"><?php echo $item['name']; ?></h3>
                        <span class="text-[#C9A45A] font-bold text-sm sm:text-base">₦<?php echo number_format($item['price'], 0); ?></span>
                    </div>
                    <p class="text-[#F5F5F5]/70 text-xs sm:text-sm"><?php echo $item['description']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8 sm:mt-12">
            <a href="eatery.php" class="inline-block bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-6 sm:px-8 py-3 rounded-lg transition">
                View Full Menu <i class="fas fa-utensils ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Board Room Preview - FIXED -->
<section class="py-12 sm:py-16 md:py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12">
            <div class="lg:w-1/2">
                <?php 
                $board_image = '';
                if($board_room && !empty($board_room['primary_image'])) {
                    $board_image = SITE_URL . $board_room['primary_image'];
                } elseif(!empty($board_room_images)) {
                    $board_image = SITE_URL . $board_room_images[0]['image_path'];
                } else {
                    $board_image = SITE_URL . 'assets/images/boardroom-default.jpg';
                }
                ?>
                <img src="<?php echo $board_image; ?>" 
                     alt="Executive Board Room"
                     class="board-room-img rounded-2xl shadow-2xl border-2 border-[#C9A45A]/30 w-full h-auto">
            </div>
            <div class="lg:w-1/2 text-center lg:text-left">
                <span class="text-[#C9A45A] font-semibold text-sm uppercase tracking-wider mb-2 block">Executive Meeting Space</span>
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#F5F5F5] mb-4">Professional Board Rooms</h2>
                <p class="text-base sm:text-lg text-[#F5F5F5]/80 mb-6">State-of-the-art facilities for your business meetings and conferences</p>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="bg-[#F5F5F5]/5 p-4 rounded-xl">
                        <i class="fas fa-video text-2xl text-[#C9A45A] mb-2"></i>
                        <h4 class="text-[#F5F5F5] font-semibold">Video Conferencing</h4>
                    </div>
                    <div class="bg-[#F5F5F5]/5 p-4 rounded-xl">
                        <i class="fas fa-wifi text-2xl text-[#C9A45A] mb-2"></i>
                        <h4 class="text-[#F5F5F5] font-semibold">High-Speed WiFi</h4>
                    </div>
                    <div class="bg-[#F5F5F5]/5 p-4 rounded-xl">
                        <i class="fas fa-projector text-2xl text-[#C9A45A] mb-2"></i>
                        <h4 class="text-[#F5F5F5] font-semibold">Projector & Screen</h4>
                    </div>
                    <div class="bg-[#F5F5F5]/5 p-4 rounded-xl">
                        <i class="fas fa-mug-hot text-2xl text-[#C9A45A] mb-2"></i>
                        <h4 class="text-[#F5F5F5] font-semibold">Refreshments</h4>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                    <a href="boardroom.php" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold px-6 sm:px-8 py-3 rounded-lg transition-all">
                        Explore Board Rooms <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <a href="boardroom-booking.php" class="border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] font-bold px-6 sm:px-8 py-3 rounded-lg transition-all">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Video Section - FIXED -->
<section class="py-12 sm:py-16 md:py-20 bg-[#0F0F0F] relative overflow-hidden">
    <div class="container mx-auto px-4 relative z-10">
        <div class="text-center mb-8 sm:mb-12">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#F5F5F5] mb-4">Experience Fresh Home & Suite</h2>
            <p class="text-base sm:text-lg text-[#F5F5F5]/80 max-w-2xl mx-auto">Take a virtual tour of our luxurious facilities</p>
        </div>
        
        <div class="max-w-5xl mx-auto">
            <div class="relative aspect-video rounded-2xl overflow-hidden border-2 border-[#C9A45A]/30 shadow-2xl bg-black video-container">
                <?php if($video && !empty($video['video_path'])): ?>
                    <video controls class="w-full h-full">
                        <source src="<?php echo SITE_URL . $video['video_path']; ?>" type="video/mp4">
                        <source src="<?php echo SITE_URL . $video['video_path']; ?>" type="video/webm">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] flex flex-col items-center justify-center">
                        <i class="fas fa-video-slash text-6xl text-[#C9A45A]/50 mb-4"></i>
                        <p class="text-[#F5F5F5]/60 text-center">No video uploaded yet</p>
                        <p class="text-sm text-[#F5F5F5]/40 mt-2">Admin can upload a video from the dashboard</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="flex flex-wrap justify-center gap-6 mt-6 text-sm text-[#F5F5F5]/60">
                <span><i class="fas fa-bed text-[#C9A45A] mr-1"></i> Luxury Rooms</span>
                <span><i class="fas fa-building text-[#C9A45A] mr-1"></i> Event Hall</span>
                <span><i class="fas fa-door-open text-[#C9A45A] mr-1"></i> Board Rooms</span>
                <span><i class="fas fa-utensils text-[#C9A45A] mr-1"></i> Restaurant</span>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-12 sm:py-16 md:py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative">
    <div class="container mx-auto px-4 relative z-10">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-[#F5F5F5] mb-8 sm:mb-12">What Our Guests Say</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-[#C9A45A]/20">
                <div class="flex items-center mb-3 sm:mb-4">
                    <div class="text-[#C9A45A] flex text-sm sm:text-base">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-[#F5F5F5]/80 text-sm sm:text-base mb-3 sm:mb-4">"Amazing experience! The rooms are beautiful and the staff is very friendly. Will definitely come back!"</p>
                <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-[#C9A45A] to-[#A8843F] rounded-full mr-3 sm:mr-4"></div>
                    <div>
                        <h4 class="text-[#F5F5F5] font-bold">John Ola</h4>
                        <p class="text-[#F5F5F5]/60 text-xs">Business Traveler</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-[#C9A45A]/20">
                <div class="flex items-center mb-3 sm:mb-4">
                    <div class="text-[#C9A45A] flex text-sm sm:text-base">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-[#F5F5F5]/80 text-sm sm:text-base mb-3 sm:mb-4">"The event hall is perfect for weddings. We had our reception here and everything was perfect!"</p>
                <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-[#C9A45A] to-[#A8843F] rounded-full mr-3 sm:mr-4"></div>
                    <div>
                        <h4 class="text-[#F5F5F5] font-bold">Segun Adegboye</h4>
                        <p class="text-[#F5F5F5]/60 text-xs">Event Organizer</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl sm:rounded-2xl p-4 sm:p-6 border border-[#C9A45A]/20">
                <div class="flex items-center mb-3 sm:mb-4">
                    <div class="text-[#C9A45A] flex text-sm sm:text-base">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                </div>
                <p class="text-[#F5F5F5]/80 text-sm sm:text-base mb-3 sm:mb-4">"The restaurant serves amazing food. The chef's special is a must-try!"</p>
                <div class="flex items-center">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-r from-[#C9A45A] to-[#A8843F] rounded-full mr-3 sm:mr-4"></div>
                    <div>
                        <h4 class="text-[#F5F5F5] font-bold">Mike Johnson</h4>
                        <p class="text-[#F5F5F5]/60 text-xs">Food Enthusiast</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
    .animate-float {
        animation: float 6s ease-in-out infinite;
    }
</style>

<?php require_once 'includes/footer.php'; ?>