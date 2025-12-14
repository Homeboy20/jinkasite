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
    
    // Add item to cart
    async addItem(productId, quantity = 1) {
        console.log('Adding to cart:', productId, quantity);
        try {
            // Add to PHP session cart via API
            const addResponse = await fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            });
            
            console.log('Add to cart API status:', addResponse.status);
            
            if (!addResponse.ok) {
                const errorData = await addResponse.json();
                throw new Error(errorData.error || `HTTP error! status: ${addResponse.status}`);
            }
            
            const addResult = await addResponse.json();
            console.log('Add to cart result:', addResult);
            
            if (!addResult.success) {
                alert(addResult.error || 'Failed to add product to cart');
                return false;
            }
            
            // Also fetch product data for localStorage sync
            const response = await fetch(`api/get_product.php?id=${productId}`);
            console.log('API Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API Response data:', data);
            
            if (!data.success || !data.product) {
                console.error('Product not found:', productId, data);
                alert('Sorry, this product is not available.');
                return false;
            }
            
            const product = data.product;
            // Add missing price fields for backward compatibility
            if (!product.price_ugx) product.price_ugx = 0;
            if (!product.price_usd) product.price_usd = 0;
            
            // Update localStorage cart for UI sync
            const existingItem = this.items.find(item => item.id == productId);
            
            if (existingItem) {
                existingItem.quantity += quantity;
                console.log('Updated existing item quantity:', existingItem);
            } else {
                // Get the price based on current currency from session/settings
                let price = parseFloat(product.price_kes) || 0;
                
                const newItem = {
                    id: product.id,
                    name: product.name,
                    price: price,
                    image: product.image || 'images/placeholder.png',
                    sku: product.sku || product.id,
                    quantity: quantity
                };
                
                this.items.push(newItem);
                console.log('Added new item to cart:', newItem);
            }
            
            this.saveCart();
            this.updateCartUI();
            this.showAddedNotification(product.name);
            console.log('Cart updated successfully');
            return true;
        } catch (error) {
            console.error('Error adding to cart:', error);
            alert('Sorry, there was an error adding this product to cart: ' + error.message);
            return false;
        }
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
        try {
            const saved = localStorage.getItem('jinka_cart');
            if (saved) {
                const data = JSON.parse(saved);
                // Validate cart items structure
                if (data.items && Array.isArray(data.items)) {
                    this.items = data.items.filter(item => {
                        // Ensure each item has required properties with correct types
                        return item.id && 
                               item.name && 
                               typeof item.price === 'number' && 
                               typeof item.quantity === 'number' &&
                               item.quantity > 0;
                    });
                }
                this.currency = data.currency || 'KES';
            }
        } catch (error) {
            console.error('Error loading cart, resetting:', error);
            // Clear corrupted cart data
            localStorage.removeItem('jinka_cart');
            this.items = [];
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
    
    // Show checkout redirect with countdown
    showCheckoutRedirect() {
        // Create redirect modal
        const modal = document.createElement('div');
        modal.className = 'checkout-redirect-modal';
        modal.innerHTML = `
            <div class="redirect-overlay"></div>
            <div class="redirect-content">
                <div class="redirect-icon">
                    <svg width="48" height="48" fill="#10b981" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h3 class="redirect-title">Item Added to Cart!</h3>
                <p class="redirect-message">Redirecting to checkout in <strong><span id="redirectCountdown">3</span>s</strong></p>
                <div class="redirect-actions">
                    <button class="btn-checkout" onclick="window.location.href='${site_url('checkout')}'">Checkout Now</button>
                    <button class="btn-continue" onclick="this.closest('.checkout-redirect-modal').remove()">Continue Shopping</button>
                </div>
            </div>
        `;
        
        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .checkout-redirect-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .redirect-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.6);
                backdrop-filter: blur(4px);
            }
            .redirect-content {
                position: relative;
                background: white;
                padding: 2rem;
                border-radius: 1rem;
                box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
                max-width: 400px;
                width: 90%;
                text-align: center;
                animation: slideUp 0.3s ease;
            }
            @keyframes slideUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .redirect-icon {
                margin: 0 auto 1rem;
                width: 64px;
                height: 64px;
                background: #d1fae5;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .redirect-title {
                font-size: 1.5rem;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 0.5rem;
            }
            .redirect-message {
                color: #6b7280;
                margin-bottom: 1.5rem;
            }
            .redirect-message strong {
                color: #1f2937;
            }
            .redirect-actions {
                display: flex;
                gap: 0.75rem;
                flex-direction: column;
            }
            .btn-checkout, .btn-continue {
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.2s;
            }
            .btn-checkout {
                background: #d32f2f;
                color: white;
            }
            .btn-checkout:hover {
                background: #b71c1c;
                transform: translateY(-1px);
            }
            .btn-continue {
                background: #f3f4f6;
                color: #6b7280;
            }
            .btn-continue:hover {
                background: #e5e7eb;
            }
        `;
        document.head.appendChild(style);
        document.body.appendChild(modal);
        
        // Countdown timer
        let countdown = 3;
        const countdownEl = document.getElementById('redirectCountdown');
        const timer = setInterval(() => {
            countdown--;
            if (countdownEl) {
                countdownEl.textContent = countdown;
            }
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = site_url('checkout');
            }
        }, 1000);
        
        // Stop countdown if user clicks continue shopping
        modal.querySelector('.btn-continue').addEventListener('click', () => {
            clearInterval(timer);
        });
        
        // Stop countdown if user clicks overlay
        modal.querySelector('.redirect-overlay').addEventListener('click', () => {
            clearInterval(timer);
            modal.remove();
        });
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
async function addToCart(productId, quantity = 1) {
    return await cart.addItem(productId, quantity);
}

// Listen for hero add-to-cart
document.addEventListener('click', async function(e) {
    // Check for hero button
    const btn = e.target.closest('#heroAddToCart');
    if (btn) {
        e.preventDefault();
        console.log('Hero button clicked');
        const productId = btn.getAttribute('data-product-id');
        console.log('Product ID:', productId);
        if (!productId) {
            console.error('No product ID found');
            return;
        }
        
        btn.disabled = true;
        btn.classList.add('loading');
        
        const success = await cart.addItem(productId, 1);
        
        if (success) {
            btn.classList.add('added');
            btn.innerHTML = '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg> Added! Redirecting...';
            // Ensure cart is saved before redirecting
            console.log('Cart items before redirect:', cart.items);
            // Redirect to cart page
            window.location.href = 'cart.php';
        } else {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
        return;
    }
    
    // Check for spec section button
    const specBtn = e.target.closest('#specAddToCart');
    if (specBtn) {
        e.preventDefault();
        console.log('Spec button clicked');
        const productId = specBtn.getAttribute('data-product-id');
        console.log('Product ID:', productId);
        if (!productId) {
            console.error('No product ID found on spec button');
            return;
        }
        
        specBtn.disabled = true;
        specBtn.classList.add('loading');
        
        const success = await cart.addItem(productId, 1);
        
        if (success) {
            specBtn.classList.add('added');
            specBtn.innerHTML = '<svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg> Added! Redirecting...';
            // Ensure cart is saved before redirecting
            console.log('Cart items before redirect:', cart.items);
            // Redirect to cart page
            window.location.href = 'cart.php';
        } else {
            specBtn.disabled = false;
            specBtn.classList.remove('loading');
        }
        return;
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
