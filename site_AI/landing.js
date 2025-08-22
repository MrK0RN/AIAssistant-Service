// Landing Page JavaScript

class LandingPage {
    constructor() {
        this.init();
    }

    init() {
        this.initializeAOS();
        this.setupNavigation();
        this.setupScrollEffects();
        this.setupSmoothScrolling();
        this.setupAnimations();
        this.setupParallax();
    }

    // Initialize AOS (Animate On Scroll)
    initializeAOS() {
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 1000,
                easing: 'ease-out-cubic',
                once: true,
                offset: 120
            });
        }
    }

    // Setup navigation effects
    setupNavigation() {
        const navbar = document.querySelector('.navbar');
        
        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu auto-close
        const navLinks = document.querySelectorAll('.nav-link');
        const navCollapse = document.querySelector('.navbar-collapse');
        
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (navCollapse.classList.contains('show')) {
                    const navToggler = document.querySelector('.navbar-toggler');
                    navToggler.click();
                }
            });
        });
    }

    // Setup scroll effects
    setupScrollEffects() {
        // Parallax effect for hero shapes
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            const shapes = document.querySelectorAll('.hero-shape');
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.3;
                shape.style.transform = `translateY(${rate * speed}px)`;
            });
        });

        // Floating cards animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                } else {
                    entry.target.style.animationPlayState = 'paused';
                }
            });
        }, observerOptions);

        const floatingCards = document.querySelectorAll('.floating-card');
        floatingCards.forEach(card => {
            observer.observe(card);
        });
    }

    // Setup smooth scrolling for anchor links
    setupSmoothScrolling() {
        const anchorLinks = document.querySelectorAll('a[href^="#"]');
        
        anchorLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                if (href === '#') return;
                
                e.preventDefault();
                
                const target = document.querySelector(href);
                if (target) {
                    const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                    
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }

    // Setup additional animations
    setupAnimations() {
        // Animate counters in hero stats
        this.animateCounters();
        
        // Setup hover effects for cards
        this.setupCardHoverEffects();
        
        // Setup pricing card interactions
        this.setupPricingCards();
    }

    // Animate number counters
    animateCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => {
            observer.observe(counter);
        });
    }

    animateCounter(element) {
        const text = element.textContent;
        const number = parseInt(text.replace(/[^\d]/g, ''));
        const suffix = text.replace(/[\d]/g, '');
        
        let current = 0;
        const increment = number / 100;
        
        const timer = setInterval(() => {
            current += increment;
            
            if (current >= number) {
                current = number;
                clearInterval(timer);
            }
            
            if (suffix.includes('K')) {
                element.textContent = (current / 1000).toFixed(1) + 'K+';
            } else if (suffix.includes('%')) {
                element.textContent = current.toFixed(1) + '%';
            } else if (suffix.includes('M')) {
                element.textContent = (current / 1000000).toFixed(1) + 'M+';
            } else {
                element.textContent = Math.floor(current) + suffix;
            }
        }, 20);
    }

    // Setup card hover effects
    setupCardHoverEffects() {
        const cards = document.querySelectorAll('.feature-card, .testimonial-card, .pricing-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
                card.style.transition = 'all 0.3s ease';
            });
            
            card.addEventListener('mouseleave', () => {
                if (card.classList.contains('featured')) {
                    card.style.transform = 'scale(1.05)';
                } else {
                    card.style.transform = 'translateY(0) scale(1)';
                }
            });
        });
    }

    // Setup pricing card interactions
    setupPricingCards() {
        const pricingCards = document.querySelectorAll('.pricing-card');
        
        pricingCards.forEach(card => {
            const ctaButton = card.querySelector('.btn');
            
            if (ctaButton) {
                ctaButton.addEventListener('click', (e) => {
                    // Add click animation
                    ctaButton.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        ctaButton.style.transform = '';
                    }, 150);
                });
            }
        });
    }

    // Setup parallax effects
    setupParallax() {
        const parallaxElements = document.querySelectorAll('.hero-background, .dashboard-preview');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * 0.3;
                element.style.transform = `translateY(${rate}px)`;
            });
        });
    }
}

