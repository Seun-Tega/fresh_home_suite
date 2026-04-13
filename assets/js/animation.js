// Animation JavaScript file

// Intersection Observer for scroll animations - ALWAYS ACTIVE
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            // Don't unobserve - let it trigger every time element comes into view
        } else {
            // Optional: remove animation class when out of view to replay on next scroll
            // entry.target.classList.remove('animate__animated', 'animate__fadeInUp');
        }
    });
}, observerOptions);

document.querySelectorAll('[data-animate]').forEach(el => {
    observer.observe(el);
});

// Parallax effect - CONTINUOUS
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallax = document.querySelectorAll('[data-parallax]');
    
    parallax.forEach(el => {
        const speed = el.dataset.parallax || 0.5;
        // Apply transform with smooth transition
        el.style.transition = 'transform 0.1s ease-out';
        el.style.transform = `translateY(${scrolled * speed}px)`;
    });
});

// Counter animation for stats - REPEAT ON HOVER
function animateCounter(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.innerText = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Initialize counters and make them repeat on hover
document.querySelectorAll('[data-counter]').forEach(el => {
    const endValue = parseInt(el.dataset.target);
    const startValue = 0;
    
    // Initial animation when element comes into view
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target, startValue, endValue, 2000);
            }
        });
    }, { threshold: 0.5 });
    
    counterObserver.observe(el);
    
    // Add hover effect to replay animation
    el.addEventListener('mouseenter', function() {
        // Reset to start value
        this.innerText = startValue;
        // Animate again
        animateCounter(this, startValue, endValue, 1000); // Faster on hover
    });
    
    // Add click effect to replay animation
    el.addEventListener('click', function() {
        this.innerText = startValue;
        animateCounter(this, startValue, endValue, 800); // Even faster on click
    });
});

// Typing animation - CONTINUOUS
class TypeWriter {
    constructor(element, words, wait = 3000) {
        this.element = element;
        this.words = words;
        this.txt = '';
        this.wordIndex = 0;
        this.wait = parseInt(wait, 10);
        this.isDeleting = false;
        this.isPaused = false;
        this.type();
        
        // Add hover effect to pause/resume
        element.addEventListener('mouseenter', () => {
            this.isPaused = true;
        });
        
        element.addEventListener('mouseleave', () => {
            this.isPaused = false;
            // Resume typing
            if (!this.timeout) {
                this.type();
            }
        });
        
        // Add click to change word
        element.addEventListener('click', () => {
            this.wordIndex = (this.wordIndex + 1) % this.words.length;
            this.txt = '';
            this.isDeleting = false;
        });
    }
    
    type() {
        if (this.isPaused) {
            this.timeout = setTimeout(() => this.type(), 100);
            return;
        }
        
        const current = this.wordIndex % this.words.length;
        const fullTxt = this.words[current];
        
        if (this.isDeleting) {
            this.txt = fullTxt.substring(0, this.txt.length - 1);
        } else {
            this.txt = fullTxt.substring(0, this.txt.length + 1);
        }
        
        this.element.innerHTML = `<span class="txt">${this.txt}</span><span class="cursor">|</span>`;
        
        let typeSpeed = 300;
        
        if (this.isDeleting) {
            typeSpeed /= 2;
        }
        
        if (!this.isDeleting && this.txt === fullTxt) {
            typeSpeed = this.wait;
            this.isDeleting = true;
        } else if (this.isDeleting && this.txt === '') {
            this.isDeleting = false;
            this.wordIndex++;
            typeSpeed = 500;
        }
        
        this.timeout = setTimeout(() => this.type(), typeSpeed);
    }
}

// Initialize typing animation
const typingElement = document.getElementById('typing');
if (typingElement) {
    new TypeWriter(typingElement, ['Luxury', 'Comfort', 'Elegance', 'Sophistication'], 2000);
}

// Hover animations for cards
document.querySelectorAll('.hover-scale, .room-card, .menu-item, .package-card').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.02)';
        this.style.transition = 'transform 0.3s ease';
        this.style.boxShadow = '0 20px 25px -5px rgba(201, 164, 90, 0.2), 0 10px 10px -5px rgba(201, 164, 90, 0.1)';
    });
    
    el.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = 'none';
    });
});

