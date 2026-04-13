<?php
$page_title = 'Book Event Hall';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get hall details
$stmt = $pdo->query("SELECT * FROM hall WHERE status = 'available' LIMIT 1");
$hall = $stmt->fetch();

if (!$hall) {
    header("Location: hall.php");
    exit();
}

// Get hall packages
$stmt = $pdo->query("SELECT * FROM hall_packages ORDER BY price ASC");
$packages = $stmt->fetchAll();

// Get hall amenities
$stmt = $pdo->query("SELECT * FROM hall_amenities WHERE is_available = 1 ORDER BY name");
$amenities = $stmt->fetchAll();

// Process booking form
$booking_success = false;
$booking_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['submit_booking'])) {
        // Validate form data
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $event_type = trim($_POST['event_type']);
        $guest_count = (int)$_POST['guest_count'];
        $booking_date = $_POST['booking_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $duration_type = $_POST['duration_type']; // hourly, half_day, full_day
        $package_id = !empty($_POST['package_id']) ? (int)$_POST['package_id'] : null;
        $selected_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];
        $special_requests = trim($_POST['special_requests']);
        
        // Calculate price based on duration type
        $base_price = 0;
        switch($duration_type) {
            case 'hourly':
                $base_price = $hall['base_price_hourly'];
                break;
            case 'half_day':
                $base_price = $hall['base_price_half_day'];
                break;
            case 'full_day':
                $base_price = $hall['base_price_full_day'];
                break;
        }
        
        // Calculate package price if selected
        $package_price = 0;
        if ($package_id) {
            $pkg_stmt = $pdo->prepare("SELECT price FROM hall_packages WHERE id = ?");
            $pkg_stmt->execute([$package_id]);
            $package = $pkg_stmt->fetch();
            $package_price = $package ? $package['price'] : 0;
        }
        
        // Calculate amenities price
        $amenities_price = 0;
        if (!empty($selected_amenities)) {
            $placeholders = implode(',', array_fill(0, count($selected_amenities), '?'));
            $amenity_stmt = $pdo->prepare("SELECT SUM(price) as total FROM hall_amenities WHERE id IN ($placeholders)");
            $amenity_stmt->execute($selected_amenities);
            $amenities_price = $amenity_stmt->fetchColumn() ?: 0;
        }
        
        $total_amount = $base_price + $package_price + $amenities_price;
        
        // Generate booking number
        $booking_number = 'HB' . date('Ymd') . rand(1000, 9999);
        
        try {
            $pdo->beginTransaction();
            
            // Check if guest exists or create new
            $guest_stmt = $pdo->prepare("SELECT id FROM guests WHERE email = ?");
            $guest_stmt->execute([$email]);
            $guest = $guest_stmt->fetch();
            
            if ($guest) {
                $guest_id = $guest['id'];
            } else {
                $insert_guest = $pdo->prepare("INSERT INTO guests (full_name, email, phone) VALUES (?, ?, ?)");
                $insert_guest->execute([$full_name, $email, $phone]);
                $guest_id = $pdo->lastInsertId();
            }
            
            // Insert booking
            $booking_stmt = $pdo->prepare("
                INSERT INTO hall_bookings (
                    booking_number, hall_id, guest_id, package_id, 
                    event_type, guest_count, booking_date, start_time, 
                    end_time, duration_type, base_price, package_price, 
                    amenities_price, total_amount, special_requests, 
                    booking_status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    'pending', NOW()
                )
            ");
            
            $booking_stmt->execute([
                $booking_number, $hall['id'], $guest_id, $package_id,
                $event_type, $guest_count, $booking_date, $start_time,
                $end_time, $duration_type, $base_price, $package_price,
                $amenities_price, $total_amount, $special_requests
            ]);
            
            $booking_id = $pdo->lastInsertId();
            
            // Insert selected amenities
            if (!empty($selected_amenities)) {
                $amenity_booking_stmt = $pdo->prepare("
                    INSERT INTO booking_amenities (booking_id, amenity_id, price) 
                    VALUES (?, ?, ?)
                ");
                
                foreach ($selected_amenities as $amenity_id) {
                    $price_stmt = $pdo->prepare("SELECT price FROM hall_amenities WHERE id = ?");
                    $price_stmt->execute([$amenity_id]);
                    $price = $price_stmt->fetchColumn();
                    
                    $amenity_booking_stmt->execute([$booking_id, $amenity_id, $price]);
                }
            }
            
            $pdo->commit();
            $booking_success = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $booking_error = "An error occurred while processing your booking. Please try again.";
            error_log("Hall booking error: " . $e->getMessage());
        }
    }
}
?>

<?php if($booking_success): ?>
<!-- Success Message -->
<section class="py-20 bg-[#0F0F0F] min-h-screen flex items-center">
    <div class="container mx-auto px-4 text-center">
        <div class="max-w-2xl mx-auto bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-8 border border-[#C9A45A]/20" data-aos="fade-up">
            <div class="w-24 h-24 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check-circle text-5xl text-green-500"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-[#F5F5F5] mb-4">Booking Successful!</h1>
            <p class="text-[#F5F5F5]/70 text-lg mb-6">Your hall booking has been received. We'll contact you shortly to confirm your reservation.</p>
            <p class="text-[#C9A45A] text-xl font-bold mb-8">Booking Number: <?php echo $booking_number; ?></p>
            
            <div class="bg-[#F5F5F5]/5 rounded-xl p-6 mb-8 text-left">
                <h3 class="text-[#F5F5F5] font-bold mb-4">Booking Summary:</h3>
                <div class="space-y-2 text-[#F5F5F5]/70">
                    <p><span class="text-[#C9A45A]">Event:</span> <?php echo htmlspecialchars($event_type); ?></p>
                    <p><span class="text-[#C9A45A]">Date:</span> <?php echo date('F j, Y', strtotime($booking_date)); ?></p>
                    <p><span class="text-[#C9A45A]">Time:</span> <?php echo date('h:i A', strtotime($start_time)); ?> - <?php echo date('h:i A', strtotime($end_time)); ?></p>
                    <p><span class="text-[#C9A45A]">Guests:</span> <?php echo $guest_count; ?></p>
                    <p><span class="text-[#C9A45A]">Total Amount:</span> <?php echo formatCurrency($total_amount); ?></p>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="hall.php" class="bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] px-6 py-3 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Hall
                </a>
                <a href="index.php" class="bg-[#F5F5F5]/20 hover:bg-[#F5F5F5]/30 text-[#F5F5F5] px-6 py-3 rounded-lg transition border border-[#C9A45A]/20">
                    Go to Homepage
                </a>
            </div>
        </div>
    </div>
</section>

<?php else: ?>

<!-- Booking Form -->
<section class="py-20 bg-[#0F0F0F] min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="text-center mb-12" data-aos="fade-down">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-[#F5F5F5] mb-4">
                Book <span class="text-[#C9A45A]"><?php echo htmlspecialchars($hall['name']); ?></span>
            </h1>
            <p class="text-lg text-[#F5F5F5]/70 max-w-2xl mx-auto">
                Fill in the details below to reserve the hall for your special event
            </p>
        </div>
        
        <?php if($booking_error): ?>
        <div class="max-w-4xl mx-auto mb-6 bg-red-500/20 border border-red-500 text-red-100 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $booking_error; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Booking Form -->
            <div class="lg:col-span-2" data-aos="fade-right">
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 md:p-8 border border-[#C9A45A]/20">
                    <form method="POST" id="bookingForm" class="space-y-6">
                        <!-- Personal Information -->
                        <div>
                            <h2 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                                <i class="fas fa-user text-[#C9A45A] mr-2"></i>
                                Personal Information
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Full Name *</label>
                                    <input type="text" name="full_name" required
                                           class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none"
                                           placeholder="John Doe">
                                </div>
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Email Address *</label>
                                    <input type="email" name="email" required
                                           class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none"
                                           placeholder="john@example.com">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Phone Number *</label>
                                    <input type="tel" name="phone" required
                                           class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none"
                                           placeholder="+234 800 000 0000">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Event Details -->
                        <div>
                            <h2 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                                <i class="fas fa-calendar-alt text-[#C9A45A] mr-2"></i>
                                Event Details
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Event Type *</label>
                                    <select name="event_type" required
                                            class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                                        <option value="">Select Event Type</option>
                                        <option value="Wedding">Wedding</option>
                                        <option value="Birthday">Birthday Party</option>
                                        <option value="Conference">Conference / Seminar</option>
                                        <option value="Corporate">Corporate Event</option>
                                        <option value="Graduation">Graduation</option>
                                        <option value="Anniversary">Anniversary</option>
                                        <option value="Reunion">Family Reunion</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Number of Guests *</label>
                                    <input type="number" name="guest_count" min="1" max="<?php echo $hall['capacity_theater']; ?>" required
                                           class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none"
                                           placeholder="e.g., 100">
                                </div>
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Booking Date *</label>
                                    <input type="date" name="booking_date" 
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           max="<?php echo date('Y-m-d', strtotime('+6 months')); ?>" required
                                           class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Start Time *</label>
                                    <select name="start_time" id="start_time" required
                                            class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                                        <option value="">Select Time</option>
                                        <option value="08:00:00">8:00 AM</option>
                                        <option value="09:00:00">9:00 AM</option>
                                        <option value="10:00:00">10:00 AM</option>
                                        <option value="11:00:00">11:00 AM</option>
                                        <option value="12:00:00">12:00 PM</option>
                                        <option value="13:00:00">1:00 PM</option>
                                        <option value="14:00:00">2:00 PM</option>
                                        <option value="15:00:00">3:00 PM</option>
                                        <option value="16:00:00">4:00 PM</option>
                                        <option value="17:00:00">5:00 PM</option>
                                        <option value="18:00:00">6:00 PM</option>
                                        <option value="19:00:00">7:00 PM</option>
                                        <option value="20:00:00">8:00 PM</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">End Time *</label>
                                    <select name="end_time" id="end_time" required
                                            class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none">
                                        <option value="">Select Time</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[#F5F5F5]/70 mb-2 text-sm">Duration Type *</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <label class="flex items-center p-3 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition">
                                            <input type="radio" name="duration_type" value="hourly" class="mr-2 accent-[#C9A45A]" onchange="calculatePrice()">
                                            <span class="text-[#F5F5F5]">Hourly</span>
                                        </label>
                                        <label class="flex items-center p-3 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition">
                                            <input type="radio" name="duration_type" value="half_day" class="mr-2 accent-[#C9A45A]" onchange="calculatePrice()">
                                            <span class="text-[#F5F5F5]">Half Day</span>
                                        </label>
                                        <label class="flex items-center p-3 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition">
                                            <input type="radio" name="duration_type" value="full_day" class="mr-2 accent-[#C9A45A]" onchange="calculatePrice()">
                                            <span class="text-[#F5F5F5]">Full Day</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Packages -->
                        <?php if(!empty($packages)): ?>
                        <div>
                            <h2 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                                <i class="fas fa-gift text-[#C9A45A] mr-2"></i>
                                Event Packages (Optional)
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach($packages as $package): ?>
                                <label class="relative p-4 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition group">
                                    <input type="radio" name="package_id" value="<?php echo $package['id']; ?>" 
                                           class="absolute right-4 top-4 accent-[#C9A45A]" onchange="calculatePrice()">
                                    <div class="pr-8">
                                        <h3 class="text-[#F5F5F5] font-bold mb-1"><?php echo htmlspecialchars($package['name']); ?></h3>
                                        <p class="text-[#C9A45A] font-bold mb-2"><?php echo formatCurrency($package['price']); ?></p>
                                        <p class="text-[#F5F5F5]/60 text-sm mb-2"><?php echo htmlspecialchars($package['description']); ?></p>
                                        <?php 
                                        $items = explode(',', $package['amenities_included']);
                                        if(!empty($items)): 
                                        ?>
                                        <div class="text-xs text-[#F5F5F5]/50">
                                            <span class="text-[#C9A45A]">Includes:</span> 
                                            <?php echo implode(', ', array_slice($items, 0, 3)); ?>
                                            <?php if(count($items) > 3): ?> +<?php echo count($items) - 3; ?> more<?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                                <label class="relative p-4 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition">
                                    <input type="radio" name="package_id" value="" checked class="absolute right-4 top-4 accent-[#C9A45A]" onchange="calculatePrice()">
                                    <div class="pr-8">
                                        <h3 class="text-[#F5F5F5] font-bold mb-1">No Package</h3>
                                        <p class="text-[#F5F5F5]/60 text-sm">Book hall only without any package</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Additional Amenities -->
                        <?php if(!empty($amenities)): ?>
                        <div>
                            <h2 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                                <i class="fas fa-couch text-[#C9A45A] mr-2"></i>
                                Additional Amenities (Optional)
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <?php foreach($amenities as $amenity): ?>
                                <label class="flex items-center p-3 bg-black/30 border border-[#C9A45A]/20 rounded-lg cursor-pointer hover:bg-[#C9A45A]/10 transition">
                                    <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" 
                                           class="mr-3 accent-[#C9A45A]" onchange="calculatePrice()">
                                    <div class="flex-1 flex justify-between items-center">
                                        <span class="text-[#F5F5F5]"><?php echo htmlspecialchars($amenity['name']); ?></span>
                                        <span class="text-[#C9A45A] text-sm font-bold"><?php echo formatCurrency($amenity['price']); ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Special Requests -->
                        <div>
                            <h2 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                                <i class="fas fa-comment text-[#C9A45A] mr-2"></i>
                                Special Requests
                            </h2>
                            <textarea name="special_requests" rows="4"
                                      class="w-full px-4 py-3 bg-black/50 border border-[#C9A45A]/20 rounded-lg text-[#F5F5F5] focus:border-[#C9A45A] focus:outline-none"
                                      placeholder="Tell us about any special requirements or requests..."></textarea>
                        </div>
                        
                        <!-- Terms and Submit -->
                        <div>
                            <label class="flex items-center mb-4">
                                <input type="checkbox" name="terms" required class="mr-2 accent-[#C9A45A]">
                                <span class="text-[#F5F5F5]/70 text-sm">
                                    I agree to the <a href="#" class="text-[#C9A45A] hover:underline">terms and conditions</a> and confirm that the information provided is accurate.
                                </span>
                            </label>
                            
                            <button type="submit" name="submit_booking"
                                    class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#F5F5F5] py-4 rounded-xl text-lg font-bold transition transform hover:scale-105">
                                <i class="fas fa-calendar-check mr-2"></i> Submit Booking Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Price Summary Sidebar -->
            <div class="lg:col-span-1" data-aos="fade-left">
                <div class="bg-[#F5F5F5]/10 backdrop-blur-lg rounded-2xl p-6 sticky top-24 border border-[#C9A45A]/20">
                    <h3 class="text-xl font-bold text-[#F5F5F5] mb-4 flex items-center">
                        <i class="fas fa-calculator text-[#C9A45A] mr-2"></i>
                        Price Summary
                    </h3>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-[#F5F5F5]/70">Hall Rental:</span>
                            <span class="text-[#F5F5F5]" id="hallPrice"><?php echo formatCurrency(0); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#F5F5F5]/70">Package:</span>
                            <span class="text-[#F5F5F5]" id="packagePrice"><?php echo formatCurrency(0); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-[#F5F5F5]/70">Amenities:</span>
                            <span class="text-[#F5F5F5]" id="amenitiesPrice"><?php echo formatCurrency(0); ?></span>
                        </div>
                        <div class="border-t border-[#C9A45A]/20 my-3 pt-3">
                            <div class="flex justify-between font-bold">
                                <span class="text-[#F5F5F5]">Total:</span>
                                <span class="text-[#C9A45A] text-xl" id="totalPrice"><?php echo formatCurrency(0); ?></span>
                            </div>
                            <p class="text-[#F5F5F5]/50 text-xs mt-2">* Final price may be adjusted based on actual duration</p>
                        </div>
                    </div>
                    
                    <!-- Hall Info -->
                    <div class="bg-[#F5F5F5]/5 rounded-lg p-4 mb-4">
                        <h4 class="text-[#F5F5F5] font-bold mb-2 text-sm">Hall Capacity</h4>
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div>
                                <p class="text-[#F5F5F5]/60">Theater</p>
                                <p class="text-[#F5F5F5] font-bold"><?php echo number_format($hall['capacity_theater']); ?></p>
                            </div>
                            <div>
                                <p class="text-[#F5F5F5]/60">Banquet</p>
                                <p class="text-[#F5F5F5] font-bold"><?php echo number_format($hall['capacity_banquet']); ?></p>
                            </div>
                            <div>
                                <p class="text-[#F5F5F5]/60">Classroom</p>
                                <p class="text-[#F5F5F5] font-bold"><?php echo number_format($hall['capacity_classroom']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Info -->
                    <div class="bg-[#F5F5F5]/5 rounded-lg p-4">
                        <h4 class="text-[#F5F5F5] font-bold mb-2 text-sm">Pricing</h4>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between">
                                <span class="text-[#F5F5F5]/60">Hourly:</span>
                                <span class="text-[#F5F5F5]"><?php echo formatCurrency($hall['base_price_hourly']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#F5F5F5]/60">Half Day (4-6 hrs):</span>
                                <span class="text-[#F5F5F5]"><?php echo formatCurrency($hall['base_price_half_day']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#F5F5F5]/60">Full Day (8-12 hrs):</span>
                                <span class="text-[#F5F5F5]"><?php echo formatCurrency($hall['base_price_full_day']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="mt-4 text-center">
                        <p class="text-[#F5F5F5]/50 text-xs">
                            Need help? <a href="contact.php" class="text-[#C9A45A] hover:underline">Contact us</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Price calculation
const hallPrices = {
    hourly: <?php echo $hall['base_price_hourly']; ?>,
    half_day: <?php echo $hall['base_price_half_day']; ?>,
    full_day: <?php echo $hall['base_price_full_day']; ?>
};

const packages = <?php echo json_encode(array_column($packages, 'price', 'id')); ?>;
const amenities = <?php echo json_encode(array_column($amenities, 'price', 'id')); ?>;

function calculatePrice() {
    // Get hall price based on duration type
    const durationType = document.querySelector('input[name="duration_type"]:checked');
    let hallPrice = 0;
    if (durationType) {
        hallPrice = hallPrices[durationType.value] || 0;
    }
    document.getElementById('hallPrice').textContent = '₦' + hallPrice.toLocaleString();
    
    // Get package price
    const packageRadio = document.querySelector('input[name="package_id"]:checked');
    let packagePrice = 0;
    if (packageRadio && packageRadio.value) {
        packagePrice = packages[packageRadio.value] || 0;
    }
    document.getElementById('packagePrice').textContent = '₦' + packagePrice.toLocaleString();
    
    // Get amenities price
    const selectedAmenities = document.querySelectorAll('input[name="amenities[]"]:checked');
    let amenitiesTotal = 0;
    selectedAmenities.forEach(cb => {
        amenitiesTotal += amenities[cb.value] || 0;
    });
    document.getElementById('amenitiesPrice').textContent = '₦' + amenitiesTotal.toLocaleString();
    
    // Calculate total
    const total = hallPrice + packagePrice + amenitiesTotal;
    document.getElementById('totalPrice').textContent = '₦' + total.toLocaleString();
}

// Update end time based on start time
document.getElementById('start_time').addEventListener('change', function() {
    const startTime = this.value;
    const endTimeSelect = document.getElementById('end_time');
    endTimeSelect.innerHTML = '<option value="">Select Time</option>';
    
    if (startTime) {
        const times = [
            { value: '08:00:00', label: '8:00 AM' },
            { value: '09:00:00', label: '9:00 AM' },
            { value: '10:00:00', label: '10:00 AM' },
            { value: '11:00:00', label: '11:00 AM' },
            { value: '12:00:00', label: '12:00 PM' },
            { value: '13:00:00', label: '1:00 PM' },
            { value: '14:00:00', label: '2:00 PM' },
            { value: '15:00:00', label: '3:00 PM' },
            { value: '16:00:00', label: '4:00 PM' },
            { value: '17:00:00', label: '5:00 PM' },
            { value: '18:00:00', label: '6:00 PM' },
            { value: '19:00:00', label: '7:00 PM' },
            { value: '20:00:00', label: '8:00 PM' },
            { value: '21:00:00', label: '9:00 PM' },
            { value: '22:00:00', label: '10:00 PM' }
        ];
        
        const startIndex = times.findIndex(t => t.value === startTime);
        
        for (let i = startIndex + 1; i < times.length; i++) {
            const option = document.createElement('option');
            option.value = times[i].value;
            option.textContent = times[i].label;
            endTimeSelect.appendChild(option);
        }
    }
});

// Initialize price calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculatePrice();
    
    // Add event listeners for all form elements that affect price
    document.querySelectorAll('input[name="duration_type"], input[name="package_id"], input[name="amenities[]"]').forEach(el => {
        el.addEventListener('change', calculatePrice);
    });
});
</script>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>