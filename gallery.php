<?php
$page_title = 'Gallery';
require_once 'config/config.php';
require_once 'includes/header.php';

// Get all gallery images from different categories
$stmt = $pdo->query("
    SELECT 'room' as category, r.room_type as title, ri.image_path, ri.is_primary 
    FROM room_images ri
    JOIN rooms r ON ri.room_id = r.id
    UNION ALL
    SELECT 'hall' as category, 'Event Hall' as title, hi.image_path, hi.is_primary 
    FROM hall_images hi
    UNION ALL
    SELECT 'food' as category, fi.name as title, fi.image_path, 0 as is_primary 
    FROM food_items fi
    WHERE fi.image_path IS NOT NULL AND fi.image_path != ''
    UNION ALL
    SELECT 'boardroom' as category, br.name as title, bri.image_path, bri.is_primary 
    FROM boardroom_images bri
    JOIN boardrooms br ON bri.boardroom_id = br.id
    ORDER BY category
");
$gallery_images = $stmt->fetchAll();

// Get images by category
$room_images = array_filter($gallery_images, function($img) { return $img['category'] == 'room'; });
$hall_images = array_filter($gallery_images, function($img) { return $img['category'] == 'hall'; });
$food_images = array_filter($gallery_images, function($img) { return $img['category'] == 'food'; });
$boardroom_images = array_filter($gallery_images, function($img) { return $img['category'] == 'boardroom'; });
?>

<style>
/* Gallery Styles */
.gallery-item {
    position: relative;
    overflow: hidden;
    border-radius: 1rem;
    cursor: pointer;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.gallery-item:hover img {
    transform: scale(1.1);
}

.gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: flex-end;
    padding: 1.5rem;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

/* Lightbox */
.lightbox {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.95);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.lightbox.active {
    display: flex;
}

.lightbox-content {
    max-width: 90%;
    max-height: 90%;
}

.lightbox-content img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 20px;
    color: #C9A45A;
    font-size: 2rem;
    cursor: pointer;
    z-index: 10000;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: #C9A45A;
    font-size: 2rem;
    cursor: pointer;
    padding: 1rem;
    z-index: 10000;
}

.lightbox-prev {
    left: 20px;
}

.lightbox-next {
    right: 20px;
}

/* Filter Buttons */
.filter-btn.active {
    background: #C9A45A;
    color: #0F0F0F;
}

/* Masonry Grid */
.masonry-grid {
    column-count: 4;
    column-gap: 1rem;
}

.masonry-item {
    break-inside: avoid;
    margin-bottom: 1rem;
}

@media (max-width: 1024px) {
    .masonry-grid {
        column-count: 3;
    }
}

@media (max-width: 768px) {
    .masonry-grid {
        column-count: 2;
    }
}

@media (max-width: 640px) {
    .masonry-grid {
        column-count: 1;
    }
}
</style>

<!-- Page Header -->
<section class="relative py-20 bg-gradient-to-r from-[#0F0F0F] to-[#0F0F0F] overflow-hidden">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-64 h-64 bg-[#C9A45A] rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-[#C9A45A] rounded-full filter blur-3xl"></div>
    </div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-[#F5F5F5] mb-4" data-aos="fade-up">Our Gallery</h1>
        <p class="text-lg sm:text-xl text-[#F5F5F5]/80 max-w-3xl mx-auto" data-aos="fade-up" data-aos-delay="100">
            Explore the beauty and elegance of Fresh Home & Suite Hotel
        </p>
    </div>
</section>

<!-- Gallery Section -->
<section class="py-12 sm:py-16 bg-[#0F0F0F]">
    <div class="container mx-auto px-4">
        <!-- Filter Buttons -->
        <div class="flex flex-wrap justify-center gap-3 mb-8 sm:mb-12" data-aos="fade-up">
            <button class="filter-btn active px-4 sm:px-6 py-2 rounded-full text-sm sm:text-base border border-[#C9A45A]/30 hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition-all duration-300" data-filter="all">
                All
            </button>
            <button class="filter-btn px-4 sm:px-6 py-2 rounded-full text-sm sm:text-base border border-[#C9A45A]/30 hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition-all duration-300" data-filter="rooms">
                Rooms
            </button>
            <button class="filter-btn px-4 sm:px-6 py-2 rounded-full text-sm sm:text-base border border-[#C9A45A]/30 hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition-all duration-300" data-filter="hall">
                Event Hall
            </button>
            <button class="filter-btn px-4 sm:px-6 py-2 rounded-full text-sm sm:text-base border border-[#C9A45A]/30 hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition-all duration-300" data-filter="boardroom">
                Board Rooms
            </button>
            <button class="filter-btn px-4 sm:px-6 py-2 rounded-full text-sm sm:text-base border border-[#C9A45A]/30 hover:bg-[#C9A45A] hover:text-[#0F0F0F] transition-all duration-300" data-filter="restaurant">
                Restaurant
            </button>
        </div>

        <!-- Gallery Grid -->
        <div class="masonry-grid" id="gallery-grid">
            <?php foreach($gallery_images as $image): 
                $filter_class = '';
                switch($image['category']) {
                    case 'room':
                        $filter_class = 'rooms';
                        break;
                    case 'hall':
                        $filter_class = 'hall';
                        break;
                    case 'boardroom':
                        $filter_class = 'boardroom';
                        break;
                    case 'food':
                        $filter_class = 'restaurant';
                        break;
                }
            ?>
            <div class="masonry-item gallery-item <?php echo $filter_class; ?>" data-aos="zoom-in">
                <img src="<?php echo SITE_URL . $image['image_path']; ?>" 
                     alt="<?php echo $image['title']; ?>"
                     onclick="openLightbox('<?php echo SITE_URL . $image['image_path']; ?>', '<?php echo $image['title']; ?>')">
                <div class="gallery-overlay">
                    <div>
                        <h3 class="text-[#F5F5F5] font-bold text-lg"><?php echo $image['title']; ?></h3>
                        <p class="text-[#C9A45A] text-sm capitalize"><?php echo $image['category']; ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if(empty($gallery_images)): ?>
        <div class="text-center py-12">
            <i class="fas fa-images text-6xl text-[#C9A45A]/30 mb-4"></i>
            <h3 class="text-2xl text-[#F5F5F5] mb-2">No Images Yet</h3>
            <p class="text-[#F5F5F5]/60">Check back soon for our gallery updates</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <span class="lightbox-nav lightbox-prev" onclick="changeImage(-1)">&#10094;</span>
    <span class="lightbox-nav lightbox-next" onclick="changeImage(1)">&#10095;</span>
    <div class="lightbox-content">
        <img id="lightbox-img" src="" alt="">
    </div>
</div>

<script>
// Filter functionality
const filterBtns = document.querySelectorAll('.filter-btn');
const galleryItems = document.querySelectorAll('.gallery-item');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        // Filter items
        const filter = btn.dataset.filter;
        galleryItems.forEach(item => {
            if(filter === 'all' || item.classList.contains(filter)) {
                item.style.display = 'block';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'scale(1)';
                }, 50);
            } else {
                item.style.opacity = '0';
                item.style.transform = 'scale(0.8)';
                setTimeout(() => {
                    item.style.display = 'none';
                }, 300);
            }
        });
    });
});

// Lightbox functionality
let currentImageIndex = 0;
let images = [];

function openLightbox(src, title) {
    // Get all visible gallery images
    images = Array.from(document.querySelectorAll('.gallery-item:not([style*="display: none"]) img')).map(img => ({
        src: img.src,
        title: img.alt
    }));
    
    // Find current image index
    currentImageIndex = images.findIndex(img => img.src === src);
    
    // Show lightbox
    document.getElementById('lightbox').classList.add('active');
    document.getElementById('lightbox-img').src = src;
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = 'auto';
}

function changeImage(direction) {
    currentImageIndex += direction;
    
    if(currentImageIndex < 0) {
        currentImageIndex = images.length - 1;
    } else if(currentImageIndex >= images.length) {
        currentImageIndex = 0;
    }
    
    document.getElementById('lightbox-img').src = images[currentImageIndex].src;
}

// Close lightbox with Escape key
document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') {
        closeLightbox();
    } else if(e.key === 'ArrowLeft') {
        changeImage(-1);
    } else if(e.key === 'ArrowRight') {
        changeImage(1);
    }
});

// Initialize AOS
AOS.init({
    duration: 1000,
    once: true
});
</script>

<?php require_once 'includes/footer.php'; ?>