// Button hover animations
document.querySelectorAll('button, .btn, a').forEach(el => {
    if (el.tagName === 'A' || el.tagName === 'BUTTON' || el.classList.contains('btn')) {
        el.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    }
});

// Floating animation on hover for icons
document.querySelectorAll('.fas, .fab, .far').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.animation = 'float 1s ease-in-out infinite';
    });
    
    el.addEventListener('mouseleave', function() {
        this.style.animation = 'float 6s ease-in-out infinite';
    });
});

// Image gallery hover effect
document.querySelectorAll('.gallery-image, .room-image, .hall-image').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.05)';
        this.style.transition = 'transform 0.5s ease';
    });
    
    el.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
});

// Price card hover effects
document.querySelectorAll('.price-card, .pricing-card').forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.transition = 'all 0.3s ease';
        this.style.borderColor = '#C9A45A';
    });
    
    el.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.borderColor = 'rgba(201, 164, 90, 0.2)';
    });
});

// Add smooth scroll animations
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

// Navbar scroll effect with animation
let lastScroll = 0;
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('nav');
    const currentScroll = window.pageYOffset;
    
    if (!navbar) return;
    
    if (currentScroll > lastScroll && currentScroll > 100) {
        // Scrolling down - hide navbar with animation
        navbar.style.transform = 'translateY(-100%)';
        navbar.style.transition = 'transform 0.3s ease';
    } else if (currentScroll < lastScroll) {
        // Scrolling up - show navbar with animation
        navbar.style.transform = 'translateY(0)';
        navbar.style.transition = 'transform 0.3s ease';
        
        // Add shadow when not at top
        if (currentScroll > 50) {
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
        } else {
            navbar.style.boxShadow = 'none';
        }
    }
    
    lastScroll = currentScroll;
});

// Page load animations
document.addEventListener('DOMContentLoaded', function() {
    // Animate page title
    const pageTitle = document.querySelector('h1');
    if (pageTitle) {
        pageTitle.classList.add('animate__animated', 'animate__fadeInDown');
    }
    
    // Animate all images with fade-in
    document.querySelectorAll('img').forEach((img, index) => {
        img.style.animation = `fadeIn 0.5s ease ${index * 0.1}s forwards`;
        img.style.opacity = '0';
    });
    
    // Add loading animation to buttons
    document.querySelectorAll('button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
        });
    });
});

// Add custom keyframe animations
const style = document.createElement('style');
style.textContent = `
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-50px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(50px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    /* Typing cursor animation */
    .cursor {
        animation: blink 1s infinite;
        color: #C9A45A;
        font-weight: bold;
        margin-left: 2px;
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    
    /* Hover glow effect for gold elements */
    .hover-glow:hover {
        box-shadow: 0 0 20px rgba(201, 164, 90, 0.5);
        transition: box-shadow 0.3s ease;
    }
    
    /* Scale on hover for interactive elements */
    .hover-scale {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-scale:hover {
        transform: scale(1.02);
        box-shadow: 0 20px 25px -5px rgba(201, 164, 90, 0.2);
    }
`;

document.head.appendChild(style);

// Replay animations on click for any element with data-replay attribute
document.querySelectorAll('[data-replay]').forEach(el => {
    el.addEventListener('click', function() {
        const animation = this.dataset.replay || 'fadeIn';
        this.classList.remove('animate__animated', `animate__${animation}`);
        void this.offsetWidth; // Trigger reflow
        this.classList.add('animate__animated', `animate__${animation}`);
    });
});

// Make animations restart when element comes into view (every time)
const replayObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            const animation = el.dataset.replayOnView || 'fadeInUp';
            
            // Remove and re-add animation class
            el.classList.remove('animate__animated', `animate__${animation}`);
            void el.offsetWidth; // Trigger reflow
            el.classList.add('animate__animated', `animate__${animation}`);
        }
    });
}, { threshold: 0.3 });

document.querySelectorAll('[data-replay-on-view]').forEach(el => {
    replayObserver.observe(el);
});

// Add touch support for mobile
if ('ontouchstart' in window) {
    document.querySelectorAll('.hover-scale, [data-animate]').forEach(el => {
        el.addEventListener('touchstart', function() {
            this.classList.add('touch-active');
        });
        
        el.addEventListener('touchend', function() {
            this.classList.remove('touch-active');
        });
    });
}