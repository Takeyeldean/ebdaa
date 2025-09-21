// Simple Optimized JavaScript for Ebdaa
(function() {
    'use strict';
    
    // Performance optimization for animations
    function optimizeAnimations() {
        const animatedElements = document.querySelectorAll('.floating, .bounce-in, .fade-in');
        animatedElements.forEach(el => {
            el.style.willChange = 'transform';
            el.style.transform = 'translateZ(0)';
        });
    }
    
    // Lazy loading for images
    function lazyLoadImages() {
        const images = document.querySelectorAll('img[data-src]');
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy-load');
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            });
            images.forEach(img => imageObserver.observe(img));
        } else {
            images.forEach(img => {
                img.src = img.dataset.src;
                img.classList.add('loaded');
            });
        }
    }
    
    // Optimize chart rendering
    function optimizeCharts() {
        const charts = document.querySelectorAll('canvas');
        charts.forEach(canvas => {
            canvas.style.willChange = 'transform';
            canvas.style.transform = 'translateZ(0)';
        });
    }
    
    // Performance monitoring
    function monitorPerformance() {
        window.addEventListener('load', () => {
            const loadTime = performance.now();
            console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);
        });
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        optimizeAnimations();
        lazyLoadImages();
        optimizeCharts();
        monitorPerformance();
    });
    
})();
