// Optimized JavaScript for Ebdaa - Performance Enhanced
(function() {
    'use strict';
    
    const performanceOptimized = {
        init: function() {
            this.lazyLoadImages();
            this.optimizeAnimations();
            this.setupIntersectionObserver();
            this.preloadCriticalResources();
            this.setupServiceWorker();
        },
        
        lazyLoadImages: function() {
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
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });
                images.forEach(img => imageObserver.observe(img));
            } else {
                images.forEach(img => {
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                });
            }
        },
        
        optimizeAnimations: function() {
            const animatedElements = document.querySelectorAll('.floating, .bounce-in, .fade-in');
            animatedElements.forEach(el => {
                el.classList.add('performance-optimized');
            });
        },
        
        setupIntersectionObserver: function() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('fade-in');
                        }
                    });
                }, { threshold: 0.1 });
                
                document.querySelectorAll('.glass-card, .chart-container').forEach(el => {
                    observer.observe(el);
                });
            }
        },
        
        preloadCriticalResources: function() {
            const criticalResources = [
                'https://cdn.tailwindcss.com',
                'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
            ];
            
            criticalResources.forEach(resource => {
                const link = document.createElement('link');
                link.rel = 'preload';
                link.href = resource;
                link.as = 'style';
                document.head.appendChild(link);
            });
        },
        
        setupServiceWorker: function() {
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .catch(err => console.log('SW registration failed'));
            }
        },
        
        debounce: function(func, wait, immediate) {
            let timeout;
            return function executedFunction() {
                const context = this;
                const args = arguments;
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };
    
    const chartOptimizer = {
        init: function() {
            this.optimizeChartRendering();
            this.setupChartResize();
        },
        
        optimizeChartRendering: function() {
            const charts = document.querySelectorAll('canvas');
            charts.forEach(canvas => {
                canvas.style.willChange = 'transform';
                canvas.style.transform = 'translateZ(0)';
            });
        },
        
        setupChartResize: function() {
            if ('ResizeObserver' in window) {
                const resizeObserver = new ResizeObserver(entries => {
                    entries.forEach(entry => {
                        const chart = entry.target._chart;
                        if (chart) {
                            chart.resize();
                        }
                    });
                });
                
                document.querySelectorAll('canvas').forEach(canvas => {
                    resizeObserver.observe(canvas);
                });
            }
        },
        
        debouncedResize: performanceOptimized.debounce(function() {
            const charts = document.querySelectorAll('canvas');
            charts.forEach(canvas => {
                if (canvas._chart) {
                    canvas._chart.resize();
                }
            });
        }, 250)
    };
    
    const modalOptimizer = {
        init: function() {
            this.setupModalEvents();
            this.optimizeModalAnimations();
        },
        
        setupModalEvents: function() {
            document.addEventListener('click', function(e) {
                if (e.target.matches('[data-modal-target]')) {
                    e.preventDefault();
                    const target = e.target.getAttribute('data-modal-target');
                    const modal = document.querySelector(target);
                    if (modal) {
                        modal.classList.add('slide-in');
                        modal.style.display = 'block';
                    }
                }
                
                if (e.target.matches('[data-modal-close]') || e.target.matches('.modal-backdrop')) {
                    const modal = e.target.closest('.modal');
                    if (modal) {
                        modal.classList.remove('slide-in');
                        setTimeout(() => modal.style.display = 'none', 300);
                    }
                }
            });
        },
        
        optimizeModalAnimations: function() {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.style.willChange = 'transform';
                modal.style.transform = 'translateZ(0)';
            });
        }
    };
    
    const formOptimizer = {
        init: function() {
            this.setupFormValidation();
            this.optimizeFormSubmission();
        },
        
        setupFormValidation: function() {
            const forms = document.querySelectorAll('form[data-validate]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                        form.classList.add('was-validated');
                    }
                });
                
                form.addEventListener('input', performanceOptimized.debounce(function(e) {
                    const input = e.target;
                    if (input.checkValidity()) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                }, 300));
            });
        },
        
        optimizeFormSubmission: function() {
            const forms = document.querySelectorAll('form[data-ajax]');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.textContent;
                    
                    submitBtn.innerHTML = '<span class="loading"></span> جاري الإرسال...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                location.reload();
                            }
                        } else {
                            alert(data.message || 'حدث خطأ');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('حدث خطأ في الاتصال');
                    })
                    .finally(() => {
                        submitBtn.textContent = originalText;
                    });
                });
            });
        }
    };
    
    const notificationOptimizer = {
        init: function() {
            this.setupNotificationPolling();
            this.optimizeNotificationDisplay();
        },
        
        setupNotificationPolling: function() {
            if (document.querySelector('.notification-badge')) {
                setInterval(() => {
                    fetch('/api/notifications/count')
                        .then(response => response.json())
                        .then(data => {
                            const badge = document.querySelector('.notification-badge');
                            if (badge) {
                                badge.textContent = data.count;
                                badge.style.display = data.count > 0 ? 'flex' : 'none';
                            }
                        })
                        .catch(err => console.log('Notification polling failed'));
                }, 30000);
            }
        },
        
        optimizeNotificationDisplay: function() {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                notification.style.willChange = 'transform';
                notification.style.transform = 'translateZ(0)';
            });
        }
    };
    
    const performanceMonitor = {
        init: function() {
            this.monitorPageLoad();
            this.monitorUserInteractions();
        },
        
        monitorPageLoad: function() {
            window.addEventListener('load', () => {
                const loadTime = performance.now();
                console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);
                if (loadTime > 3000) {
                    console.warn('Page load time is slow:', loadTime);
                }
            });
        },
        
        monitorUserInteractions: function() {
            let interactionCount = 0;
            ['click', 'scroll', 'keydown'].forEach(event => {
                document.addEventListener(event, performanceOptimized.throttle(() => {
                    interactionCount++;
                    if (interactionCount > 100) {
                        console.warn('High interaction count detected:', interactionCount);
                    }
                }, 1000));
            });
        }
    };
    
    // Initialize everything when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        performanceOptimized.init();
        chartOptimizer.init();
        modalOptimizer.init();
        formOptimizer.init();
        notificationOptimizer.init();
        performanceMonitor.init();
    });
    
    // Expose to global scope
    window.EbdaaOptimized = {
        performanceOptimized,
        chartOptimizer,
        modalOptimizer,
        formOptimizer,
        notificationOptimizer,
        performanceMonitor
    };
    
})();