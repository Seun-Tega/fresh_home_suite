<?php
$page_title = 'Our Restaurant';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get food categories
$stmt = $pdo->query("SELECT * FROM food_categories ORDER BY display_order");
$categories = $stmt->fetchAll();

// Get all food items
$stmt = $pdo->query("
    SELECT fi.*, fc.name as category_name 
    FROM food_items fi 
    JOIN food_categories fc ON fi.category_id = fc.id 
    WHERE fi.is_available = 1 
    ORDER BY fc.display_order, fi.display_order
");
$food_items = $stmt->fetchAll();

// Group items by category
$grouped_items = [];
foreach($food_items as $item) {
    $grouped_items[$item['category_name']][] = $item;
}
?>

<!-- Hero Section -->
<section class="relative py-32 overflow-hidden">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
             alt="Restaurant" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-[#0F0F0F]/90 to-[#0F0F0F]/90"></div>
    </div>
    
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 opacity-30">
        <div class="absolute top-0 left-0 w-96 h-96 bg-[#C9A45A] rounded-full mix-blend-multiply filter blur-xl animate-float"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#A8843F] rounded-full mix-blend-multiply filter blur-xl animate-float animation-delay-2000"></div>
    </div>
    
    <div class="relative z-10 text-center text-[#F5F5F5] px-4">
        <h1 class="text-5xl md:text-7xl font-bold mb-4 animate__animated animate__fadeInDown">
            Our <span class="text-[#C9A45A]">Restaurant</span>
        </h1>
        <p class="text-xl md:text-2xl mb-8 animate__animated animate__fadeInUp animate__delay-1s">
            Culinary delights crafted with passion at Fresh Home & Suite Hotel
        </p>
    </div>
</section>

<!-- Menu Section -->
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <!-- Category Tabs -->
        <div class="flex flex-wrap justify-center gap-4 mb-12" data-aos="fade-up">
            <button onclick="filterMenu('all')" 
                    class="category-btn active px-6 py-3 bg-[#C9A45A] text-[#0F0F0F] rounded-full hover:bg-[#A8843F] transition font-medium">
                All Items
            </button>
            <?php foreach($categories as $category): ?>
            <button onclick="filterMenu('<?php echo $category['name']; ?>')" 
                    class="category-btn px-6 py-3 bg-[#F5F5F5]/10 text-[#F5F5F5] rounded-full hover:bg-[#F5F5F5]/20 transition border border-[#C9A45A]/20">
                <?php echo $category['name']; ?>
            </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Menu Items Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($food_items as $index => $item): 
                // Fix image path for display
                $image_src = SITE_URL . 'assets/images/no-image.jpg'; // Default
                if (!empty($item['image_path'])) {
                    // Clean the path - remove any extra characters
                    $clean_path = ltrim($item['image_path'], './');
                    $clean_path = str_replace(['../', './', '\\'], '', $clean_path);
                    // Ensure it starts with uploads/food/
                    if (strpos($clean_path, 'uploads/food/') !== 0) {
                        $clean_path = 'uploads/food/' . basename($clean_path);
                    }
                    $image_src = SITE_URL . $clean_path;
                }
            ?>
            <div class="menu-item bg-[#F5F5F5]/5 backdrop-blur-lg rounded-2xl overflow-hidden hover-scale group border border-[#C9A45A]/20" 
                 data-category="<?php echo $item['category_name']; ?>"
                 data-aos="fade-up" data-aos-delay="<?php echo $index * 50; ?>">
                
                <div class="relative h-48 overflow-hidden bg-[#0F0F0F]">
                    <img src="<?php echo $image_src; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-700"
                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg'; this.onerror=null;">
                    
                    <!-- Dietary Badge -->
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold
                        <?php 
                        switch($item['dietary_type']) {
                            case 'veg':
                                echo 'bg-green-500';
                                break;
                            case 'non_veg':
                                echo 'bg-red-500';
                                break;
                            case 'spicy':
                                echo 'bg-orange-500';
                                break;
                            case 'chef_special':
                                echo 'bg-[#C9A45A]';
                                break;
                            default:
                                echo 'bg-[#C9A45A]';
                        }
                        ?> text-white">
                        <?php 
                        echo $item['dietary_type'] == 'veg' ? 'VEG' : 
                             ($item['dietary_type'] == 'non_veg' ? 'NON-VEG' : 
                             ($item['dietary_type'] == 'spicy' ? '🌶️ SPICY' : 
                             ($item['dietary_type'] == 'chef_special' ? '👨‍🍳 SPECIAL' : 'NEW')));
                        ?>
                    </span>
                </div>
                
                <div class="p-6">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-[#F5F5F5]"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <span class="text-2xl font-bold text-[#C9A45A]"><?php echo formatCurrency($item['price']); ?></span>
                    </div>
                    
                    <p class="text-[#F5F5F5]/70 mb-4"><?php echo htmlspecialchars($item['description']); ?></p>
                    
                    <!-- Dietary Indicators -->
                    <div class="flex flex-wrap gap-2">
                        <?php if($item['dietary_type'] == 'veg'): ?>
                        <span class="px-2 py-1 bg-green-500/20 text-green-300 rounded text-xs border border-green-500/30">🌱 Pure Veg</span>
                        <?php elseif($item['dietary_type'] == 'non_veg'): ?>
                        <span class="px-2 py-1 bg-red-500/20 text-red-300 rounded text-xs border border-red-500/30">🍗 Non-Veg</span>
                        <?php endif; ?>
                        
                        <?php if($item['dietary_type'] == 'spicy'): ?>
                        <span class="px-2 py-1 bg-orange-500/20 text-orange-300 rounded text-xs border border-orange-500/30">🌶️ Spicy</span>
                        <?php endif; ?>
                        
                        <?php if($item['dietary_type'] == 'chef_special'): ?>
                        <span class="px-2 py-1 bg-[#C9A45A]/20 text-[#C9A45A] rounded text-xs border border-[#C9A45A]/30">⭐ Chef's Special</span>
                        <?php endif; ?>
                        
                        <?php if(!$item['is_available']): ?>
                        <span class="px-2 py-1 bg-red-500/20 text-red-300 rounded text-xs border border-red-500/30">❌ Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Order Button -->
                    <div class="mt-4">
                        <button onclick="addToOrder(<?php echo $item['id']; ?>)" 
                                class="w-full bg-[#C9A45A] hover:bg-[#A8843F] text-[#0F0F0F] py-2 rounded-lg transition transform hover:scale-105 font-medium"
                                <?php echo !$item['is_available'] ? 'disabled' : ''; ?>>
                            <i class="fas fa-plus-circle mr-2"></i> Add to Order
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Restaurant Features -->
<section class="py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] relative overflow-hidden border-t border-[#C9A45A]/20">
    <div class="absolute inset-0 bg-[#C9A45A]/5"></div>
    <div class="container mx-auto px-4 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center" data-aos="fade-up">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-clock text-3xl text-[#0F0F0F]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Opening Hours</h3>
                <p class="text-[#F5F5F5]/70">Breakfast: 7am - 11am</p>
                <p class="text-[#F5F5F5]/70">Lunch: 12pm - 4pm</p>
                <p class="text-[#F5F5F5]/70">Dinner: 6pm - 11pm</p>
            </div>
            
            <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-truck text-3xl text-[#0F0F0F]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Room Service</h3>
                <p class="text-[#F5F5F5]/70">24/7 room dining available</p>
                <p class="text-[#F5F5F5]/70">Call ext. 123 to order</p>
            </div>
            
            <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                <div class="inline-block p-4 bg-[#C9A45A] rounded-full mb-4">
                    <i class="fas fa-calendar-alt text-3xl text-[#0F0F0F]"></i>
                </div>
                <h3 class="text-xl font-bold text-[#F5F5F5] mb-2">Private Dining</h3>
                <p class="text-[#F5F5F5]/70">Book our private dining room</p>
                <p class="text-[#F5F5F5]/70">For special occasions</p>
            </div>
        </div>
    </div>
</section>

<!-- Chef's Special Section -->
<?php 
$special_items = array_filter($food_items, function($item) {
    return $item['dietary_type'] == 'chef_special';
});
if (!empty($special_items)): 
?>
<section class="py-20 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <h2 class="text-4xl font-bold text-center text-[#F5F5F5] mb-12" data-aos="fade-up">Chef's Special Recommendations</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php 
            $special_items = array_slice($special_items, 0, 4);
            foreach($special_items as $index => $item): 
                // Fix image path for special items
                $special_image = SITE_URL . 'assets/images/no-image.jpg';
                if (!empty($item['image_path'])) {
                    $clean_path = ltrim($item['image_path'], './');
                    $clean_path = str_replace(['../', './', '\\'], '', $clean_path);
                    if (strpos($clean_path, 'uploads/food/') !== 0) {
                        $clean_path = 'uploads/food/' . basename($clean_path);
                    }
                    $special_image = SITE_URL . $clean_path;
                }
            ?>
            <div class="flex gap-4 bg-[#F5F5F5]/5 backdrop-blur-lg rounded-xl p-4 border border-[#C9A45A]/20 hover-scale" data-aos="fade-right" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0 bg-[#0F0F0F]">
                    <img src="<?php echo $special_image; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                         class="w-full h-full object-cover"
                         onerror="this.src='<?php echo SITE_URL; ?>assets/images/no-image.jpg';">
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h4 class="text-lg font-bold text-[#F5F5F5]"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <span class="text-[#C9A45A] font-bold"><?php echo formatCurrency($item['price']); ?></span>
                    </div>
                    <p class="text-[#F5F5F5]/70 text-sm mb-2"><?php echo htmlspecialchars($item['description']); ?></p>
                    <span class="inline-block px-2 py-1 bg-[#C9A45A]/20 text-[#C9A45A] rounded text-xs border border-[#C9A45A]/30">👨‍🍳 Chef's Special</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Filter Script -->
<script>
function filterMenu(category) {
    const items = document.querySelectorAll('.menu-item');
    const buttons = document.querySelectorAll('.category-btn');
    
    buttons.forEach(btn => {
        if (btn.textContent.trim() === category || (category === 'all' && btn.textContent.trim() === 'All Items')) {
            btn.classList.add('bg-[#C9A45A]', 'text-[#0F0F0F]');
            btn.classList.remove('bg-[#F5F5F5]/10', 'text-[#F5F5F5]');
        } else {
            btn.classList.remove('bg-[#C9A45A]', 'text-[#0F0F0F]');
            btn.classList.add('bg-[#F5F5F5]/10', 'text-[#F5F5F5]');
        }
    });
    
    items.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
            item.style.animation = 'fadeIn 0.5s ease-in-out';
        } else {
            item.style.display = 'none';
        }
    });
}

function addToOrder(itemId) {
    // You can implement cart functionality here
    alert('Item added to your order! You can proceed to checkout.');
}
</script>

<!-- Add animation keyframes -->
<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.hover-scale {
    transition: transform 0.3s ease;
}
.hover-scale:hover {
    transform: scale(1.02);
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}
.animate-float {
    animation: float 6s ease-in-out infinite;
}
.animation-delay-2000 {
    animation-delay: 2s;
}
</style>

<?php require_once 'includes/footer.php'; ?>