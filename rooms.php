<?php
$page_title = 'Our Rooms';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get all rooms with their primary image from room_images table
$stmt = $pdo->query("
    SELECT r.*, 
           (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM rooms r 
    WHERE r.status = 'available' 
    ORDER BY r.room_type
");
$rooms = $stmt->fetchAll();

// Get room types for filter
$stmt = $pdo->query("SELECT DISTINCT room_type FROM rooms");
$room_types = $stmt->fetchAll();
?>

<style>
    /* Animation for floating elements */
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(2deg); }
    }
    
    .animate-float {
        animation: float 8s ease-in-out infinite;
    }
    
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    
    /* Room card hover effect */
    .hover-scale {
        transition: transform 0.3s ease;
    }
    
    .hover-scale:hover {
        transform: scale(1.02);
    }
    
    /* Responsive adjustments */
    @media (max-width: 640px) {
        .page-header h1 {
            font-size: 2.5rem !important;
            line-height: 1.2 !important;
        }
        
        .page-header p {
            font-size: 1rem !important;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .filter-bar {
            padding: 1rem !important;
            margin-left: 1rem !important;
            margin-right: 1rem !important;
        }
        
        .filter-bar select {
            width: 100%;
            font-size: 16px !important; /* Prevents zoom on iOS */
            padding: 0.75rem !important;
            margin-bottom: 0.5rem;
        }
        
        .filter-bar form {
            flex-direction: column;
            gap: 0.5rem !important;
        }
        
        .room-card .h-64 {
            height: 200px !important;
        }
        
        .room-card .grid-cols-3 {
            gap: 0.5rem !important;
        }
        
        .room-card .text-2xl {
            font-size: 1.25rem !important;
        }
        
        .room-card .flex-wrap.gap-2 {
            margin-bottom: 1rem !important;
        }
        
        .room-card .flex.gap-3 {
            flex-direction: column;
            gap: 0.5rem !important;
        }
        
        .room-card .flex.gap-3 a {
            width: 100%;
            text-align: center;
        }
    }
    
    /* Extra small devices (phones, 360px and down) */
    @media (max-width: 360px) {
        .page-header h1 {
            font-size: 2rem !important;
        }
        
        .room-card .grid-cols-3 {
            grid-template-columns: 1fr;
            gap: 0.75rem !important;
        }
        
        .room-card .grid-cols-3 > div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(201, 164, 90, 0.2);
            padding-bottom: 0.5rem;
        }
        
        .room-card .grid-cols-3 > div:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .room-card .grid-cols-3 i {
            margin-bottom: 0 !important;
            margin-right: 0.5rem;
            font-size: 1.25rem !important;
        }
        
        .room-card .grid-cols-3 p {
            display: inline-block;
            margin: 0;
        }
        
        .room-card .grid-cols-3 .text-xs {
            font-size: 0.75rem !important;
        }
        
        .room-card .grid-cols-3 .font-semibold {
            margin-left: auto;
        }
    }
    
    /* Small devices (phones, 361px to 480px) */
    @media (min-width: 361px) and (max-width: 480px) {
        .room-card .grid-cols-3 {
            gap: 0.25rem !important;
        }
        
        .room-card .grid-cols-3 i {
            font-size: 1.25rem !important;
        }
    }
    
    /* Medium devices (tablets) */
    @media (min-width: 641px) and (max-width: 768px) {
        .filter-bar select {
            font-size: 14px;
            padding: 0.5rem 1rem !important;
        }
        
        .room-card .h-64 {
            height: 220px !important;
        }
    }
    
    /* Landscape mode on phones */
    @media (max-height: 500px) and (orientation: landscape) {
        .page-header {
            padding-top: 2rem !important;
            padding-bottom: 2rem !important;
        }
        
        .filter-bar {
            margin-top: 0 !important;
        }
        
        .filter-bar form {
            flex-direction: row;
            flex-wrap: wrap;
        }
        
        .filter-bar select {
            width: auto;
            flex: 1;
            min-width: 150px;
        }
    }
    
    /* Better touch targets for mobile */
    @media (max-width: 768px) {
        button,
        a,
        select,
        .filter-bar select,
        .room-card a {
            min-height: 44px;
        }
        
        .room-card .px-3.py-1 {
            padding: 0.5rem 0.75rem !important;
            font-size: 0.75rem !important;
        }
    }
    
    /* Fix for notched phones */
    @supports (padding: max(0px)) {
        .container {
            padding-left: max(1rem, env(safe-area-inset-left));
            padding-right: max(1rem, env(safe-area-inset-right));
        }
    }
    
    /* No results message responsive */
    #noResults {
        padding: 2rem 1rem !important;
    }
    
    #noResults i {
        font-size: 3rem !important;
    }
    
    #noResults h3 {
        font-size: 1.5rem !important;
    }
    
    @media (min-width: 640px) {
        #noResults {
            padding: 4rem !important;
        }
        
        #noResults i {
            font-size: 4rem !important;
        }
        
        #noResults h3 {
            font-size: 2rem !important;
        }
    }
    
    /* Active filter indicator */
    select:focus {
        outline: none;
        ring: 2px solid #C9A45A;
    }
    
    /* Smooth transitions */
    .room-card {
        transition: all 0.3s ease;
    }
    
    /* Price badge responsive */
    .room-card .absolute.top-4.right-4 {
        font-size: 0.875rem;
        padding: 0.25rem 0.75rem;
    }
    
    @media (min-width: 640px) {
        .room-card .absolute.top-4.right-4 {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
    }
</style>

<!-- Page Header with Animation - Fully Responsive -->
<section class="page-header relative py-12 sm:py-16 md:py-20 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F] via-[#C9A45A]/20 to-[#0F0F0F]">
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 left-0 w-48 sm:w-64 md:w-96 h-48 sm:h-64 md:h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-xl md:blur-2xl lg:blur-3xl animate-float"></div>
            <div class="absolute top-0 right-0 w-48 sm:w-64 md:w-96 h-48 sm:h-64 md:h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-xl md:blur-2xl lg:blur-3xl animate-float animation-delay-2000"></div>
        </div>
    </div>
    
    <div class="relative z-10 text-center text-[#F5F5F5] px-4 sm:px-6 max-w-7xl mx-auto">
        <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold mb-2 sm:mb-3 md:mb-4 animate__animated animate__fadeInDown">
            Our <span class="text-[#C9A45A]">Luxurious Rooms</span>
        </h1>
        <p class="text-base sm:text-lg md:text-xl lg:text-2xl mb-4 sm:mb-6 md:mb-8 animate__animated animate__fadeInUp animate__delay-1s max-w-3xl mx-auto">
            Choose from our collection of beautifully designed rooms at The Branding Gold
        </p>
        
        <!-- Filter Bar - Fully Responsive -->
        <div class="filter-bar max-w-xs sm:max-w-sm md:max-w-2xl lg:max-w-4xl mx-auto bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl sm:rounded-2xl p-4 sm:p-5 md:p-6 animate__animated animate__zoomIn animate__delay-2s border border-[#C9A45A]/30 mx-2 sm:mx-4">
            <form id="filterForm" class="flex flex-col sm:flex-row gap-2 sm:gap-3 md:gap-4 justify-center">
                <select id="roomTypeFilter" class="w-full sm:flex-1 px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:ring-2 focus:ring-[#C9A45A] transition appearance-none">
                    <option value="all">All Room Types</option>
                    <?php foreach($room_types as $type): ?>
                    <option value="<?php echo $type['room_type']; ?>"><?php echo $type['room_type']; ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="priceFilter" class="w-full sm:flex-1 px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:ring-2 focus:ring-[#C9A45A] transition appearance-none">
                    <option value="all">Any Price</option>
                    <option value="0-5000">Under ₦5,000</option>
                    <option value="5000-10000">₦5,000 - ₦10,000</option>
                    <option value="10000-15000">₦10,000 - ₦15,000</option>
                    <option value="15000+">Above ₦15,000</option>
                </select>
                
                <select id="occupancyFilter" class="w-full sm:flex-1 px-3 sm:px-4 md:px-6 py-2.5 sm:py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] text-sm sm:text-base focus:ring-2 focus:ring-[#C9A45A] transition appearance-none">
                    <option value="all">Any Occupancy</option>
                    <option value="2">Up to 2 Guests</option>
                    <option value="3">Up to 3 Guests</option>
                    <option value="4">Up to 4 Guests</option>
                    <option value="5">5+ Guests</option>
                </select>
            </form>
            
            <!-- Mobile Filter Indicator (optional) -->
            <div class="sm:hidden text-left mt-2 text-xs text-[#F5F5F5]/60">
                <i class="fas fa-sliders-h text-[#C9A45A] mr-1"></i>
                Select filters to narrow down rooms
            </div>
        </div>
    </div>
</section>

<!-- Rooms Grid -->
<section class="py-8 sm:py-12 md:py-16 bg-[#0F0F0F]">
    <div class="container mx-auto px-3 sm:px-4">
        <div id="roomsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5 md:gap-6 lg:gap-8">
            <?php foreach($rooms as $index => $room): ?>
            <div class="room-card bg-[#F5F5F5]/10 backdrop-blur-lg rounded-xl sm:rounded-2xl overflow-hidden hover-scale group border border-[#C9A45A]/20" 
                 data-room-type="<?php echo $room['room_type']; ?>"
                 data-price="<?php echo $room['base_price']; ?>"
                 data-occupancy="<?php echo $room['max_occupancy']; ?>"
                 data-aos="fade-up" 
                 data-aos-delay="<?php echo $index * 100; ?>">
                
                <div class="relative h-48 sm:h-56 md:h-64 overflow-hidden">
                    <?php 
                    // Check if primary image exists
                    if(!empty($room['primary_image'])): 
                    ?>
                        <img src="<?php echo SITE_URL . $room['primary_image']; ?>" 
                             alt="<?php echo $room['room_type']; ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-700"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                    <?php 
                    else:
                        // Try to get any image for this room
                        $img_stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? LIMIT 1");
                        $img_stmt->execute([$room['id']]);
                        $any_image = $img_stmt->fetch();
                        
                        if($any_image):
                    ?>
                        <img src="<?php echo SITE_URL . $any_image['image_path']; ?>" 
                             alt="<?php echo $room['room_type']; ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-700"
                             loading="lazy"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                    <?php 
                        else:
                    ?>
                        <div class="w-full h-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center">
                            <i class="fas fa-hotel text-4xl sm:text-5xl md:text-6xl text-[#C9A45A]/50"></i>
                        </div>
                    <?php 
                        endif;
                    endif; 
                    ?>
                    
                    <!-- Price Badge -->
                    <div class="absolute top-3 right-3 sm:top-4 sm:right-4 bg-[#C9A45A] text-[#F5F5F5] px-2 sm:px-3 md:px-4 py-1 sm:py-1.5 md:py-2 rounded-full font-bold shadow-lg text-xs sm:text-sm md:text-base">
                        <?php echo formatCurrency($room['base_price']); ?>/night
                    </div>
                    
                    <!-- Quick View Overlay - Hidden on mobile, shown on hover on desktop -->
                    <div class="absolute inset-0 bg-[#0F0F0F]/70 opacity-0 lg:group-hover:opacity-100 transition duration-300 flex items-center justify-center">
                        <a href="room-detail.php?id=<?php echo $room['id']; ?>" 
                           class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-4 sm:px-5 md:px-6 py-2 sm:py-2.5 md:py-3 rounded-lg transition transform -translate-y-10 lg:group-hover:translate-y-0 text-sm sm:text-base">
                            <i class="fas fa-eye mr-1 sm:mr-2"></i> Quick View
                        </a>
                    </div>
                </div>
                
                <div class="p-3 sm:p-4 md:p-5 lg:p-6">
                    <h3 class="text-lg sm:text-xl md:text-2xl font-bold text-[#F5F5F5] mb-1 sm:mb-2"><?php echo $room['room_type']; ?></h3>
                    <p class="text-[#F5F5F5]/70 text-xs sm:text-sm md:text-base mb-3 sm:mb-4"><?php echo substr($room['description'], 0, 80); ?>...</p>
                    
                    <!-- Room Features - Responsive grid -->
                    <div class="grid grid-cols-3 gap-1 sm:gap-2 md:gap-3 lg:gap-4 mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        <div class="text-center">
                            <i class="fas fa-bed text-lg sm:text-xl md:text-2xl text-[#C9A45A] mb-0.5 sm:mb-1 md:mb-2"></i>
                            <p class="text-[#F5F5F5]/60 text-[10px] xs:text-xs">Bed</p>
                            <p class="text-[#F5F5F5] font-semibold text-[10px] xs:text-xs sm:text-sm truncate px-1"><?php echo $room['bed_type']; ?></p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-arrows-alt text-lg sm:text-xl md:text-2xl text-[#C9A45A] mb-0.5 sm:mb-1 md:mb-2"></i>
                            <p class="text-[#F5F5F5]/60 text-[10px] xs:text-xs">Size</p>
                            <p class="text-[#F5F5F5] font-semibold text-[10px] xs:text-xs sm:text-sm"><?php echo $room['square_feet']; ?> sq ft</p>
                        </div>
                        <div class="text-center">
                            <i class="fas fa-users text-lg sm:text-xl md:text-2xl text-[#C9A45A] mb-0.5 sm:mb-1 md:mb-2"></i>
                            <p class="text-[#F5F5F5]/60 text-[10px] xs:text-xs">Max</p>
                            <p class="text-[#F5F5F5] font-semibold text-[10px] xs:text-xs sm:text-sm"><?php echo $room['max_occupancy']; ?> Guests</p>
                        </div>
                    </div>
                    
                    <!-- Amenities Preview - Scrollable on mobile -->
                    <div class="flex flex-wrap gap-1 sm:gap-2 mb-3 sm:mb-4 md:mb-5 lg:mb-6">
                        <?php 
                        $amenities = explode(',', $room['amenities']);
                        $show_amenities = array_slice($amenities, 0, 3);
                        foreach($show_amenities as $amenity): 
                        ?>
                        <span class="px-2 sm:px-3 py-0.5 sm:py-1 bg-[#F5F5F5]/20 rounded-full text-[#F5F5F5] text-[10px] xs:text-xs border border-[#C9A45A]/30 whitespace-nowrap">
                            <?php echo trim($amenity); ?>
                        </span>
                        <?php endforeach; ?>
                        <?php if(count($amenities) > 3): ?>
                        <span class="px-2 sm:px-3 py-0.5 sm:py-1 bg-[#F5F5F5]/20 rounded-full text-[#F5F5F5] text-[10px] xs:text-xs border border-[#C9A45A]/30">
                            +<?php echo count($amenities) - 3; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons - Stack on mobile -->
                    <div class="flex flex-col xs:flex-row gap-2 sm:gap-3">
                        <a href="room-detail.php?id=<?php echo $room['id']; ?>" 
                           class="flex-1 bg-[#F5F5F5]/20 text-[#F5F5F5] px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg hover:bg-[#F5F5F5]/30 transition text-center border border-[#C9A45A]/20 text-xs sm:text-sm md:text-base">
                            Details
                        </a>
                        <a href="booking.php?room_id=<?php echo $room['id']; ?>" 
                           class="flex-1 bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg transition text-center text-xs sm:text-sm md:text-base">
                            Book Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No Results Message - Responsive -->
        <div id="noResults" class="hidden text-center py-8 sm:py-12 md:py-16">
            <i class="fas fa-search text-4xl sm:text-5xl md:text-6xl text-[#C9A45A]/30 mb-3 sm:mb-4"></i>
            <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-[#F5F5F5] mb-1 sm:mb-2">No Rooms Found</h3>
            <p class="text-[#F5F5F5]/70 text-sm sm:text-base mb-3 sm:mb-4">Try adjusting your filters</p>
            <button onclick="resetFilters()" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-4 sm:px-5 md:px-6 py-2 sm:py-2.5 md:py-3 rounded-lg transition text-sm sm:text-base">
                Reset Filters
            </button>
        </div>
    </div>
</section>

<!-- Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roomTypeFilter = document.getElementById('roomTypeFilter');
    const priceFilter = document.getElementById('priceFilter');
    const occupancyFilter = document.getElementById('occupancyFilter');
    const roomCards = document.querySelectorAll('.room-card');
    const noResults = document.getElementById('noResults');
    
    function filterRooms() {
        let visibleCount = 0;
        
        roomCards.forEach(card => {
            const roomType = card.dataset.roomType;
            const price = parseInt(card.dataset.price);
            const occupancy = parseInt(card.dataset.occupancy);
            
            let typeMatch = roomTypeFilter.value === 'all' || roomType === roomTypeFilter.value;
            
            let priceMatch = true;
            if (priceFilter.value !== 'all') {
                if (priceFilter.value === '0-5000') priceMatch = price <= 5000;
                else if (priceFilter.value === '5000-10000') priceMatch = price > 5000 && price <= 10000;
                else if (priceFilter.value === '10000-15000') priceMatch = price > 10000 && price <= 15000;
                else if (priceFilter.value === '15000+') priceMatch = price > 15000;
            }
            
            let occupancyMatch = true;
            if (occupancyFilter.value !== 'all') {
                const filterOcc = parseInt(occupancyFilter.value);
                if (filterOcc === 5) occupancyMatch = occupancy >= 5;
                else occupancyMatch = occupancy <= filterOcc;
            }
            
            if (typeMatch && priceMatch && occupancyMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
            // Smooth scroll to no results
            noResults.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            noResults.classList.add('hidden');
        }
    }
    
    roomTypeFilter.addEventListener('change', filterRooms);
    priceFilter.addEventListener('change', filterRooms);
    occupancyFilter.addEventListener('change', filterRooms);
    
    // Add touch-friendly enhancements for mobile
    if (window.innerWidth <= 768) {
        // Make selects more touch-friendly
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.addEventListener('touchstart', function() {
                this.style.backgroundColor = '#F5F5F5';
            });
        });
    }
});

// Reset filters function
function resetFilters() {
    document.getElementById('roomTypeFilter').value = 'all';
    document.getElementById('priceFilter').value = 'all';
    document.getElementById('occupancyFilter').value = 'all';
    
    // Trigger filter
    const event = new Event('change');
    document.getElementById('roomTypeFilter').dispatchEvent(event);
}

// Handle orientation change
window.addEventListener('orientationchange', function() {
    // Re-apply any necessary adjustments
    setTimeout(function() {
        // Force repaint of cards
        const cards = document.querySelectorAll('.room-card');
        cards.forEach(card => {
            card.style.display = 'none';
            card.style.display = 'block';
        });
    }, 200);
});

// Lazy load images for better performance
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>