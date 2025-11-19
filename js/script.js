// Mobile Menu Toggle
const mobileMenuToggle = document.getElementById('mobileMenuToggle');
const mobileMenuClose = document.getElementById('mobileMenuClose');
const mobileNav = document.getElementById('mobileNav');

if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        mobileNav.classList.add('mobile-menu-open');
        document.body.style.overflow = 'hidden';
    });
}

if (mobileMenuClose) {
    mobileMenuClose.addEventListener('click', () => {
        mobileNav.classList.remove('mobile-menu-open');
        document.body.style.overflow = '';
    });
}

// Close menu when clicking outside
if (mobileNav) {
    mobileNav.addEventListener('click', (e) => {
        if (e.target === mobileNav) {
            mobileNav.classList.remove('mobile-menu-open');
            document.body.style.overflow = '';
        }
    });
}

// Close menu when clicking nav links
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
            mobileNav.classList.remove('mobile-menu-open');
            document.body.style.overflow = '';
        }
    });
});

// Smooth scrolling for anchor links
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

// Header scroll effect
let lastScroll = 0;
const header = document.querySelector('.header');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 100) {
        header.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1)';
    } else {
        header.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
    }
    
    lastScroll = currentScroll;
});

// Intersection Observer for fade-in animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all feature cards, application cards, etc.
document.querySelectorAll('.feature-card, .application-card, .stat-item').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// Form submission handling
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        // Disable button and show loading state
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
        
        // Submit form via AJAX
        fetch('contact.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for your inquiry! We will contact you soon.');
                this.reset();
            } else {
                alert('Sorry, there was an error sending your message. Please try WhatsApp or call us directly.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Sorry, there was an error. Please contact us via WhatsApp or phone.');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    });
}

// Mobile menu toggle (if needed in future)
const createMobileMenu = () => {
    const nav = document.querySelector('.nav');
    if (!nav) return;
    // Avoid creating more than one mobile button
    if (document.querySelector('.mobile-menu-button')) return;

    const menuButton = document.createElement('button');
    menuButton.className = 'mobile-menu-button';
    menuButton.setAttribute('aria-label', 'Toggle menu');
    menuButton.innerHTML = '<svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>';

    if (window.innerWidth <= 768) {
        const header = document.querySelector('.header-content');
        if (header && !header.querySelector('.mobile-menu-button')) {
            header.insertBefore(menuButton, nav);
        }

        menuButton.addEventListener('click', () => {
            nav.classList.toggle('mobile-menu-open');
        });
    }
};

// Initialize on load
window.addEventListener('load', () => {
    // Add any initialization code here
    console.log('JINKA Cutting Plotter website loaded');
    // initialize mobile menu button when page loads
    try { createMobileMenu(); } catch (e) { console.warn('Mobile menu init failed', e); }
});

// Keep mobile menu in sync on resize
window.addEventListener('resize', () => {
    // re-create mobile menu button if needed
    try { createMobileMenu(); } catch (e) {}
});

// Track WhatsApp clicks for analytics (optional)
document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
    link.addEventListener('click', () => {
        console.log('WhatsApp link clicked');
        // Add analytics tracking here if needed
    });
});

// Track phone clicks for analytics (optional)
document.querySelectorAll('a[href^="tel:"]').forEach(link => {
    link.addEventListener('click', () => {
        console.log('Phone link clicked');
        // Add analytics tracking here if needed
    });
});

// Specifications Section Functionality
function showSpecCategory(category) {
    // Update buttons
    document.querySelectorAll('.spec-category-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-category="${category}"]`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.spec-category').forEach(section => {
        section.classList.remove('active');
    });
    document.querySelector(`.spec-category[data-category="${category}"]`).classList.add('active');
    
    // Update image if needed (future enhancement)
    updateSpecImage(category);
}

function updateSpecImage(category) {
    const specImage = document.getElementById('specImage');
    const imageMap = {
        'general': 'images/plotter-hero.webp',
        'cutting': 'images/plotter-main.jpg',
        'connectivity': 'images/plotter-hero.webp',
        'physical': 'images/plotter-action.jpg'
    };
    
    if (imageMap[category] && specImage) {
        specImage.src = imageMap[category];
    }
}

