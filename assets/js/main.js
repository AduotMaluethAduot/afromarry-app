// Main JavaScript functionality for AfroMarry

// Global variables
let currentUser = null;
let cartCount = 0;

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    loadInitialData();
    // Safely handle any autoplay media to avoid NotSupportedError
    try {
        const mediaEls = document.querySelectorAll('audio, video');
        mediaEls.forEach(el => {
            if (el.autoplay) {
                el.muted = true; // many browsers require muted to autoplay
                const p = el.play();
                if (p && typeof p.catch === 'function') {
                    p.catch(err => console.debug('Autoplay prevented:', err));
                }
            }
        });
    } catch (e) {
        console.debug('Media autoplay guard error:', e);
    }
});

// Initialize application
function initializeApp() {
    // Check if user is logged in
    checkAuthStatus();
    
    // Setup mobile navigation
    setupMobileNav();
    
    // Initialize smooth scrolling
    setupSmoothScrolling();
    
    // Load cart count
    loadCartCount();
}

// Require auth or redirect to login
function requireAuthOrRedirect() {
    if (!currentUser) {
        const redirect = encodeURIComponent(window.location.pathname + window.location.search + window.location.hash);
        window.location.href = `/AfroMarry/auth/login.php?redirect=${redirect}`;
        return false;
    }
    return true;
}

// Setup event listeners
function setupEventListeners() {
    // Search functionality
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', handleSearch);
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleSearch();
            }
        });
    }
    
    // Region cards
    const regionCards = document.querySelectorAll('.region-card');
    regionCards.forEach(card => {
        card.addEventListener('click', function() {
            const region = this.dataset.region;
            filterTribesByRegion(region);
        });
    });
    
    // Category filters
    const filterBtns = document.querySelectorAll('.filter-btn');
    if (filterBtns.length === 0) {
        console.warn('No filter buttons found');
    } else {
        console.log('Found', filterBtns.length, 'filter buttons');
    }
    filterBtns.forEach((btn, index) => {
        const category = btn.dataset.category || btn.getAttribute('data-category');
        console.log(`Filter button ${index}: category = "${category}"`);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const category = this.dataset.category || this.getAttribute('data-category');
            console.log('Filter button clicked, category:', category);
            if (category) {
                filterProductsByCategory(category);
                
                // Update active state
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            } else {
                console.error('Filter button missing category attribute');
            }
        });
    });
}

// Setup mobile navigation
function setupMobileNav() {
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
}

// Setup smooth scrolling
function setupSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Load initial data
async function loadInitialData() {
    try {
        await Promise.all([
            loadTribes(),
            loadProducts(),
            loadExperts()
        ]);
    } catch (error) {
        console.error('Error loading initial data:', error);
        showMessage('Error loading data. Please refresh the page.', 'error');
    }
}

// Load tribes
async function loadTribes() {
    try {
        const response = await fetch(actionUrl('tribes.php'));
        const data = await response.json();
        
        let allTribes = [];
        
        if (data.success && data.data && data.data.length > 0) {
            // Merge database tribes with sample tribes (avoid duplicates)
            allTribes = [...data.data];
            
            // Add sample tribes if they don't exist in database
            if (window.sampleTribes && Array.isArray(window.sampleTribes)) {
                const dbTribeNames = new Set(data.data.map(t => `${t.name}_${t.country}`));
                window.sampleTribes.forEach(sampleTribe => {
                    const key = `${sampleTribe.name}_${sampleTribe.country}`;
                    if (!dbTribeNames.has(key)) {
                        allTribes.push(sampleTribe);
                    }
                });
            }
        } else {
            // Use sample tribes as fallback if database is empty
            allTribes = window.sampleTribes || [];
            if (data.data && data.data.length > 0) {
                allTribes = [...data.data];
            }
        }
        
        console.log('[Tribes] Loaded', allTribes.length, '(DB:', data.success ? data.data?.length || 0 : 0, 'Sample:', window.sampleTribes?.length || 0, ')');
        displayTribes(allTribes);
    } catch (error) {
        console.error('Error loading tribes:', error);
        // Fallback to sample tribes on error
        if (window.sampleTribes && Array.isArray(window.sampleTribes)) {
            console.log('[Tribes] Using sample data as fallback');
            displayTribes(window.sampleTribes);
        } else {
            showMessage('Error loading tribes', 'error');
        }
    }
}

