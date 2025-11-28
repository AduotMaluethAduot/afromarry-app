// Ad functionality for AfroMarry

// Initialize ads when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadAdsForCurrentPage();
});

// Load ads based on current page context
async function loadAdsForCurrentPage() {
    try {
        // Check if user is premium - premium users don't see ads
        if (typeof window.currentUser !== 'undefined' && window.currentUser) {
            // Check if user has premium status (would need to check from server)
            // For now, we'll check if ads should be hidden
            const isPremium = document.body.dataset.isPremium === 'true' || 
                             (typeof window.userPremiumStatus !== 'undefined' && window.userPremiumStatus);
            if (isPremium) {
                // Premium users don't see ads
                return;
            }
        }
        
        // Get current page context (tribe, region, etc.)
        const pageContext = getPageContext();
        
        // Load ads even without context (general ads)
        const tribeParam = pageContext?.tribe ? `&tribe=${encodeURIComponent(pageContext.tribe)}` : '';
        const regionParam = pageContext?.region ? `&region=${encodeURIComponent(pageContext.region)}` : '';
        
        // Request ads from server
        const response = await fetch(`${BASE_PATH}/actions/ads.php?limit=3${tribeParam}${regionParam}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            displayAds(data.data);
        }
    } catch (error) {
        console.error('Error loading ads:', error);
    }
}

// Get page context for targeting
function getPageContext() {
    // Check if we're on a tribe page
    const tribeElement = document.querySelector('[data-tribe-name]');
    if (tribeElement) {
        return {
            tribe: tribeElement.dataset.tribeName,
            region: tribeElement.dataset.tribeRegion
        };
    }
    
    // Check if we're on a region page
    const regionElement = document.querySelector('[data-region-name]');
    if (regionElement) {
        return {
            region: regionElement.dataset.regionName
        };
    }
    
    // Check URL for context
    const urlParams = new URLSearchParams(window.location.search);
    const tribe = urlParams.get('tribe');
    const region = urlParams.get('region');
    
    if (tribe || region) {
        return { tribe, region };
    }
    
    return null;
}

// Display ads in designated containers
function displayAds(ads) {
    // Find all ad containers
    const adContainers = document.querySelectorAll('.ad-container');
    
    if (adContainers.length === 0) {
        // Create a default ad container if none exists
        createDefaultAdContainer(ads);
        return;
    }
    
    // Distribute ads among containers
    adContainers.forEach((container, index) => {
        if (index < ads.length) {
            const ad = ads[index];
            container.innerHTML = createAdHTML(ad);
            
            // Add click tracking
            const adLink = container.querySelector('a');
            if (adLink) {
                adLink.addEventListener('click', function(e) {
                    trackAdClick(ad.id);
                });
            }
        }
    });
}

// Create HTML for an ad
function createAdHTML(ad) {
    return `
        <div class="ad-banner" data-ad-id="${ad.id}">
            <div class="ad-content">
                <div class="ad-header">
                    <span class="ad-label">Sponsored</span>
                    <h4 class="ad-title">${escapeHtml(ad.title)}</h4>
                </div>
                <div class="ad-body">
                    ${ad.image_url ? `<img src="${ad.image_url}" alt="${escapeHtml(ad.title)}" class="ad-image">` : ''}
                    <p class="ad-description">${escapeHtml(ad.description)}</p>
                </div>
                <div class="ad-footer">
                    <span class="ad-company">${escapeHtml(ad.company_name)}</span>
                    <a href="${ad.target_url}" class="ad-cta btn-primary" target="_blank">View Product</a>
                </div>
            </div>
        </div>
    `;
}

// Create a default ad container
function createDefaultAdContainer(ads) {
    // Find a suitable place to insert ads (e.g., after the first section)
    const firstSection = document.querySelector('section');
    if (firstSection) {
        const adContainer = document.createElement('div');
        adContainer.className = 'ad-container';
        adContainer.innerHTML = createAdHTML(ads[0]);
        
        // Add click tracking
        const adLink = adContainer.querySelector('a');
        if (adLink) {
            adLink.addEventListener('click', function(e) {
                trackAdClick(ads[0].id);
            });
        }
        
        firstSection.parentNode.insertBefore(adContainer, firstSection.nextSibling);
    }
}

// Track ad click
async function trackAdClick(adId) {
    try {
        const response = await fetch(`${BASE_PATH}/actions/track-ad-click.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                ad_id: adId
            })
        });
        
        // We don't need to wait for the response, tracking is fire-and-forget
    } catch (error) {
        console.error('Error tracking ad click:', error);
    }
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Add CSS for ads
const adStyles = `
    .ad-container {
        margin: 2rem 0;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .ad-banner {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .ad-header {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        background: #f8f9fa;
    }
    
    .ad-label {
        font-size: 0.75rem;
        font-weight: bold;
        color: #8B5CF6;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .ad-title {
        margin: 0.5rem 0 0;
        font-size: 1.25rem;
        color: #333;
    }
    
    .ad-body {
        padding: 1rem;
    }
    
    .ad-image {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    
    .ad-description {
        color: #666;
        margin: 0;
        line-height: 1.5;
    }
    
    .ad-footer {
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }
    
    .ad-company {
        font-weight: 500;
        color: #495057;
    }
    
    .ad-cta {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
`;

// Inject styles
const styleSheet = document.createElement("style");
styleSheet.innerText = adStyles;
document.head.appendChild(styleSheet);