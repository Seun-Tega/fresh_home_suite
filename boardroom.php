<?php
$page_title = 'Board Rooms';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get all boardrooms
$stmt = $pdo->query("
    SELECT br.*, 
           (SELECT image_path FROM boardroom_images WHERE boardroom_id = br.id AND is_primary = 1 LIMIT 1) as primary_image
    FROM boardrooms br
    ORDER BY br.display_order
");
$boardrooms = $stmt->fetchAll();

// Get amenities
$amenities = [
    'projector' => 'Projector & Screen',
    'whiteboard' => 'Whiteboard',
    'wifi' => 'Free WiFi',
    'conferencing' => 'Video Conferencing',
    'catering' => 'Catering Available',
    'ac' => 'Air Conditioning',
    'sound' => 'Sound System',
    'recording' => 'Recording Facility',
    'secretarial' => 'Secretarial Services',
    'refreshments' => 'Refreshments'
];
?>

<style>
/* Boardroom Card Styles */
.boardroom-card {
    background: linear-gradient(145deg, rgba(201, 164, 90, 0.1) 0%, rgba(15, 15, 15, 0.95) 100%);
    border: 1px solid rgba(201, 164, 90, 0.2);
    transition: all 0.3s ease;
}

.boardroom-card:hover {
    transform: translateY(-10px);
    border-color: #C9A45A;
    box-shadow: 0 20px 40px rgba(201, 164, 90, 0.2);
}

/* Amenity Badge */
.amenity-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: rgba(201, 164, 90, 0.1);
    border: 1px solid rgba(201, 164, 90, 0.2);
    border-radius: 9999px;
    color: #F5F5F5;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.amenity-badge:hover {
    background: #C9A45A;
    color: #0F0F0F;
}

.amenity-badge i {
    color: #C9A45A;
    margin-right: 0.5rem;
    transition: all 0.3s ease;
}

.amenity-badge:hover i {
    color: #0F0F0F;
}

/* Price Tag */
.price-tag {
    background: #C9A45A;
    color: #0F0F0F;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    font-weight: bold;
    font-size: 1.25rem;
    display: inline-block;
}

.price-tag small {
    font-size: 0.875rem;
    font-weight: normal;
    opacity: 0.8;
}
</style>

