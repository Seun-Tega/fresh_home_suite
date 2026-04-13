<?php
$page_title = 'Room Details';
require_once 'config/config.php';
require_once 'includes/header.php';

$room_id = $_GET['id'] ?? 0;

// Get room details
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    redirect('rooms.php');
}

// Get room images
$stmt = $pdo->prepare("SELECT * FROM room_images WHERE room_id = ? ORDER BY is_primary DESC, id ASC");
$stmt->execute([$room_id]);
$images = $stmt->fetchAll();
?>

<!-- Room Detail Section -->
<section class="py-20 bg-[#0F0F0F] min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Back Button -->
        <a href="rooms.php" class="inline-flex items-center text-[#F5F5F5]/70 hover:text-[#C9A45A] mb-8 transition group">
            <i class="fas fa-arrow-left mr-2 group-hover:-translate-x-1 transition-transform"></i> Back to Rooms
        </a>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content - Left Column -->
            <div class="lg:col-span-2">
                <!-- Image Gallery -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-[#C9A45A]/20" data-aos="fade-up">
                    <div class="relative h-96 mb-4 rounded-xl overflow-hidden group">
                        <?php 
                        // Determine main image - use primary image first, then first image, then placeholder
                        $main_image = '';
                        if (!empty($images)) {
                            // Find primary image
                            $primary_image = array_filter($images, function($img) {
                                return $img['is_primary'] == 1;
                            });
                            
                            if (!empty($primary_image)) {
                                $main_image = reset($primary_image)['image_path'];
                            } else {
                                $main_image = $images[0]['image_path'];
                            }
                        }
                        ?>
                        
                        <img id="mainImage" 
                             src="<?php echo $main_image ? SITE_URL . $main_image : SITE_URL . 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo $room['room_type']; ?>"
                             class="w-full h-full object-cover transition duration-500 group-hover:scale-105"
                             onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                        
                        <!-- Image Navigation Overlay (only show if multiple images) -->
                        <?php if(count($images) > 1): ?>
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-between px-4">
                            <button onclick="changeImage(-1)" class="w-10 h-10 rounded-full bg-[#C9A45A] flex items-center justify-center hover:bg-[#A8843F] transition">
                                <i class="fas fa-chevron-left text-white"></i>
                            </button>
                            <button onclick="changeImage(1)" class="w-10 h-10 rounded-full bg-[#C9A45A] flex items-center justify-center hover:bg-[#A8843F] transition">
                                <i class="fas fa-chevron-right text-white"></i>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Image Counter -->
                        <?php if(count($images) > 0): ?>
                        <div class="absolute bottom-4 right-4 bg-black/70 text-[#F5F5F5] px-3 py-1 rounded-full text-sm">
                            <span id="currentImageIndex">1</span> / <?php echo count($images); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(count($images) > 0): ?>
                    <div class="grid grid-cols-4 gap-4">
                        <?php foreach($images as $index => $image): ?>
                        <div class="relative group cursor-pointer" onclick="setMainImage('<?php echo SITE_URL . $image['image_path']; ?>', <?php echo $index + 1; ?>)">
                            <img src="<?php echo SITE_URL . $image['image_path']; ?>" 
                                 alt="Room Image <?php echo $index + 1; ?>"
                                 class="h-24 w-full object-cover rounded-lg transition duration-300 group-hover:opacity-80 group-hover:scale-105 border-2 <?php echo ($image['is_primary'] == 1) ? 'border-[#C9A45A]' : 'border-transparent group-hover:border-[#C9A45A]'; ?>">
                            <?php if($image['is_primary'] == 1): ?>
                            <div class="absolute top-1 left-1 bg-[#C9A45A] text-[#0F0F0F] text-xs px-2 py-0.5 rounded-full font-bold">
                                Primary
                            </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition rounded-lg"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 bg-[#F5F5F5]/5 rounded-lg">
                        <i class="fas fa-images text-4xl text-[#C9A45A]/50 mb-2"></i>
                        <p class="text-[#F5F5F5]/50">No additional images available</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Room Description -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-[#C9A45A]/20" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-info-circle text-[#C9A45A] mr-2"></i>
                        About This Room
                    </h2>
                    <p class="text-[#F5F5F5]/70 leading-relaxed"><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
                </div>
                
                <!-- Amenities -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-[#C9A45A]/20" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-concierge-bell text-[#C9A45A] mr-2"></i>
                        Amenities
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php 
                        $amenities = explode(',', $room['amenities']);
                        foreach($amenities as $amenity): 
                        ?>
                        <div class="flex items-center text-[#F5F5F5]/70 bg-[#F5F5F5]/5 p-2 rounded-lg hover:bg-[#C9A45A]/10 transition group">
                            <i class="fas fa-check-circle text-[#C9A45A] mr-2 group-hover:scale-110 transition"></i>
                            <span><?php echo trim($amenity); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Room Policies -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 border border-[#C9A45A]/20" data-aos="fade-up">
                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-gavel text-[#C9A45A] mr-2"></i>
                        Room Policies
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <i class="fas fa-clock text-[#C9A45A] mt-1 mr-3"></i>
                            <div>
                                <p class="text-[#F5F5F5] font-medium">Check-in / Check-out</p>
                                <p class="text-[#F5F5F5]/70">Check-in: 2:00 PM - Check-out: 12:00 PM</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-smoking-ban text-[#C9A45A] mt-1 mr-3"></i>
                            <div>
                                <p class="text-[#F5F5F5] font-medium">Smoking Policy</p>
                                <p class="text-[#F5F5F5]/70">This is a non-smoking room</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-paw text-[#C9A45A] mt-1 mr-3"></i>
                            <div>
                                <p class="text-[#F5F5F5] font-medium">Pets</p>
                                <p class="text-[#F5F5F5]/70">Pets are not allowed in this room</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <i class="fas fa-wifi text-[#C9A45A] mt-1 mr-3"></i>
                            <div>
                                <p class="text-[#F5F5F5] font-medium">Wi-Fi</p>
                                <p class="text-[#F5F5F5]/70">Free high-speed Wi-Fi available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar - Right Column -->
            <div class="lg:col-span-1">
                <!-- Booking Widget -->
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 sticky top-24 border border-[#C9A45A]/20 shadow-xl" data-aos="fade-left">
                    <div class="absolute -top-3 left-6">
                        <span class="bg-[#C9A45A] text-[#F5F5F5] px-4 py-1 rounded-full text-sm font-bold">Best Rate Guarantee</span>
                    </div>
                    
                    <h3 class="text-2xl font-bold text-[#F5F5F5] mb-4 mt-2">Book This Room</h3>
                    
                    <div class="mb-6 p-4 bg-gradient-to-r from-[#C9A45A]/20 to-transparent rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-[#F5F5F5]/70">Price per night:</span>
                            <div class="text-right">
                                <span class="text-3xl font-bold text-[#C9A45A]"><?php echo formatCurrency($room['base_price']); ?></span>
                                <span class="text-[#F5F5F5]/50 text-sm block">plus taxes</span>
                            </div>
                        </div>
                    </div>
                    
                    <form action="booking.php" method="GET" class="space-y-4">
                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">
                                <i class="fas fa-calendar-check text-[#C9A45A] mr-1"></i> Check-in Date
                            </label>
                            <input type="date" name="check_in" id="check_in" min="<?php echo date('Y-m-d'); ?>" 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">
                                <i class="fas fa-calendar-times text-[#C9A45A] mr-1"></i> Check-out Date
                            </label>
                            <input type="date" name="check_out" id="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                   value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2 text-sm">
                                    <i class="fas fa-user text-[#C9A45A] mr-1"></i> Adults
                                </label>
                                <select name="adults" id="adults" class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                                    <?php for($i = 1; $i <= $room['max_occupancy']; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[#F5F5F5]/70 mb-2 text-sm">
                                    <i class="fas fa-child text-[#C9A45A] mr-1"></i> Children
                                </label>
                                <select name="children" id="children" class="w-full px-4 py-3 rounded-lg text-[#0F0F0F] bg-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                                    <option value="0">0</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Price Summary (Dynamic with JS) -->
                        <div class="bg-[#F5F5F5]/5 p-3 rounded-lg">
                            <div class="flex justify-between text-sm">
                                <span class="text-[#F5F5F5]/70">Subtotal:</span>
                                <span class="text-[#F5F5F5]" id="subtotal"><?php echo formatCurrency($room['base_price']); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-[#F5F5F5]/70">Taxes & fees:</span>
                                <span class="text-[#F5F5F5]" id="taxes"><?php echo formatCurrency($room['base_price'] * 0.1); ?></span>
                            </div>
                            <div class="flex justify-between font-bold mt-2 pt-2 border-t border-[#C9A45A]/20">
                                <span class="text-[#F5F5F5]">Total:</span>
                                <span class="text-[#C9A45A]" id="total"><?php echo formatCurrency($room['base_price'] * 1.1); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-3 rounded-lg transition transform hover:scale-105 font-bold shadow-lg">
                            <i class="fas fa-calendar-check mr-2"></i> Check Availability
                        </button>
                    </form>
                    
                    <!-- Room Features Summary -->
                    <div class="mt-6 pt-6 border-t border-[#C9A45A]/20">
                        <h4 class="text-[#F5F5F5] font-bold mb-3">Room Features</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="text-center p-2 bg-[#F5F5F5]/5 rounded-lg">
                                <i class="fas fa-bed text-xl text-[#C9A45A] mb-1"></i>
                                <p class="text-[#F5F5F5]/60 text-xs">Bed</p>
                                <p class="text-[#F5F5F5] font-semibold text-sm"><?php echo $room['bed_type']; ?></p>
                            </div>
                            <div class="text-center p-2 bg-[#F5F5F5]/5 rounded-lg">
                                <i class="fas fa-arrows-alt text-xl text-[#C9A45A] mb-1"></i>
                                <p class="text-[#F5F5F5]/60 text-xs">Size</p>
                                <p class="text-[#F5F5F5] font-semibold text-sm"><?php echo $room['square_feet']; ?> sq ft</p>
                            </div>
                            <div class="text-center p-2 bg-[#F5F5F5]/5 rounded-lg">
                                <i class="fas fa-users text-xl text-[#C9A45A] mb-1"></i>
                                <p class="text-[#F5F5F5]/60 text-xs">Max</p>
                                <p class="text-[#F5F5F5] font-semibold text-sm"><?php echo $room['max_occupancy']; ?> Guests</p>
                            </div>
                            <div class="text-center p-2 bg-[#F5F5F5]/5 rounded-lg">
                                <i class="fas fa-door-open text-xl text-[#C9A45A] mb-1"></i>
                                <p class="text-[#F5F5F5]/60 text-xs">Room</p>
                                <p class="text-[#F5F5F5] font-semibold text-sm"><?php echo $room['room_number']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Trust Badges -->
                    <div class="mt-4 flex justify-center gap-4 text-[#F5F5F5]/50">
                        <i class="fas fa-shield-alt" title="Secure Booking"></i>
                        <i class="fas fa-lock" title="Encrypted Payment"></i>
                        <i class="fas fa-credit-card" title="Multiple Payment Options"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Image navigation
let currentImageIndex = 0;
const images = <?php echo json_encode(array_column($images, 'image_path')); ?>;
const siteUrl = '<?php echo SITE_URL; ?>';

function changeImage(direction) {
    if (images.length === 0) return;
    
    currentImageIndex = (currentImageIndex + direction + images.length) % images.length;
    document.getElementById('mainImage').src = siteUrl + images[currentImageIndex];
    document.getElementById('currentImageIndex').textContent = currentImageIndex + 1;
}

function setMainImage(imageSrc, index) {
    document.getElementById('mainImage').src = imageSrc;
    currentImageIndex = index - 1;
    document.getElementById('currentImageIndex').textContent = index;
}

// Date validation and price calculation
document.addEventListener('DOMContentLoaded', function() {
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    const adults = document.getElementById('adults');
    const children = document.getElementById('children');
    
    function calculatePrice() {
        const checkInDate = new Date(checkIn.value);
        const checkOutDate = new Date(checkOut.value);
        const basePrice = <?php echo $room['base_price']; ?>;
        
        if (checkInDate && checkOutDate && checkOutDate > checkInDate) {
            const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
            const subtotal = basePrice * nights;
            const taxes = subtotal * 0.1;
            const total = subtotal + taxes;
            
            document.getElementById('subtotal').textContent = '₦' + subtotal.toLocaleString();
            document.getElementById('taxes').textContent = '₦' + taxes.toLocaleString();
            document.getElementById('total').textContent = '₦' + total.toLocaleString();
        }
    }
    
    checkIn.addEventListener('change', function() {
        const checkOutMin = new Date(this.value);
        checkOutMin.setDate(checkOutMin.getDate() + 1);
        checkOut.min = checkOutMin.toISOString().split('T')[0];
        
        // If current checkout is before new min, update it
        if (new Date(checkOut.value) <= new Date(this.value)) {
            checkOut.value = checkOutMin.toISOString().split('T')[0];
        }
        
        calculatePrice();
    });
    
    checkOut.addEventListener('change', calculatePrice);
    adults.addEventListener('change', calculatePrice);
    children.addEventListener('change', calculatePrice);
});
</script>

<?php require_once 'includes/footer.php'; ?>