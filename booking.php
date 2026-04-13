<?php
$page_title = 'Book Now';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get booking parameters
$check_in = $_GET['check_in'] ?? date('Y-m-d');
$check_out = $_GET['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
$adults = $_GET['adults'] ?? 1;
$children = $_GET['children'] ?? 0;
$room_id = $_GET['room_id'] ?? null;

// Get available rooms with their primary images
$sql = "SELECT r.*, 
        (SELECT image_path FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image,
        (SELECT COUNT(*) FROM room_bookings rb 
         WHERE rb.room_id = r.id 
         AND rb.booking_status IN ('confirmed', 'checked_in')
         AND (
             (rb.check_in <= ? AND rb.check_out > ?) OR
             (rb.check_in < ? AND rb.check_out >= ?) OR
             (rb.check_in >= ? AND rb.check_out <= ?)
         )) as booking_count
        FROM rooms r
        WHERE r.status = 'available'";

$stmt = $pdo->prepare($sql);
$stmt->execute([$check_in, $check_in, $check_out, $check_out, $check_in, $check_out]);
$rooms = $stmt->fetchAll();

// Filter available rooms (booking_count == 0)
$available_rooms = array_filter($rooms, function($room) {
    return $room['booking_count'] == 0;
});

// Get bank accounts for payment
$bank_accounts = getBankAccounts($pdo);
?>

<!-- Background with animated elements -->
<div class="min-h-screen py-20 relative overflow-hidden">
    <!-- Animated Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-[#0F0F0F] via-[#0F0F0F] to-[#0F0F0F]">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-3xl animate-float"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-3xl animate-float animation-delay-2000"></div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 relative z-10">
        <!-- Page Header -->
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-bold text-[#F5F5F5] mb-4">Book Your Stay</h1>
            <p class="text-xl text-[#F5F5F5]/80">Choose from our available rooms at Fresh Home & Suite Hotel</p>
        </div>
        
        <!-- Search Summary -->
        <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 mb-8 border border-[#C9A45A]/20" data-aos="fade-up">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-[#C9A45A]/20 flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-check text-[#C9A45A]"></i>
                    </div>
                    <div>
                        <label class="block text-[#F5F5F5]/60 text-xs">Check-in</label>
                        <p class="text-[#F5F5F5] font-semibold"><?php echo date('M d, Y', strtotime($check_in)); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-[#C9A45A]/20 flex items-center justify-center mr-3">
                        <i class="fas fa-calendar-times text-[#C9A45A]"></i>
                    </div>
                    <div>
                        <label class="block text-[#F5F5F5]/60 text-xs">Check-out</label>
                        <p class="text-[#F5F5F5] font-semibold"><?php echo date('M d, Y', strtotime($check_out)); ?></p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-[#C9A45A]/20 flex items-center justify-center mr-3">
                        <i class="fas fa-users text-[#C9A45A]"></i>
                    </div>
                    <div>
                        <label class="block text-[#F5F5F5]/60 text-xs">Guests</label>
                        <p class="text-[#F5F5F5] font-semibold"><?php echo $adults + $children; ?> (<?php echo $adults; ?> Adults, <?php echo $children; ?> Children)</p>
                    </div>
                </div>
                <div class="text-right flex items-center justify-end">
                    <a href="index.php" class="text-[#C9A45A] hover:text-[#A8843F] transition group">
                        <i class="fas fa-edit mr-1 group-hover:rotate-12 transition-transform"></i> Modify Search
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Available Rooms -->
        <?php if(count($available_rooms) > 0): ?>
            <div class="space-y-6">
                <?php foreach($available_rooms as $index => $room): 
                    $price_calc = calculateRoomPrice($pdo, $room['id'], $check_in, $check_out, $adults, $children);
                ?>
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl overflow-hidden hover-scale border border-[#C9A45A]/20" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="flex flex-col md:flex-row">
                        <!-- Room Image - FIXED: Now using database images -->
                        <div class="md:w-1/3 h-64 md:h-auto relative group overflow-hidden bg-[#0F0F0F]">
                            <?php if(!empty($room['primary_image'])): ?>
                                <img src="<?php echo SITE_URL . $room['primary_image']; ?>" 
                                     alt="<?php echo $room['room_type']; ?>"
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110"
                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                            <?php else: 
                                // Try to get any image for this room
                                $img_stmt = $pdo->prepare("SELECT image_path FROM room_images WHERE room_id = ? LIMIT 1");
                                $img_stmt->execute([$room['id']]);
                                $any_image = $img_stmt->fetch();
                                
                                if($any_image):
                            ?>
                                <img src="<?php echo SITE_URL . $any_image['image_path']; ?>" 
                                     alt="<?php echo $room['room_type']; ?>"
                                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110"
                                     onerror="this.onerror=null; this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                            <?php else: ?>
                                <div class="w-full h-full bg-gradient-to-br from-[#C9A45A]/20 to-[#A8843F]/20 flex items-center justify-center">
                                    <i class="fas fa-hotel text-5xl text-[#C9A45A]/50"></i>
                                </div>
                            <?php endif; endif; ?>
                            
                            <div class="absolute inset-0 bg-gradient-to-t from-[#0F0F0F] to-transparent opacity-60"></div>
                            
                            <!-- Best Seller Badge (optional) -->
                            <?php if($index == 0): ?>
                            <div class="absolute bottom-4 left-4">
                                <span class="bg-[#C9A45A] text-[#F5F5F5] px-3 py-1 rounded-full text-sm font-semibold">
                                    <i class="fas fa-star mr-1"></i> Best Seller
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Room Details -->
                        <div class="md:w-2/3 p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-2xl font-bold text-[#F5F5F5] mb-2"><?php echo $room['room_type']; ?></h2>
                                    <p class="text-[#F5F5F5]/70"><?php echo substr($room['description'], 0, 150); ?>...</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-[#C9A45A]"><?php echo formatCurrency($price_calc['total']); ?></p>
                                    <p class="text-[#F5F5F5]/60 text-sm">for <?php echo $price_calc['days']; ?> night(s)</p>
                                    <p class="text-[#C9A45A]/80 text-xs">₦<?php echo number_format($room['base_price']); ?>/night</p>
                                </div>
                            </div>
                            
                            <!-- Amenities -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php 
                                $amenities = explode(',', $room['amenities']);
                                $show_amenities = array_slice($amenities, 0, 5);
                                foreach($show_amenities as $amenity): 
                                ?>
                                <span class="px-3 py-1 bg-[#F5F5F5]/10 rounded-full text-[#F5F5F5] text-xs border border-[#C9A45A]/30">
                                    <i class="fas fa-check-circle text-[#C9A45A] mr-1"></i>
                                    <?php echo trim($amenity); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if(count($amenities) > 5): ?>
                                <span class="px-3 py-1 bg-[#F5F5F5]/10 rounded-full text-[#F5F5F5] text-xs border border-[#C9A45A]/30">
                                    +<?php echo count($amenities) - 5; ?> more
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Room Features -->
                            <div class="grid grid-cols-3 gap-4 mb-6">
                                <div class="text-center p-2 bg-[#F5F5F5]/5 rounded-lg">
                                    <i class="fas fa-bed text-xl text-[#C9A45A] mb-1"></i>
                                    <p class="text-[#F5F5F5]/60 text-xs">Bed Type</p>
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
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4">
                                <button onclick="showBookingModal(<?php echo $room['id']; ?>, '<?php echo addslashes($room['room_type']); ?>', <?php echo $price_calc['total']; ?>)" 
                                        class="flex-1 bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-6 py-3 rounded-lg transition transform hover:scale-105 font-semibold group">
                                    Book Now <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                                </button>
                                <a href="room-detail.php?id=<?php echo $room['id']; ?>" 
                                   class="flex-1 bg-[#F5F5F5]/10 hover:bg-[#F5F5F5]/20 text-[#F5F5F5] px-6 py-3 rounded-lg transition text-center border border-[#C9A45A]/20">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Rooms Available -->
            <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-12 text-center border border-[#C9A45A]/20" data-aos="zoom-in">
                <div class="w-24 h-24 bg-[#C9A45A]/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-calendar-times text-4xl text-[#C9A45A]"></i>
                </div>
                <h2 class="text-2xl font-bold text-[#F5F5F5] mb-2">No Rooms Available</h2>
                <p class="text-[#F5F5F5]/70 mb-6 max-w-md mx-auto">Sorry, no rooms are available for the selected dates. Please try different dates or contact us for assistance.</p>
                <a href="index.php" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-6 py-3 rounded-lg transition inline-flex items-center">
                    <i class="fas fa-search mr-2"></i> Search Again
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Booking Modal -->
<div id="bookingModal" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center">
    <div class="bg-[#0F0F0F] rounded-2xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto border border-[#C9A45A]/30" data-aos="zoom-in">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-[#F5F5F5]">Complete Your Booking</h3>
                <button onclick="hideBookingModal()" class="text-[#F5F5F5]/50 hover:text-[#C9A45A] transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <form id="bookingForm" action="process-booking.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="room_id" id="modal_room_id">
                <input type="hidden" name="check_in" value="<?php echo $check_in; ?>">
                <input type="hidden" name="check_out" value="<?php echo $check_out; ?>">
                <input type="hidden" name="adults" value="<?php echo $adults; ?>">
                <input type="hidden" name="children" value="<?php echo $children; ?>">
                <input type="hidden" name="total_amount" id="modal_total_amount">
                
                <!-- Guest Information -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-user text-[#C9A45A] mr-2"></i> Guest Information
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Full Name *</label>
                            <input type="text" name="full_name" required 
                                   class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Email *</label>
                            <input type="email" name="email" required 
                                   class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Phone *</label>
                            <input type="tel" name="phone" required 
                                   class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Address</label>
                            <input type="text" name="address" 
                                   class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-credit-card text-[#C9A45A] mr-2"></i> Payment Method
                    </h4>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 bg-[#F5F5F5]/5 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#F5F5F5]/10 transition">
                            <input type="radio" name="payment_method" value="pay_at_hotel" class="mr-3 accent-[#C9A45A]" checked>
                            <div>
                                <span class="font-semibold text-[#F5F5F5]">Pay at Hotel</span>
                                <p class="text-sm text-[#F5F5F5]/60">Reserve now and pay upon arrival</p>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-4 bg-[#F5F5F5]/5 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#F5F5F5]/10 transition">
                            <input type="radio" name="payment_method" value="bank_transfer" class="mr-3 accent-[#C9A45A]" id="bankTransfer">
                            <div>
                                <span class="font-semibold text-[#F5F5F5]">Bank Transfer</span>
                                <p class="text-sm text-[#F5F5F5]/60">Pay via bank transfer and upload receipt</p>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Bank Transfer Details (hidden by default) -->
                <div id="bankDetails" class="mb-6 hidden">
                    <div class="bg-[#C9A45A]/10 p-4 rounded-lg border border-[#C9A45A]/30">
                        <h5 class="font-semibold text-[#C9A45A] mb-3 flex items-center">
                            <i class="fas fa-university mr-2"></i> Bank Transfer Instructions
                        </h5>
                        <p class="text-[#F5F5F5]/70 mb-3">Please make payment to any of these accounts:</p>
                        
                        <?php if(!empty($bank_accounts)): ?>
                            <?php foreach($bank_accounts as $account): ?>
                            <div class="bg-[#0F0F0F] p-3 rounded mb-2 border border-[#C9A45A]/20">
                                <p class="font-medium text-[#F5F5F5]"><?php echo $account['bank_name']; ?></p>
                                <p class="text-[#F5F5F5]/70 text-sm">Account Name: <?php echo $account['account_name']; ?></p>
                                <p class="text-[#F5F5F5]">Account Number: <span class="font-bold text-[#C9A45A]"><?php echo $account['account_number']; ?></span></p>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="bg-[#0F0F0F] p-3 rounded border border-[#C9A45A]/20">
                                <p class="text-[#F5F5F5]/70">Please contact hotel for bank transfer details.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Upload Payment Receipt *</label>
                            <input type="file" name="receipt" accept="image/*,application/pdf" 
                                   class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-[#C9A45A] file:text-[#F5F5F5] hover:file:bg-[#A8843F]">
                            <p class="text-xs text-[#F5F5F5]/50 mt-1">Max file size: 5MB (JPG, PNG, PDF only)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Special Requests -->
                <div class="mb-6">
                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Special Requests (Optional)</label>
                    <textarea name="special_requests" rows="3" 
                              class="w-full px-4 py-2 bg-[#F5F5F5]/10 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:ring-2 focus:ring-[#C9A45A] focus:outline-none"
                              placeholder="Any special requests? Let us know..."></textarea>
                </div>
                
                <!-- Summary -->
                <div class="bg-[#F5F5F5]/5 p-4 rounded-lg mb-6 border border-[#C9A45A]/20">
                    <h4 class="font-semibold text-[#F5F5F5] mb-3 flex items-center">
                        <i class="fas fa-receipt text-[#C9A45A] mr-2"></i> Booking Summary
                    </h4>
                    <div class="flex justify-between mb-2">
                        <span class="text-[#F5F5F5]/70">Room: <span id="modal_room_name" class="text-[#F5F5F5]"></span></span>
                        <span class="font-bold text-[#C9A45A]" id="modal_total_display"></span>
                    </div>
                    <div class="flex justify-between text-sm text-[#F5F5F5]/60">
                        <span><i class="fas fa-calendar-check mr-1"></i> <?php echo date('M d, Y', strtotime($check_in)); ?></span>
                        <span><i class="fas fa-calendar-times mr-1"></i> <?php echo date('M d, Y', strtotime($check_out)); ?></span>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-3 rounded-lg transition transform hover:scale-105 font-bold">
                    <i class="fas fa-check-circle mr-2"></i> Confirm Booking
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Add this CSS for responsive fixes -->
<style>
@media (max-width: 768px) {
    .flex-col.sm\:flex-row {
        flex-direction: column;
    }
    
    .flex-col.sm\:flex-row > * {
        width: 100%;
    }
    
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    .text-4xl.md\:text-5xl {
        font-size: 2rem;
    }
    
    .p-6 {
        padding: 1rem;
    }
}

input[type="file"] {
    font-size: 14px;
}

input[type="file"]::-webkit-file-upload-button {
    cursor: pointer;
}
</style>

<script>
function showBookingModal(roomId, roomName, totalAmount) {
    document.getElementById('modal_room_id').value = roomId;
    document.getElementById('modal_room_name').textContent = roomName;
    document.getElementById('modal_total_amount').value = totalAmount;
    document.getElementById('modal_total_display').textContent = '₦' + totalAmount.toLocaleString();
    
    document.getElementById('bookingModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function hideBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Toggle bank details
document.getElementById('bankTransfer')?.addEventListener('change', function() {
    document.getElementById('bankDetails').classList.remove('hidden');
});

document.querySelector('input[value="pay_at_hotel"]')?.addEventListener('change', function() {
    document.getElementById('bankDetails').classList.add('hidden');
});

// Close modal when clicking outside
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideBookingModal();
    }
});

// Form validation
document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) return;
    
    const receipt = document.querySelector('input[name="receipt"]');
    
    if (paymentMethod.value === 'bank_transfer' && (!receipt || receipt.files.length === 0)) {
        e.preventDefault();
        alert('Please upload your payment receipt for bank transfer.');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>