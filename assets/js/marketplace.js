// Marketplace functionality

let marketplace = {
    cart: [],
    total: 0,
    currentCategory: 'all'
};

// Pricing constants
const SHIPPING_FEE = 2000; // USD
const TAX_RATE = 0; // set to e.g., 0.075 for 7.5%

// Initialize marketplace
function initializeMarketplace() {
    loadCart();
    setupCartEventListeners();
}

// Setup cart event listeners
function setupCartEventListeners() {
    // Add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart-btn') || e.target.closest('.add-to-cart-btn')) {
            e.preventDefault();
            const productId = e.target.dataset.productId || e.target.closest('.add-to-cart-btn').dataset.productId;
            if (productId) {
                addToCart(parseInt(productId));
            }
        }
    });
    
    // Quantity controls
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('quantity-btn')) {
            const action = e.target.dataset.action;
            const productId = parseInt(e.target.dataset.productId);
            updateCartQuantity(productId, action);
        }
    });
    
    // Remove from cart
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-from-cart-btn')) {
            const productId = parseInt(e.target.dataset.productId);
            removeFromCart(productId);
        }
    });
}

// Add to cart
async function addToCart(productId) {
    if (!currentUser) {
        const redirect = encodeURIComponent('/AfroMarry/pages/cart.php');
        window.location.href = `/AfroMarry/auth/login.php?redirect=${redirect}`;
        return;
    }
    
    try {
        const response = await fetch('/AfroMarry/actions/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: 1
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Item added to cart!', 'success');
            loadCart();
            updateCartDisplay();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showMessage('Error adding item to cart', 'error');
    }
}

// Load cart
async function loadCart() {
    if (!currentUser) return;
    
    try {
        const response = await fetch('/AfroMarry/actions/cart.php');
        const data = await response.json();
        
        if (data.success) {
            marketplace.cart = data.data.items;
            // Normalize each item price to a maximum of $10,000 when displayed/calculated client-side
            const normalizedTotal = marketplace.cart.reduce((sum, it) => {
                const unit = Math.min(parseFloat(it.price || 0), 10000);
                return sum + unit * (it.quantity || 1);
            }, 0);
            marketplace.total = normalizedTotal;
            updateCartDisplay();
        }
    } catch (error) {
        console.error('Error loading cart:', error);
    }
}

// Update cart quantity
async function updateCartQuantity(productId, action) {
    const cartItem = marketplace.cart.find(item => item.product_id === productId);
    if (!cartItem) return;
    
    let newQuantity = cartItem.quantity;
    if (action === 'increase') {
        newQuantity++;
    } else if (action === 'decrease') {
        newQuantity--;
    }
    
    if (newQuantity <= 0) {
        removeFromCart(productId);
        return;
    }
    
    try {
        const response = await fetch(`/AfroMarry/actions/cart.php/${cartItem.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                quantity: newQuantity
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadCart();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error updating cart:', error);
        showMessage('Error updating cart', 'error');
    }
}

// Remove from cart
async function removeFromCart(productId) {
    const cartItem = marketplace.cart.find(item => item.product_id === productId);
    if (!cartItem) return;
    
    try {
        const response = await fetch(`/AfroMarry/actions/cart.php/${cartItem.id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Item removed from cart', 'success');
            loadCart();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        showMessage('Error removing item from cart', 'error');
    }
}

// Update cart display
function updateCartDisplay() {
    updateCartCount();
    updateCartTotalsUI();
    
    // Update cart page if it exists
    if (document.getElementById('cart-items')) {
        displayCartItems();
    }
}

// Update cart count
function updateCartCount() {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = marketplace.cart.length;
    }
}

// Update cart total
function updateCartTotalsUI() {
    const subtotal = marketplace.total || 0;
    const tax = Math.round(subtotal * TAX_RATE);
    const total = subtotal + SHIPPING_FEE + tax;

    const elSubtotal = document.getElementById('cart-subtotal');
    if (elSubtotal) elSubtotal.textContent = `$ ${subtotal.toLocaleString()}`;

    const elTax = document.getElementById('cart-tax');
    if (elTax) elTax.textContent = `$ ${tax.toLocaleString()}`;

    const elTotal = document.getElementById('cart-total');
    if (elTotal) elTotal.textContent = `$ ${total.toLocaleString()}`;
}

// Display cart items
function displayCartItems() {
    const cartItemsContainer = document.getElementById('cart-items');
    if (!cartItemsContainer) return;
    
    if (marketplace.cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Add some beautiful cultural items to get started!</p>
                <a href="/AfroMarry/index.php#marketplace" class="btn-primary">Browse Products</a>
            </div>
        `;
        return;
    }
    
    cartItemsContainer.innerHTML = marketplace.cart.map(item => {
        const safeImg = item.image && String(item.image).trim() ? item.image : `/AfroMarry/assets/img/placeholder-product.svg`;
        return `
        <div class="cart-item">
            <img src="${safeImg}" alt="${item.name}" class="cart-item-image">
            <div class="cart-item-details">
                <h4 class="cart-item-name">${item.name}</h4>
                <p class="cart-item-price">$ ${parseFloat(item.price).toLocaleString()}</p>
                <div class="cart-item-controls">
                    <button class="quantity-btn" data-action="decrease" data-product-id="${item.product_id}">-</button>
                    <span class="quantity">${item.quantity}</span>
                    <button class="quantity-btn" data-action="increase" data-product-id="${item.product_id}">+</button>
                </div>
            </div>
            <div class="cart-item-actions">
                <button class="remove-from-cart-btn" data-product-id="${item.product_id}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `}).join('');
}

// Proceed to checkout
function proceedToCheckout() {
    if (!currentUser) {
        const redirect = encodeURIComponent('/AfroMarry/pages/cart.php');
        window.location.href = `/AfroMarry/auth/login.php?redirect=${redirect}`;
        return;
    }
    
    if (marketplace.cart.length === 0) {
        showMessage('Your cart is empty', 'error');
        return;
    }
    
    window.location.href = '/AfroMarry/pages/checkout.php';
}

// Clear cart
async function clearCart() {
    if (!currentUser) return;
    
    try {
        // Remove all items from cart
        for (const item of marketplace.cart) {
            await fetch(`actions/cart.php/${item.id}`, {
                method: 'DELETE'
            });
        }
        
        loadCart();
        showMessage('Cart cleared', 'success');
    } catch (error) {
        console.error('Error clearing cart:', error);
        showMessage('Error clearing cart', 'error');
    }
}

// Apply discount code
function applyDiscountCode() {
    const discountInput = document.getElementById('discount-code');
    const discountCode = discountInput.value.trim();
    
    if (!discountCode) {
        showMessage('Please enter a discount code', 'error');
        return;
    }
    
    // This would typically validate the discount code with the server
    const validCodes = {
        'WELCOME10': 0.1,
        'CULTURE15': 0.15,
        'TRADITION20': 0.2
    };
    
    if (validCodes[discountCode]) {
        const discount = validCodes[discountCode];
        const discountAmount = marketplace.total * discount;
        const newTotal = marketplace.total - discountAmount;
        
        showMessage(`Discount applied! You saved $ ${discountAmount.toLocaleString()}`, 'success');
        
        // Update total display
        const cartTotalElement = document.getElementById('cart-total');
        if (cartTotalElement) {
            cartTotalElement.innerHTML = `
                <span class="original-price">$ ${marketplace.total.toLocaleString()}</span>
                <span class="discounted-price">$ ${newTotal.toLocaleString()}</span>
                <span class="discount-amount">-$ ${discountAmount.toLocaleString()}</span>
            `;
        }
    } else {
        showMessage('Invalid discount code', 'error');
    }
}

// Search products
function searchProducts(query) {
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        const name = product.querySelector('.product-name').textContent.toLowerCase();
        const description = product.querySelector('.product-description').textContent.toLowerCase();
        const category = product.querySelector('.product-category').textContent.toLowerCase();
        
        if (name.includes(query.toLowerCase()) || 
            description.includes(query.toLowerCase()) || 
            category.includes(query.toLowerCase())) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// Sort products
function sortProducts(sortBy) {
    const productsGrid = document.getElementById('products-grid');
    const products = Array.from(productsGrid.children);
    
    products.sort((a, b) => {
        switch (sortBy) {
            case 'price-low':
                const priceA = parseFloat(a.querySelector('.product-price').textContent.replace(/[^\d]/g, ''));
                const priceB = parseFloat(b.querySelector('.product-price').textContent.replace(/[^\d]/g, ''));
                return priceA - priceB;
            case 'price-high':
                const priceA2 = parseFloat(a.querySelector('.product-price').textContent.replace(/[^\d]/g, ''));
                const priceB2 = parseFloat(b.querySelector('.product-price').textContent.replace(/[^\d]/g, ''));
                return priceB2 - priceA2;
            case 'name':
                const nameA = a.querySelector('.product-name').textContent;
                const nameB = b.querySelector('.product-name').textContent;
                return nameA.localeCompare(nameB);
            default:
                return 0;
        }
    });
    
    products.forEach(product => productsGrid.appendChild(product));
}

// Initialize marketplace when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeMarketplace();
});