// Display tribes
function displayTribes(tribes) {
    const tribesGrid = document.getElementById('tribes-grid');
    if (!tribesGrid) return;
    
    // Empty state
    if (!tribes || tribes.length === 0) {
        tribesGrid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:2rem; color:#6b7280;">
                <i class="fas fa-info-circle" style="font-size:2rem; color:#9ca3af;"></i>
                <h3 style="color:#374151; margin-top:.5rem;">No tribes found</h3>
                <p>Try another region or clear your filters.</p>
            </div>
        `;
        return;
    }
    tribesGrid.innerHTML = '';
    
    tribes.forEach(tribe => {
        const tribeCard = createTribeCard(tribe);
        tribesGrid.appendChild(tribeCard);
    });
}

// Create tribe card
function createTribeCard(tribe) {
    const card = document.createElement('div');
    card.className = 'tribe-card';
    const customsArr = Array.isArray(tribe.customs) ? tribe.customs : [];
    const imageSrc = tribe.image && String(tribe.image).trim()
        ? tribe.image
        : generatePlaceholderImage(tribe.name || 'Tribe', tribe.region || 'Africa', 400, 250);
    const placeholderTribeImg = generatePlaceholderImage(tribe.name || 'Tribe', tribe.region || 'Africa', 400, 250);
    card.innerHTML = `
        <img src="${imageSrc}" alt="${tribe.name}" class="tribe-image" onerror="this.onerror=null; this.src='${placeholderTribeImg}';">
        <div class="tribe-content">
            <h3 class="tribe-name">${tribe.name}</h3>
            <p class="tribe-location">${tribe.country}, ${tribe.region}</p>
            <div class="tribe-dowry">
                <div class="tribe-dowry-type">${tribe.dowry_type}</div>
                <div class="tribe-dowry-details">${tribe.dowry_details}</div>
            </div>
            <div class="tribe-customs">
                <h4>Key Customs:</h4>
                <ul class="customs-list">
                    ${customsArr.slice(0, 3).map(custom => `<li>${custom}</li>`).join('')}
                </ul>
            </div>
        </div>
    `;
    
    return card;
}

// Generate a lightweight SVG placeholder with title/region
function generatePlaceholderImage(title, subtitle, width = 400, height = 250) {
    const bg = '#f3f4f6';
    const fg = '#6b7280';
    const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns='http://www.w3.org/2000/svg' width='${width}' height='${height}' viewBox='0 0 ${width} ${height}'>
  <defs>
    <linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>
      <stop offset='0%' stop-color='#ffffff'/>
      <stop offset='100%' stop-color='${bg}'/>
    </linearGradient>
  </defs>
  <rect width='100%' height='100%' fill='url(#g)'/>
  <g fill='${fg}' font-family='Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica Neue, Arial, "Apple Color Emoji", "Segoe UI Emoji"' text-anchor='middle'>
    <text x='50%' y='45%' font-size='20' font-weight='600'>${escapeSvg(title)}</text>
    <text x='50%' y='60%' font-size='14' opacity='0.8'>${escapeSvg(subtitle)}</text>
  </g>
</svg>`;
    return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
}

function escapeSvg(text) {
    return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Load products
async function loadProducts() {
    console.log('[Marketplace] Loading products...');
    try {
        const url = actionUrl('products.php');
        console.log('[Marketplace] Fetching from:', url);
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[Marketplace] Products loaded:', data);
        
        if (data.success) {
            const products = data.data || [];
            console.log(`[Marketplace] Displaying ${products.length} products`);
            displayProducts(products);
        } else {
            throw new Error(data.message || 'Failed to load products');
        }
    } catch (error) {
        console.error('[Marketplace] Error loading products:', error);
        
        // Show empty state on error
        const productsGrid = document.getElementById('products-grid');
        if (productsGrid) {
            productsGrid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:3rem; color:#6b7280;">
                    <i class="fas fa-shopping-bag" style="font-size:3rem; color:#9ca3af; margin-bottom:1rem;"></i>
                    <h3 style="color:#374151; margin-bottom:0.5rem;">No products available</h3>
                    <p>Products will appear here once they're added to the marketplace.</p>
                    <p style="color:#9ca3af; font-size:0.9rem; margin-top:0.5rem;">Error: ${error.message}</p>
                    <button onclick="loadProducts()" class="btn-primary" style="margin-top:1rem;">Retry</button>
                </div>
            `;
        }
    }
}

// Display products
function displayProducts(products) {
    const productsGrid = document.getElementById('products-grid');
    if (!productsGrid) return;
    
    productsGrid.innerHTML = '';
    
    if (!products || products.length === 0) {
        productsGrid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:3rem; color:#6b7280;">
                <i class="fas fa-shopping-bag" style="font-size:3rem; color:#9ca3af; margin-bottom:1rem;"></i>
                <h3 style="color:#374151; margin-bottom:0.5rem;">No products found</h3>
                <p>Products will appear here once they're added to the marketplace.</p>
                ${(typeof window.currentUser !== 'undefined' && window.currentUser) ? '<p style="margin-top:1rem;"><a href="' + adminUrl('products.php') + '" class="btn-primary">Add Products (Admin)</a></p>' : ''}
            </div>
        `;
        return;
    }
    
    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
}

// Create product card
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    const productImg = product.image && String(product.image).trim()
        ? product.image
        : generatePlaceholderImage(product.name || 'Product', product.category || 'Marketplace', 400, 300);
    const normalizedPrice = Math.min(parseFloat(product.price || 0), 10000);
    card.innerHTML = `
        <img src="${productImg}" alt="${product.name}" class="product-image" onerror="this.onerror=null; this.src='${generatePlaceholderImage(product.name || 'Product', product.category || 'Marketplace', 400, 300)}';">
        <div class="product-content">
            <h3 class="product-name">${product.name}</h3>
            <div class="product-price">$ ${normalizedPrice.toLocaleString()}</div>
            <span class="product-category">${product.category}</span>
            <p class="product-description">${product.description}</p>
            <button class="add-to-cart-btn" onclick="addToCart(${product.id})">
                <i class="fas fa-shopping-cart"></i> Add to Cart
            </button>
        </div>
    `;
    
    return card;
}

// Load experts
async function loadExperts() {
    try {
        const response = await fetch('actions/experts.php');
        const data = await response.json();
        
        if (data.success) {
            displayExperts(data.data);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading experts:', error);
        showMessage('Error loading experts', 'error');
    }
}

// Display experts
function displayExperts(experts) {
    const expertsList = document.getElementById('experts-list');
    if (!expertsList) return;
    
    expertsList.innerHTML = '';
    
    experts.forEach(expert => {
        const expertCard = createExpertCard(expert);
        expertsList.appendChild(expertCard);
    });
}

// Store experts data globally for booking
window.expertsData = window.expertsData || {};

// Create expert card
function createExpertCard(expert) {
    const card = document.createElement('div');
    card.className = 'expert-card';
    const displayRate = parseFloat(expert.hourly_rate || 150);
    
    // Store expert data for booking
    window.expertsData[expert.id] = {
        id: expert.id,
        name: expert.name,
        tribe: expert.tribe,
        hourlyRate: displayRate,
        specialization: expert.specialization
    };
    
    const expertImg = expert.image && String(expert.image).trim() 
        ? expert.image 
        : assetUrl('img/placeholder-product.svg');
    const placeholderImg = generatePlaceholderImage(expert.name || 'Expert', expert.tribe || 'Cultural Expert', 400, 300);
    
    card.innerHTML = `
        <img src="${expertImg}" alt="${expert.name}" class="expert-image" onerror="this.onerror=null; this.src='${placeholderImg}';">
        <div class="expert-info">
            <h3 class="expert-name">${expert.name}</h3>
            <p class="expert-tribe">${expert.tribe} Expert</p>
            <p class="expert-specialization">${expert.specialization}</p>
            <div class="expert-languages">
                ${(expert.languages || []).map(lang => `<span>${lang}</span>`).join('')}
            </div>
            <div class="expert-rating">
                <div class="stars">
                    ${'★'.repeat(Math.floor(expert.rating || 0))}${'☆'.repeat(5 - Math.floor(expert.rating || 0))}
                </div>
                <span>${expert.rating || 0}/5</span>
            </div>
            <p class="expert-availability">${expert.availability || 'Check availability'}</p>
            <p class="expert-rate">$ ${displayRate.toLocaleString()}/hour</p>
            <button type="button" class="book-btn" onclick="bookExpert(${expert.id})" data-expert-id="${expert.id}">Book Consultation</button>
        </div>
    `;
    
    return card;
}

// Search functionality
function handleSearch() {
    const searchInput = document.getElementById('search-input');
    const query = searchInput.value.trim();
    
    if (query) {
        searchTribes(query);
        scrollToSection('tribes');
    }
}

// Search tribes
async function searchTribes(query) {
    try {
            const response = await fetch(actionUrl(`tribes.php?search=${encodeURIComponent(query)}`));
        const data = await response.json();
        
        if (data.success) {
            displayTribes(data.data);
            updateSearchResults(query);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error searching tribes:', error);
        showMessage('Error searching tribes', 'error');
    }
}

// Update search results display
function updateSearchResults(query) {
    const sectionTitle = document.querySelector('#tribes .section-title');
    const sectionSubtitle = document.querySelector('#tribes .section-subtitle');
    
    if (sectionTitle && sectionSubtitle) {
        sectionTitle.innerHTML = `Search Results for <span class="highlight">"${query}"</span>`;
        sectionSubtitle.textContent = 'Explore the traditions that match your search';
    }
}

// Filter tribes by region
async function filterTribesByRegion(region) {
    try {
        // Map UI keys to database region names
        const regionMap = {
            east: 'East Africa',
            west: 'West Africa',
            southern: 'Southern Africa',
            north: 'North Africa',
            central: 'Central Africa'
        };
        const dbRegion = regionMap[region] || region;
        
        let filteredTribes = [];
        
        // Try to fetch from database
        try {
            const response = await fetch(actionUrl(`tribes.php?region=${encodeURIComponent(dbRegion)}`));
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                filteredTribes = [...data.data];
            }
        } catch (dbError) {
            console.warn('Database fetch failed, using sample data:', dbError);
        }
        
        // Merge with sample tribes for this region
        if (window.sampleTribes && Array.isArray(window.sampleTribes)) {
            const sampleForRegion = window.sampleTribes.filter(t => t.region === dbRegion);
            if (sampleForRegion.length > 0) {
                const existingNames = new Set(filteredTribes.map(t => `${t.name}_${t.country}`));
                sampleForRegion.forEach(sampleTribe => {
                    const key = `${sampleTribe.name}_${sampleTribe.country}`;
                    if (!existingNames.has(key)) {
                        filteredTribes.push(sampleTribe);
                    }
                });
            }
        }
        
        if (filteredTribes.length > 0) {
            displayTribes(filteredTribes);
            scrollToSection('tribes');
        } else {
            const tribesGrid = document.getElementById('tribes-grid');
            if (tribesGrid) {
                tribesGrid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:2rem; color:#6b7280;">
                        <i class="fas fa-info-circle" style="font-size:2rem; color:#9ca3af;"></i>
                        <h3 style="color:#374151; margin-top:.5rem;">No tribes found for this region</h3>
                        <p>Try selecting another region or use the search to find tribes.</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error filtering tribes:', error);
        const tribesGrid = document.getElementById('tribes-grid');
        if (tribesGrid) {
            tribesGrid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:2rem; color:#6b7280;">
                    <i class="fas fa-exclamation-triangle" style="font-size:2rem; color:#ef4444;"></i>
                    <h3 style="color:#374151; margin-top:.5rem;">Unable to load tribes</h3>
                    <p>Please try again.</p>
                </div>
            `;
        }
        showMessage('Error filtering tribes', 'error');
    }
}

// Filter products by category
async function filterProductsByCategory(category) {
    console.log('[Marketplace] Filtering by category:', category);
    try {
        const url = category === 'all' ? actionUrl('products.php') : actionUrl(`products.php?category=${encodeURIComponent(category)}`);
        console.log('[Marketplace] Fetching from:', url);
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('[Marketplace] Response:', data);
        
        if (data.success) {
            const products = data.data || [];
            console.log(`[Marketplace] Displaying ${products.length} products`);
            displayProducts(products);
            
            // Show message if no products found for category
            if (products.length === 0 && category !== 'all') {
                showMessage(`No products found in ${category} category`, 'info');
            }
        } else {
            throw new Error(data.message || 'Failed to filter products');
        }
    } catch (error) {
        console.error('[Marketplace] Error filtering products:', error);
        showMessage('Error filtering products: ' + error.message, 'error');
        
        // Show empty state
        const productsGrid = document.getElementById('products-grid');
        if (productsGrid) {
            productsGrid.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1; text-align:center; padding:3rem; color:#6b7280;">
                    <i class="fas fa-exclamation-triangle" style="font-size:3rem; color:#ef4444; margin-bottom:1rem;"></i>
                    <h3 style="color:#374151; margin-bottom:0.5rem;">Unable to load products</h3>
                    <p>${error.message}</p>
                    <button onclick="loadProducts()" class="btn-primary" style="margin-top:1rem;">Retry</button>
                </div>
            `;
        }
    }
}

// Add to cart
async function addToCart(productId) {
    if (!currentUser) {
        showSignupModal();
        return;
    }
    
    try {
        const response = await fetch(actionUrl('cart.php'), {
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
            loadCartCount();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showMessage('Error adding item to cart', 'error');
    }
}

// Load cart count
async function loadCartCount() {
    if (!currentUser) return;
    
    try {
        const response = await fetch(actionUrl('cart.php'));
        const data = await response.json();
        
        if (data.success) {
            cartCount = data.data.count;
            updateCartDisplay();
        }
    } catch (error) {
        console.error('Error loading cart count:', error);
    }
}

// Update cart display
function updateCartDisplay() {
    const cartCountElement = document.getElementById('cart-count');
    if (cartCountElement) {
        cartCountElement.textContent = cartCount;
    }
}

// Check authentication status
function checkAuthStatus() {
    // Determined by server-rendered inline script on each PHP page
    // Fallback to false if not provided
    if (typeof window !== 'undefined' && typeof window.currentUser !== 'undefined') {
        currentUser = !!window.currentUser;
    } else {
        currentUser = false;
    }
}

// Modal functions
function showSignupModal() {
    const modal = document.getElementById('signup-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Scroll to section
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Show message
function showMessage(message, type = 'info') {
    // Create message element
    const messageEl = document.createElement('div');
    messageEl.className = `message ${type}`;
    messageEl.textContent = message;
    
    // Add to page
    document.body.appendChild(messageEl);
    
    // Remove after 5 seconds
    setTimeout(() => {
        messageEl.remove();
    }, 5000);
}

// Explore tribe details
function exploreTribe(tribeId) {
    // This would open a detailed view of the tribe
    // Tribe details page would be implemented here
    window.location.href = pageUrl(`tribe-details.php?id=${tribeId}`);
}

// Book expert consultation
function bookExpert(expertId) {
    if (!requireAuthOrRedirect()) return;
    
    // Get expert data from stored global object
    const expertData = window.expertsData && window.expertsData[expertId];
    
    if (expertData) {
        showBookingModalFromMain(expertData);
    } else {
        // Fallback: try to get from DOM
        const expertCard = document.querySelector(`[data-expert-id="${expertId}"]`)?.closest('.expert-card');
        if (expertCard) {
            const name = expertCard.querySelector('.expert-name')?.textContent?.trim() || '';
            const tribe = expertCard.querySelector('.expert-tribe')?.textContent?.trim() || '';
            const rate = expertCard.querySelector('.expert-rate')?.textContent?.trim() || '';
            const expertData = { 
                id: expertId, 
                name, 
                tribe: tribe.replace(' Expert', ''), 
                hourlyRate: rate 
            };
            showBookingModalFromMain(expertData);
        } else {
            showMessage('Expert data not found. Please refresh the page.', 'error');
        }
    }
}

// Show booking modal (fallback if experts.js not loaded)
function showBookingModalFromMain(expert) {
    const modal = document.getElementById('booking-modal');
    if (!modal) return;
    
    const formContainer = document.getElementById('booking-form');
    if (!formContainer) return;
    
    // Create simple booking form
    formContainer.innerHTML = `
        <div class="expert-booking-info" style="margin-bottom:1.5rem;padding:1rem;background:#f8fafc;border-radius:10px;">
            <h4 style="margin:0 0 0.5rem 0;color:#1f2937;">Booking with ${expert.name}</h4>
            <p style="margin:0;color:#6b7280;">${expert.tribe}</p>
            <p style="margin:0.5rem 0 0 0;color:#374151;font-weight:600;">Rate: $ ${typeof expert.hourlyRate === 'number' ? expert.hourlyRate.toLocaleString() : expert.hourlyRate}/hour</p>
        </div>
        
        <form id="booking-form-submit" style="display:grid;gap:1rem;">
            <div class="form-group">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Preferred Date</label>
                <input type="date" name="booking_date" required style="width:100%;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;">
            </div>
            
            <div class="form-group">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Preferred Time</label>
                <select name="booking_time" required style="width:100%;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;">
                    <option value="">Select time</option>
                    <option value="09:00">9:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="12:00">12:00 PM</option>
                    <option value="14:00">2:00 PM</option>
                    <option value="15:00">3:00 PM</option>
                    <option value="16:00">4:00 PM</option>
                    <option value="17:00">5:00 PM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Duration (hours)</label>
                <select name="duration" id="booking-duration" required style="width:100%;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;">
                    <option value="1">1 hour</option>
                    <option value="2">2 hours</option>
                    <option value="3">3 hours</option>
                </select>
            </div>
            
            <div class="form-group">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Consultation Type</label>
                <select name="consultation_type" required style="width:100%;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;">
                    <option value="video">Video Call</option>
                    <option value="phone">Phone Call</option>
                    <option value="in-person">In-Person (if available)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Additional Notes</label>
                <textarea name="notes" rows="3" placeholder="Tell us about your specific needs or questions..." style="width:100%;padding:0.75rem;border:2px solid #e5e7eb;border-radius:8px;font-size:1rem;resize:vertical;"></textarea>
            </div>
            
            <div class="booking-summary" style="margin:1rem 0;padding:1rem;background:#f0f9ff;border-radius:10px;">
                <h4 style="margin:0 0 1rem 0;color:#1e40af;">Booking Summary</h4>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                    <span style="color:#6b7280;">Expert:</span>
                    <span style="font-weight:600;color:#1f2937;">${expert.name}</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
                    <span style="color:#6b7280;">Duration:</span>
                    <span id="summary-duration" style="font-weight:600;color:#1f2937;">1 hour</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:0.5rem;border-top:2px solid #bfdbfe;font-weight:600;font-size:1.1rem;color:#1e40af;">
                    <span>Total:</span>
                    <span id="summary-total" style="color:#1e40af;">$ ${(typeof expert.hourlyRate === 'number' ? expert.hourlyRate : parseFloat(String(expert.hourlyRate).replace(/[^\d]/g, '') || 150)).toLocaleString()}</span>
                </div>
            </div>
            
            <button type="submit" class="btn-primary btn-large" style="background:linear-gradient(135deg, #8B5CF6, #EC4899);color:white;padding:1rem 2rem;border:none;border-radius:10px;font-size:1rem;font-weight:600;cursor:pointer;width:100%;">
                Confirm Booking
            </button>
        </form>
    `;
    
    // Update total on duration change
    const durationSelect = document.getElementById('booking-duration');
    const hourlyRate = typeof expert.hourlyRate === 'number' 
        ? expert.hourlyRate 
        : parseFloat(String(expert.hourlyRate).replace(/[^\d]/g, '') || 150);
    
    if (durationSelect) {
        durationSelect.addEventListener('change', function() {
            const duration = parseInt(this.value);
            const total = hourlyRate * duration;
            const summaryDuration = document.getElementById('summary-duration');
            const summaryTotal = document.getElementById('summary-total');
            
            if (summaryDuration) {
                summaryDuration.textContent = `${duration} hour${duration > 1 ? 's' : ''}`;
            }
            if (summaryTotal) {
                summaryTotal.textContent = `$ ${total.toLocaleString()}`;
            }
        });
    }
    
    // Handle form submission
    const form = document.getElementById('booking-form-submit');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const bookingData = {
                expert_id: expert.id,
                booking_date: formData.get('booking_date'),
                booking_time: formData.get('booking_time'),
                duration_hours: parseInt(formData.get('duration')),
                consultation_type: formData.get('consultation_type'),
                notes: formData.get('notes') || ''
            };
            
            try {
                const response = await fetch(actionUrl('expert-bookings.php'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(bookingData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Booking confirmed! You will receive a confirmation email shortly.', 'success');
                    closeModal('booking-modal');
                    // Redirect to bookings page or dashboard
                    setTimeout(() => {
                        window.location.href = pageUrl('bookings.php');
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Booking failed');
                }
            } catch (error) {
                console.error('Error submitting booking:', error);
                showMessage('Error submitting booking: ' + error.message, 'error');
            }
        });
    }
    
    modal.style.display = 'block';
}

// Show upgrade modal
function showUpgradeModal() {
    const modal = document.getElementById('upgrade-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Proceed to checkout
function proceedToCheckout(type, plan = 'monthly') {
    if (!requireAuthOrRedirect()) return;
    // type can be 'premium', plan can be 'monthly' or 'annual'
    window.location.href = `pages/checkout.php?type=${type}&plan=${plan}`;
}

// Open dowry calculator
function openDowryCalculator() {
    if (!requireAuthOrRedirect()) return;
    const modal = document.getElementById('dowry-modal');
    if (modal) {
        modal.style.display = 'block';
        loadDowryCalculator();
    }
}

// Tools navigation helpers
window.goToPlanner = function () {
    if (!requireAuthOrRedirect()) return;
    window.location.href = '/AfroMarry/pages/planner.php';
};

window.goToCustomGuide = function () {
    if (!requireAuthOrRedirect()) return;
    window.location.href = '/AfroMarry/pages/custom-guide.php';
};