function downloadSpecSheet() {
    // Create a simple spec sheet download
    const specs = {
        model: 'JINKA XL-1351E',
        cuttingWidth: '1210mm (47.6")',
        feedWidth: '1350mm (53.1")',
        cuttingLength: '2000mm (6.5 feet)',
        speed: '10-800mm/s',
        pressure: '10-500g',
        accuracy: '±0.1mm',
        connectivity: 'USB 2.0, RS-232C',
        power: 'AC 110-240V, ≤100W',
        dimensions: '163 × 34 × 44 cm',
        weight: '35 kg',
        certification: 'CE Certified'
    };
    
    let content = 'JINKA XL-1351E Cutting Plotter - Technical Specifications\n\n';
    content += '='.repeat(60) + '\n\n';
    
    for (const [key, value] of Object.entries(specs)) {
        const label = key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
        content += `${label}: ${value}\n`;
    }
    
    content += '\n' + '='.repeat(60) + '\n';
    content += 'Generated from: ' + window.location.href + '\n';
    content += 'Date: ' + new Date().toLocaleDateString() + '\n';
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'JINKA-XL-1351E-Specifications.txt';
    a.click();
    window.URL.revokeObjectURL(url);
}

function requestQuote() {
    const message = 'Hi, I would like to request a detailed quote for the JINKA XL-1351E Cutting Plotter. Please include pricing, delivery time, installation, and training details.';
    const whatsappUrl = `https://wa.me/255753098911?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// Shopping Cart Functionality
class ShoppingCart {
    constructor() {
        this.items = [];
        this.currency = 'KES';
        this.taxRate = 0.16; // 16% VAT for Kenya
        this.loadCart();
        this.updateCartUI();
    }
    
    // Product data
    getProducts() {
        return {
            'JINKA-XL-1351E': {
                id: 'JINKA-XL-1351E',
                name: 'JINKA XL-1351E Cutting Plotter',
                price_kes: 120000,
                price_tzs: 2400000,
                image: 'images/plotter-main.jpg',
                sku: 'JINKA-XL-1351E'
            }
        };
    }
    
    // Add item to cart
    addItem(productId, quantity = 1) {
        const products = this.getProducts();
        const product = products[productId];
        
        if (!product) {
            console.error('Product not found:', productId);
            return false;
        }
        
        // Check if item already exists
        const existingItem = this.items.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                price: this.currency === 'KES' ? product.price_kes : product.price_tzs,
                image: product.image,
                sku: product.sku,
                quantity: quantity
            });
        }
        
        this.saveCart();
        this.updateCartUI();
        this.showAddedNotification(product.name);
        return true;
    }
    
    // Remove item from cart
    removeItem(productId) {
        this.items = this.items.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartUI();
    }
    
    // Update item quantity
    updateQuantity(productId, quantity) {
        const item = this.items.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeItem(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartUI();
            }
        }
    }
    
    // Clear cart
    clear() {
        this.items = [];
        this.saveCart();
        this.updateCartUI();
    }
    
    // Get cart totals
    getTotals() {
        const subtotal = this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
        const tax = subtotal * this.taxRate;
        const total = subtotal + tax;
        
        return { subtotal, tax, total };
    }
    
    // Format currency
    formatCurrency(amount) {
        const symbol = this.currency === 'KES' ? 'KES' : 'TZS';
        return `${symbol} ${amount.toLocaleString()}`;
    }
    
    // Save cart to localStorage
    saveCart() {
        localStorage.setItem('jinka_cart', JSON.stringify({
            items: this.items,
            currency: this.currency
        }));
    }
    
    // Load cart from localStorage
    loadCart() {
        const saved = localStorage.getItem('jinka_cart');
        if (saved) {
            const data = JSON.parse(saved);
            this.items = data.items || [];
            this.currency = data.currency || 'KES';
        }
    }
    
    // Update cart UI
    updateCartUI() {
        const cartCount = document.getElementById('cartCount');
        const cartItems = document.getElementById('cartItems');
        const cartEmpty = document.getElementById('cartEmpty');
        const cartFooter = document.getElementById('cartFooter');
        const cartSubtotal = document.getElementById('cartSubtotal');
        const cartTax = document.getElementById('cartTax');
        const cartTotal = document.getElementById('cartTotal');
        
        // Update cart count
        const totalItems = this.items.reduce((total, item) => total + item.quantity, 0);
        if (cartCount) {
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'block' : 'none';
        }
        
        // Update cart items
        if (cartItems && cartEmpty && cartFooter) {
            if (this.items.length === 0) {
                cartItems.style.display = 'none';
                cartEmpty.style.display = 'block';
                cartFooter.style.display = 'none';
            } else {
                cartItems.style.display = 'block';
                cartEmpty.style.display = 'none';
                cartFooter.style.display = 'block';
                
                // Render cart items
                cartItems.innerHTML = this.items.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="${item.image}" alt="${item.name}">
                        </div>
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">${this.formatCurrency(item.price)}</div>
                            <div class="cart-item-controls">
                                <div class="quantity-control">
                                    <button class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity - 1})">-</button>
                                    <input type="number" class="quantity-input" value="${item.quantity}" 
                                           onchange="cart.updateQuantity('${item.id}', parseInt(this.value))"
                                           min="1" max="99">
                                    <button class="quantity-btn" onclick="cart.updateQuantity('${item.id}', ${item.quantity + 1})">+</button>
                                </div>
                                <button class="remove-item" onclick="cart.removeItem('${item.id}')" title="Remove item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
                
                // Update totals
                const totals = this.getTotals();
                if (cartSubtotal) cartSubtotal.textContent = this.formatCurrency(totals.subtotal);
                if (cartTax) cartTax.textContent = this.formatCurrency(totals.tax);
                if (cartTotal) cartTotal.textContent = this.formatCurrency(totals.total);
            }
        }
    }
    
    // Show notification when item added
    showAddedNotification(productName) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'cart-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <span>Added ${productName} to cart</span>
            </div>
        `;
        
        // Add notification styles
        notification.style.cssText = `
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            z-index: 1001;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        notification.querySelector('.notification-content').style.cssText = `
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize cart
const cart = new ShoppingCart();

// Cart toggle function
function toggleCart() {
    const cartModal = document.getElementById('cartModal');
    cartModal.classList.toggle('active');
    
    if (cartModal.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

// Add to cart function
function addToCart(productId, quantity = 1) {
    cart.addItem(productId, quantity);
}

// Listen for hero add-to-cart
document.addEventListener('click', function(e) {
    const btn = e.target.closest && e.target.closest('#heroAddToCart');
    if (btn) {
        const productId = btn.getAttribute('data-product-id');
        cart.addItem(productId, 1);
        btn.classList.add('added');
        btn.textContent = 'Added';
        setTimeout(() => { btn.textContent = 'Add to Cart'; btn.classList.remove('added'); }, 1800);
    }
});

// Clear cart function
function clearCart() {
    if (confirm('Are you sure you want to clear your cart?')) {
        cart.clear();
    }
}

// Proceed to checkout
function proceedToCheckout() {
    if (cart.items.length === 0) {
        alert('Your cart is empty');
        return;
    }

    window.location.href = 'checkout.php';
}

// Close cart when clicking outside
document.addEventListener('click', function(e) {
    const cartModal = document.getElementById('cartModal');
    const cartToggle = document.querySelector('.cart-toggle');
    
    if (cartModal && cartModal.classList.contains('active')) {
        if (!cartModal.querySelector('.cart-content').contains(e.target) && !cartToggle.contains(e.target)) {
            toggleCart();
        }
    }
});

// Close cart with Escape key
document.addEventListener('keydown', function(e) {
    const cartModal = document.getElementById('cartModal');
    if (e.key === 'Escape' && cartModal && cartModal.classList.contains('active')) {
        toggleCart();
    }
});
