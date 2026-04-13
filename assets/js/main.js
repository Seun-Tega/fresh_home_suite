// Main JavaScript file

// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

// Date range picker initialization
$(document).ready(function() {
    if ($('#dateRange').length) {
        $('#dateRange').daterangepicker({
            minDate: new Date(),
            opens: 'center',
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    }
});

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });
    
    return isValid;
}

// Image preview before upload
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// AJAX function for checking availability
function checkAvailability(roomId, checkIn, checkOut) {
    $.ajax({
        url: 'api/check-availability.php',
        method: 'POST',
        data: {
            room_id: roomId,
            check_in: checkIn,
            check_out: checkOut
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.available) {
                // Show available message
            } else {
                // Show unavailable message
            }
        }
    });
}

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Back to top button
const backToTop = document.getElementById('backToTop');
if (backToTop) {
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('opacity-100', 'visible');
            backToTop.classList.remove('opacity-0', 'invisible');
        } else {
            backToTop.classList.add('opacity-0', 'invisible');
            backToTop.classList.remove('opacity-100', 'visible');
        }
    });
    
    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}
// Hero Section Rotating Background
document.addEventListener('DOMContentLoaded', function() {
    // Rotating Background Images
    const heroBgs = document.querySelectorAll('.hero-bg');
    if (heroBgs.length > 0) {
        let currentIndex = 0;
        
        function rotateBackground() {
            // Hide all images
            heroBgs.forEach(bg => {
                bg.classList.remove('opacity-100');
                bg.classList.add('opacity-0');
            });
            
            // Show current image
            heroBgs[currentIndex].classList.remove('opacity-0');
            heroBgs[currentIndex].classList.add('opacity-100');
            
            // Move to next image
            currentIndex = (currentIndex + 1) % heroBgs.length;
        }
        
        // Initial show
        rotateBackground();
        
        // Rotate every 5 seconds
        setInterval(rotateBackground, 5000);
    }
    
    // Typewriter Effect
    const typewriterElement = document.getElementById('typewriter-text');
    if (typewriterElement) {
        const phrases = [
            'Where Every Stay Feels Like Home',
            'Experience Luxury Like Never Before',
            'Your Perfect Getaway Awaits',
            'Unforgettable Moments Begin Here'
        ];
        
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        
        function typewriter() {
            const currentPhrase = phrases[phraseIndex];
            
            if (isDeleting) {
                typewriterElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
            } else {
                typewriterElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
            }
            
            if (!isDeleting && charIndex === currentPhrase.length) {
                isDeleting = true;
                setTimeout(typewriter, 2000); // Pause at end
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false;
                phraseIndex = (phraseIndex + 1) % phrases.length;
                setTimeout(typewriter, 500); // Pause before next
            } else {
                const speed = isDeleting ? 50 : 100;
                setTimeout(typewriter, speed);
            }
        }
        
        // Start typewriter effect
        setTimeout(typewriter, 2000);
    }
});