<!-- Page Header -->
<section class="relative py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-64 h-64 bg-[#C9A45A] rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-[#C9A45A] rounded-full filter blur-3xl"></div>
    </div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-[#F5F5F5] mb-4" data-aos="fade-up">Executive Board Rooms</h1>
        <p class="text-lg sm:text-xl text-[#F5F5F5]/80 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Professional spaces for your important meetings and conferences
        </p>
    </div>
</section>

<!-- Board Rooms Grid -->
<section class="py-12 sm:py-16 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <?php if(empty($boardrooms)): ?>
        <div class="text-center py-12">
            <i class="fas fa-door-open text-6xl text-[#C9A45A]/30 mb-4"></i>
            <h3 class="text-2xl text-[#F5F5F5] mb-2">No Board Rooms Added Yet</h3>
            <p class="text-[#F5F5F5]/60">Check back soon for our board room offerings</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <?php foreach($boardrooms as $index => $room): ?>
            <div class="boardroom-card rounded-2xl overflow-hidden" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="relative h-64 sm:h-80 overflow-hidden">
                    <?php if(!empty($room['primary_image'])): ?>
                    <img src="<?php echo SITE_URL . $room['primary_image']; ?>" 
                         alt="<?php echo $room['name']; ?>"
                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                    <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center">
                        <i class="fas fa-door-open text-6xl text-[#C9A45A]/50"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($room['is_available']): ?>
                    <span class="absolute top-4 right-4 bg-green-500 text-[#F5F5F5] px-3 py-1 rounded-full text-sm font-semibold">
                        Available
                    </span>
                    <?php else: ?>
                    <span class="absolute top-4 right-4 bg-red-500 text-[#F5F5F5] px-3 py-1 rounded-full text-sm font-semibold">
                        Booked
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <h2 class="text-2xl sm:text-3xl font-bold text-[#F5F5F5]"><?php echo $room['name']; ?></h2>
                        <div class="price-tag">
                            ₦<?php echo number_format($room['price_per_hour'], 0); ?><small>/hour</small>
                        </div>
                    </div>
                    
                    <p class="text-[#F5F5F5]/70 mb-6 text-base sm:text-lg"><?php echo $room['description']; ?></p>
                    
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center p-3 bg-[#C9A45A]/10 rounded-lg">
                            <i class="fas fa-users text-2xl text-[#C9A45A] mb-2"></i>
                            <p class="text-[#F5F5F5] font-semibold">Capacity</p>
                            <p class="text-[#C9A45A]"><?php echo $room['capacity']; ?> People</p>
                        </div>
                        <div class="text-center p-3 bg-[#C9A45A]/10 rounded-lg">
                            <i class="fas fa-ruler-combined text-2xl text-[#C9A45A] mb-2"></i>
                            <p class="text-[#F5F5F5] font-semibold">Size</p>
                            <p class="text-[#C9A45A]"><?php echo $room['size_sqft']; ?> sq ft</p>
                        </div>
                    </div>
                    
                    <!-- Amenities -->
                    <?php 
                    $room_amenities = explode(',', $room['amenities']);
                    if(!empty($room_amenities[0])):
                    ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-[#F5F5F5] mb-3">Amenities</h3>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($room_amenities as $amenity): ?>
                                <?php if(isset($amenities[trim($amenity)])): ?>
                                <span class="amenity-badge">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo $amenities[trim($amenity)]; ?>
                                </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <a href="boardroom-booking.php?id=<?php echo $room['id']; ?>" 
                           class="flex-1 bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] font-bold py-3 px-6 rounded-lg transition-all transform hover:scale-105 text-center">
                            Book Now
                        </a>
                        <a href="boardroom-detail.php?id=<?php echo $room['id']; ?>" 
                           class="flex-1 border border-[#C9A45A]/30 hover:border-[#C9A45A] text-[#F5F5F5] font-bold py-3 px-6 rounded-lg transition-all text-center">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Us -->
<section class="py-12 sm:py-16 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-center text-[#F5F5F5] mb-12" data-aos="fade-up">
            Why Choose Our Board Rooms
        </h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="text-center p-6" data-aos="fade-up" data-aos-delay="100">
                <div class="w-16 h-16 mx-auto bg-[#C9A45A]/10 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-wifi text-2xl text-[#C9A45A]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#F5F5F5] mb-2">High-Speed Internet</h3>
                <p class="text-[#F5F5F5]/60">Dedicated fiber optic connection for seamless video conferencing</p>
            </div>
            
            <div class="text-center p-6" data-aos="fade-up" data-aos-delay="200">
                <div class="w-16 h-16 mx-auto bg-[#C9A45A]/10 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-video text-2xl text-[#C9A45A]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#F5F5F5] mb-2">Modern Equipment</h3>
                <p class="text-[#F5F5F5]/60">Latest projectors, screens, and conferencing systems</p>
            </div>
            
            <div class="text-center p-6" data-aos="fade-up" data-aos-delay="300">
                <div class="w-16 h-16 mx-auto bg-[#C9A45A]/10 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-coffee text-2xl text-[#C9A45A]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#F5F5F5] mb-2">Refreshments</h3>
                <p class="text-[#F5F5F5]/60">Complimentary coffee, tea, and snacks during your meeting</p>
            </div>
            
            <div class="text-center p-6" data-aos="fade-up" data-aos-delay="400">
                <div class="w-16 h-16 mx-auto bg-[#C9A45A]/10 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-headset text-2xl text-[#C9A45A]"></i>
                </div>
                <h3 class="text-lg font-bold text-[#F5F5F5] mb-2">24/7 Support</h3>
                <p class="text-[#F5F5F5]/60">Dedicated staff to assist with your technical needs</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-12 sm:py-16 bg-[#C9A45A]">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-[#0F0F0F] mb-4" data-aos="fade-up">
            Ready to Book Your Meeting Space?
        </h2>
        <p class="text-lg sm:text-xl text-[#0F0F0F]/80 mb-8 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Contact us now to reserve our executive board room for your next important meeting
        </p>
        <div class="flex flex-wrap justify-center gap-4" data-aos="fade-up" data-aos-delay="200">
            <a href="contact.php" class="bg-[#0F0F0F] hover:bg-[#0F0F0F]/90 text-[#F5F5F5] font-bold px-8 py-3 rounded-lg transition-all transform hover:scale-105">
                Contact Us
            </a>
            <a href="tel:+234123456789" class="bg-transparent border-2 border-[#0F0F0F] text-[#0F0F0F] font-bold px-8 py-3 rounded-lg hover:bg-[#0F0F0F] hover:text-[#F5F5F5] transition-all">
                <i class="fas fa-phone-alt mr-2"></i> Call Now
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>