// Utility functions
function throttle(func, delay) {
    let timeoutId;
    let lastExecTime = 0;
    
    return function (...args) {
        const currentTime = Date.now();
        
        if (currentTime - lastExecTime > delay) {
            func.apply(this, args);
            lastExecTime = currentTime;
        } else {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                func.apply(this, args);
                lastExecTime = Date.now();
            }, delay - (currentTime - lastExecTime));
        }
    };
}

// Demo functionality for preview chart
class PreviewChart {
    constructor() {
        this.setupChart();
    }

    setupChart() {
        const chartElement = document.querySelector('.preview-chart');
        if (!chartElement) return;

        // Create animated chart bars
        const bars = this.createBars();
        chartElement.appendChild(bars);
        
        // Animate bars
        setTimeout(() => {
            this.animateBars(bars);
        }, 1000);
    }

    createBars() {
        const barsContainer = document.createElement('div');
        barsContainer.style.cssText = `
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            height: 60px;
            display: flex;
            align-items: end;
            gap: 5px;
        `;

        const barHeights = [30, 45, 35, 55, 40, 60, 50];
        
        barHeights.forEach((height, index) => {
            const bar = document.createElement('div');
            bar.style.cssText = `
                flex: 1;
                height: 0;
                background: linear-gradient(to top, #007bff, #17a2b8);
                border-radius: 2px 2px 0 0;
                transition: height 0.5s ease;
                transition-delay: ${index * 0.1}s;
            `;
            bar.dataset.targetHeight = height;
            barsContainer.appendChild(bar);
        });

        return barsContainer;
    }

    animateBars(barsContainer) {
        const bars = barsContainer.querySelectorAll('div');
        
        bars.forEach(bar => {
            const targetHeight = bar.dataset.targetHeight;
            bar.style.height = `${targetHeight}px`;
        });
    }
}

// Form handling for CTA buttons
class CTAHandler {
    constructor() {
        this.setupCTAButtons();
    }

    setupCTAButtons() {
        const ctaButtons = document.querySelectorAll('.btn[href*="register"], .btn[href*="login"]');
        
        ctaButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                // Add tracking or analytics here if needed
                this.trackButtonClick(button);
            });
        });
    }

    trackButtonClick(button) {
        const buttonText = button.textContent.trim();
        const section = button.closest('section')?.className || 'unknown';
        
        // Console log for demo purposes
        console.log(`CTA clicked: "${buttonText}" in section: ${section}`);
        
        // Here you would typically send analytics data
        // Example: gtag('event', 'click', { button_text: buttonText, section: section });
    }
}

// Performance optimizations
class PerformanceOptimizer {
    constructor() {
        this.optimizeImages();
        this.optimizeAnimations();
    }

    optimizeImages() {
        // Lazy load images
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    }

    optimizeAnimations() {
        // Respect user's motion preferences
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
            
            // Disable complex animations
            const style = document.createElement('style');
            style.textContent = `
                .reduced-motion * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            `;
            document.head.appendChild(style);
        }
    }
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new LandingPage();
    new PreviewChart();
    new CTAHandler();
    new PerformanceOptimizer();
    
    // Set current year in footer if needed
    const currentYear = new Date().getFullYear();
    const yearElements = document.querySelectorAll('.current-year');
    yearElements.forEach(element => {
        element.textContent = currentYear;
    });
});

// Handle window resize events
window.addEventListener('resize', throttle(() => {
    // Recalculate animations if needed
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }
}, 250));

// Handle page visibility changes
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Pause animations when page is not visible
        document.body.style.animationPlayState = 'paused';
    } else {
        // Resume animations when page becomes visible
        document.body.style.animationPlayState = 'running';
    }
});