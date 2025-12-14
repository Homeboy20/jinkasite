/**
 * Performance Optimizations JavaScript
 * Lazy Loading, Image Optimization, and Performance Utilities
 */

(function() {
    'use strict';

    // ============================================
    // LAZY LOADING IMAGES
    // ============================================
    
    /**
     * Intersection Observer for lazy loading
     */
    function initLazyLoading() {
        // Check for native lazy loading support
        if ('loading' in HTMLImageElement.prototype) {
            // Browser supports native lazy loading
            const lazyImages = document.querySelectorAll('img[data-src], img[loading="lazy"]');
            lazyImages.forEach(img => {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
            });
        } else {
            // Fallback to Intersection Observer
            const lazyImages = document.querySelectorAll('img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            img.classList.remove('lazy');
                            img.classList.add('lazy-loaded');
                            imageObserver.unobserve(img);
                        }
                    });
                }, {
                    rootMargin: '50px 0px',
                    threshold: 0.01
                });

                lazyImages.forEach(img => imageObserver.observe(img));
            } else {
                // Fallback for older browsers
                lazyImages.forEach(img => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                });
            }
        }
    }

    /**
     * Lazy load background images
     */
    function initLazyBackgrounds() {
        const lazyBackgrounds = document.querySelectorAll('[data-bg]');
        
        if ('IntersectionObserver' in window) {
            const bgObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        element.style.backgroundImage = `url(${element.dataset.bg})`;
                        element.removeAttribute('data-bg');
                        bgObserver.unobserve(element);
                    }
                });
            }, {
                rootMargin: '50px 0px'
            });

            lazyBackgrounds.forEach(bg => bgObserver.observe(bg));
        } else {
            lazyBackgrounds.forEach(bg => {
                bg.style.backgroundImage = `url(${bg.dataset.bg})`;
                bg.removeAttribute('data-bg');
            });
        }
    }

    // ============================================
    // WEBP IMAGE SUPPORT DETECTION
    // ============================================
    
    function detectWebPSupport() {
        const webpTestImage = 'data:image/webp;base64,UklGRiQAAABXRUJQVlA4IBgAAAAwAQCdASoBAAEAAwA0JaQAA3AA/vuUAAA=';
        const img = new Image();
        
        img.onload = img.onerror = function() {
            const isSupported = (img.height === 1);
            document.documentElement.classList.add(isSupported ? 'webp' : 'no-webp');
        };
        
        img.src = webpTestImage;
    }

    // ============================================
    // PRELOAD CRITICAL IMAGES
    // ============================================
    
    function preloadCriticalImages() {
        const criticalImages = document.querySelectorAll('img[data-preload]');
        criticalImages.forEach(img => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = img.src || img.dataset.src;
            document.head.appendChild(link);
        });
    }

    // ============================================
    // DEFER NON-CRITICAL CSS
    // ============================================
    
    function loadDeferredCSS() {
        const deferredStyles = document.querySelectorAll('link[data-defer-css]');
        deferredStyles.forEach(link => {
            link.rel = 'stylesheet';
            link.removeAttribute('data-defer-css');
        });
    }

    // ============================================
    // PERFORMANCE MONITORING
    // ============================================
    
    function logPerformanceMetrics() {
        if ('performance' in window && window.performance.timing) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const perfData = window.performance.timing;
                    const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                    const connectTime = perfData.responseEnd - perfData.requestStart;
                    const renderTime = perfData.domComplete - perfData.domLoading;
                    
                    console.log('🚀 Performance Metrics:');
                    console.log(`Page Load Time: ${pageLoadTime}ms`);
                    console.log(`Server Response Time: ${connectTime}ms`);
                    console.log(`DOM Render Time: ${renderTime}ms`);
                    
                    // Send to analytics if available
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'timing_complete', {
                            name: 'page_load',
                            value: pageLoadTime,
                            event_category: 'Performance'
                        });
                    }
                }, 0);
            });
        }
    }

    // ============================================
    // RESOURCE HINTS
    // ============================================
    
    function addResourceHints() {
        // DNS prefetch for external domains
        const externalDomains = [
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://www.google-analytics.com'
        ];
        
        externalDomains.forEach(domain => {
            const link = document.createElement('link');
            link.rel = 'dns-prefetch';
            link.href = domain;
            document.head.appendChild(link);
        });
    }

    // ============================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================
    
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#' || targetId === '') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // ============================================
    // DEBOUNCE UTILITY
    // ============================================
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ============================================
    // OPTIMIZE SCROLL EVENTS
    // ============================================
    
    let isScrolling = false;
    function handleScroll() {
        if (!isScrolling) {
            window.requestAnimationFrame(() => {
                // Your scroll handling code here
                isScrolling = false;
            });
            isScrolling = true;
        }
    }

    // ============================================
    // INITIALIZE ALL OPTIMIZATIONS
    // ============================================
    
    function init() {
        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initLazyLoading();
                initLazyBackgrounds();
                detectWebPSupport();
                initSmoothScroll();
                addResourceHints();
            });
        } else {
            initLazyLoading();
            initLazyBackgrounds();
            detectWebPSupport();
            initSmoothScroll();
            addResourceHints();
        }

        // Run on load
        window.addEventListener('load', () => {
            preloadCriticalImages();
            loadDeferredCSS();
            logPerformanceMetrics();
        });

        // Optimize scroll
        window.addEventListener('scroll', handleScroll, { passive: true });
    }

    // Start initialization
    init();

    // Expose utilities globally if needed
    window.PerformanceUtils = {
        debounce: debounce,
        lazyLoad: initLazyLoading
    };

})();
