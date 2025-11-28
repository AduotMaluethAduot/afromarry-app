<?php
require_once 'config/database.php';

// Redirect logged-in users to their appropriate dashboard
// This prevents authenticated users from accessing the landing page
if (isLoggedIn()) {
    if (isAdmin()) {
        // Admin users go to admin dashboard
        redirect(admin_url('dashboard.php'));
    } else {
        // Regular users go to user dashboard
        redirect(page_url('dashboard.php'));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AfroMarry - Celebrate Love Across African Traditions</title>
    <link rel="stylesheet" href="./assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="index.php">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu" id="nav-menu">
                <a href="#tribes" class="nav-link">Tribes</a>
                <a href="#marketplace" class="nav-link">Marketplace</a>
                <a href="#experts" class="nav-link">Experts</a>
                <a href="#tools" class="nav-link">Tools</a>
                <a href="pages/cultural-articles.php" class="nav-link">Articles</a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin/dashboard.php" class="nav-link">Admin Panel</a>
                    <?php endif; ?>
                    <a href="pages/dashboard.php" class="nav-link">Dashboard</a>
                    <a href="pages/notifications.php" class="nav-link">
                        <i class="fas fa-bell"></i>
                        Notifications
                    </a>
                    <a href="pages/cart.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        Cart <span id="cart-count">0</span>
                    </a>
                    <a href="auth/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link">Login</a>
                    <a href="auth/register.php" class="nav-link btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
            <div class="nav-toggle" id="nav-toggle">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">
                Celebrate Love Across <span class="highlight">African Traditions</span>
            </h1>
            <p class="hero-subtitle">
                Discover your partner's tribal marriage customs, dowry practices, and ceremonial traditions from all 54 African countries
            </p>
            
            <div class="search-container">
                <div class="search-box">
                    <input type="text" id="search-input" placeholder="Search by tribe, country, or tradition...">
                    <button id="search-btn" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="hero-buttons">
                <button class="btn-primary btn-large" onclick="showSignupModal()">
                    Get Started Free
                </button>
                <button class="btn-secondary btn-large" onclick="scrollToSection('regions')">
                    Explore Regions
                </button>
            </div>
            
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">54</span>
                    <span class="stat-label">Countries</span>
                </div>
                <div class="stat">
                    <span class="stat-number">810+</span>
                    <span class="stat-label">Tribes</span>
                </div>
                <div class="stat">
                    <span class="stat-number">3000+</span>
                    <span class="stat-label">Customs</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Regions Section -->
    <section id="regions" class="regions-section">
        <div class="container">
            <h2 class="section-title">Explore <span class="highlight">African Regions</span></h2>
            <div class="regions-grid">
                <div class="region-card" data-region="east">
                    <div class="region-icon">
                        <i class="fas fa-mountain"></i>
                    </div>
                    <h3>East Africa</h3>
                    <p>Kenya, Tanzania, Uganda, Rwanda, Ethiopia</p>
                </div>
                <div class="region-card" data-region="west">
                    <div class="region-icon">
                        <i class="fas fa-tree"></i>
                    </div>
                    <h3>West Africa</h3>
                    <p>Nigeria, Ghana, Senegal, Mali, Ivory Coast</p>
                </div>
                <div class="region-card" data-region="southern">
                    <div class="region-icon">
                        <i class="fas fa-globe-africa"></i>
                    </div>
                    <h3>Southern Africa</h3>
                    <p>South Africa, Zimbabwe, Botswana, Namibia</p>
                </div>
                <div class="region-card" data-region="north">
                    <div class="region-icon">
                        <i class="fas fa-mosque"></i>
                    </div>
                    <h3>North Africa</h3>
                    <p>Egypt, Morocco, Algeria, Tunisia, Libya</p>
                </div>
                <div class="region-card" data-region="central">
                    <div class="region-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Central Africa</h3>
                    <p>Cameroon, DRC, Congo, Chad, CAR</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tribes Section -->
    <section id="tribes" class="tribes-section">
        <div class="container">
            <h2 class="section-title">Featured <span class="highlight">Tribes</span></h2>
            <p class="section-subtitle">Explore marriage traditions from across Africa</p>
            <div id="tribes-grid" class="tribes-grid">
                <!-- Tribes will be loaded here via JavaScript -->
            </div>
        </div>
    </section>

    <!-- Tools Section -->
    <section id="tools" class="tools-section">
        <div class="container">
            <h2 class="section-title">Cultural <span class="highlight">Tools</span></h2>
            <div class="tools-grid">
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Dowry Calculator</h3>
                    <p>Calculate estimated dowry amounts based on family size, tradition level, and region</p>
                    <button class="btn-primary" onclick="openDowryCalculator()">Calculate Now</button>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>Tribe Discovery Quiz</h3>
                    <p>Discover which African tribe matches your partner's background</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo page_url('quiz.php'); ?>" class="btn-primary">Take Quiz</a>
                    <?php else: ?>
                        <button class="btn-primary" onclick="showSignupModal()">Take Quiz</button>
                    <?php endif; ?>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Compatibility Matching</h3>
                    <p>Check compatibility between different tribes and get fusion recommendations</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo page_url('compatibility-match.php'); ?>" class="btn-primary">Check Compatibility</a>
                    <?php else: ?>
                        <button class="btn-primary" onclick="showSignupModal()">Check Compatibility</button>
                    <?php endif; ?>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Wedding Timeline</h3>
                    <p>Plan your traditional wedding with our comprehensive timeline and checklist</p>
                    <?php if (isLoggedIn()): ?>
                        <a href="<?php echo page_url('timeline.php'); ?>" class="btn-primary">Start Planning</a>
                    <?php else: ?>
                        <button class="btn-primary" onclick="showSignupModal()">Start Planning</button>
                    <?php endif; ?>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>AI Chatbot</h3>
                    <p>Get instant answers about African marriage traditions and customs</p>
                    <a href="<?php echo page_url('chatbot.php'); ?>" class="btn-primary">Chat Now</a>
                </div>
                <div class="tool-card">
                    <div class="tool-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3>Custom Guide</h3>
                    <p>Get personalized guides for your specific tribal traditions and customs</p>
                    <button class="btn-primary" onclick="goToCustomGuide()">Get Guide</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Marketplace Section -->
    <section id="marketplace" class="marketplace-section">
        <div class="container">
            <h2 class="section-title">Cultural <span class="highlight">Marketplace</span></h2>
            <p class="section-subtitle">Authentic wedding items from trusted suppliers across Africa</p>
            
            <?php if (!isLoggedIn() || !isPremium()): ?>
            <!-- Ad Container for Free Users -->
            <div class="ad-container" style="margin-bottom: 2rem;"></div>
            <?php endif; ?>
            
            <div class="category-filters">
                <button class="filter-btn active" data-category="all">All</button>
                <button class="filter-btn" data-category="fabrics">Fabrics</button>
                <button class="filter-btn" data-category="jewelry">Jewelry</button>
                <button class="filter-btn" data-category="ceremonial">Ceremonial</button>
                <button class="filter-btn" data-category="attire">Attire</button>
            </div>
            
            <div id="products-grid" class="products-grid">
                <!-- Products will be loaded here via JavaScript -->
            </div>
        </div>
    </section>

    <!-- Experts Section -->
    <section id="experts" class="experts-section">
        <div class="container">
            <h2 class="section-title">Consult <span class="highlight">Cultural Experts</span></h2>
            <p class="section-subtitle">Book video consultations with elders and specialists who understand your partner's traditions</p>
            <div id="experts-list" class="experts-list">
                <!-- Experts will be loaded here via JavaScript -->
            </div>
        </div>
    </section>

    <!-- Premium Banner -->
    <section class="premium-banner">
        <div class="container">
            <div class="premium-content">
                <h2>Unlock Premium Features</h2>
                <p>Get access to exclusive content, priority expert consultations, and personalized wedding planning</p>
                <button class="btn-primary btn-large" onclick="showUpgradeModal()">Upgrade Now</button>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>AfroMarry</h3>
                    <p>Celebrating love across African traditions</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#tribes">Tribes</a></li>
                        <li><a href="#marketplace">Marketplace</a></li>
                        <li><a href="#experts">Experts</a></li>
                        <li><a href="#tools">Tools</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="help.php">Help Center</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Connect</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 AfroMarry. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <!-- Signup Modal -->
    <div id="signup-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('signup-modal')">&times;</span>
            <h3>Create Free Account</h3>
            <form id="signup-form">
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Phone Number (Optional)">
                </div>
                <button type="submit" class="btn-primary">Sign Up Free</button>
            </form>
        </div>
    </div>

    <!-- Dowry Calculator Modal -->
    <div id="dowry-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('dowry-modal')">&times;</span>
            <h3>Dowry Calculator</h3>
            <div id="dowry-calculator">
                <!-- Calculator content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Expert Booking Modal -->
    <div id="booking-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('booking-modal')">&times;</span>
            <h3>Book Consultation</h3>
            <div id="booking-form">
                <!-- Booking form will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Upgrade Modal -->
    <div id="upgrade-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('upgrade-modal')">&times;</span>
            <h3>Upgrade to Premium</h3>
            <div class="upgrade-features">
                <div class="feature">
                    <i class="fas fa-check"></i>
                    <span><strong>Ad-free experience</strong> - No advertisements</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check"></i>
                    <span>Unlimited expert consultations</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check"></i>
                    <span>Personalized wedding planning</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check"></i>
                    <span>Exclusive cultural content</span>
                </div>
                <div class="feature">
                    <i class="fas fa-check"></i>
                    <span>Priority customer support</span>
                </div>
            </div>
            <div style="text-align: center; margin-top: 1rem;">
                <button class="btn-primary btn-large" onclick="proceedToCheckout('premium', 'monthly')" style="margin-bottom: 0.5rem; display: block; width: 100%;">Upgrade Now - $20/month</button>
                <button class="btn-secondary btn-large" onclick="proceedToCheckout('premium', 'annual')" style="display: block; width: 100%;">
                    Save 30% - $168/year <span style="text-decoration: line-through; opacity: 0.7; font-size: 0.9em;">($240)</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        window.currentUser = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
        window.userPremiumStatus = <?php echo (isLoggedIn() && isPremium()) ? 'true' : 'false'; ?>;
    </script>
    <script>
        // Add premium status to body for CSS targeting
        document.body.setAttribute('data-is-premium', '<?php echo (isLoggedIn() && isPremium()) ? 'true' : 'false'; ?>');
    </script>
    <script>
    // Sample tribes data for landing page (Central & North Africa)
    window.sampleTribes = <?php
    // Sample Central Africa tribes
    $sampleCentralAfrica = [
        [
            'id' => 'sample_ca_1',
            'name' => 'Bamileke',
            'country' => 'Cameroon',
            'region' => 'Central Africa',
            'customs' => ['Dotting gifts', 'Lavish feasts', 'Polygamy option'],
            'dowry_type' => 'Goods',
            'dowry_details' => 'Cloth and money gifts',
            'image' => null
        ],
        [
            'id' => 'sample_ca_2',
            'name' => 'Ovimbundu',
            'country' => 'Angola',
            'region' => 'Central Africa',
            'customs' => ['Preservation of values', 'Family selection', 'Modern freedom'],
            'dowry_type' => 'Livestock',
            'dowry_details' => 'Cattle and goats used as bride wealth',
            'image' => null
        ],
        [
            'id' => 'sample_ca_3',
            'name' => 'Bakongo',
            'country' => 'Angola',
            'region' => 'Central Africa',
            'customs' => ['Matrilineal elements', 'Civil code bans polygyny'],
            'dowry_type' => 'Livestock',
            'dowry_details' => 'Cattle/goats per family ability',
            'image' => null
        ],
        [
            'id' => 'sample_ca_4',
            'name' => 'Fang',
            'country' => 'Gabon',
            'region' => 'Central Africa',
            'customs' => ['Inter-ethnic common', 'Arranged options'],
            'dowry_type' => 'Livestock/Cash',
            'dowry_details' => 'Cattle and cash',
            'image' => null
        ],
        [
            'id' => 'sample_ca_5',
            'name' => 'Luba',
            'country' => 'Congo, Democratic Republic of the',
            'region' => 'Central Africa',
            'customs' => ['Polygamy with first wife pre-eminent', 'Bride wealth'],
            'dowry_type' => 'Livestock/Money',
            'dowry_details' => 'Cattle and money',
            'image' => null
        ]
    ];
    
    // Sample North Africa tribes
    $sampleNorthAfrica = [
        [
            'id' => 'sample_na_1',
            'name' => 'Egyptian Arabs',
            'country' => 'Egypt',
            'region' => 'North Africa',
            'customs' => ['Henna party', 'Zaffa procession', 'Mahlabiya cake', 'Family central'],
            'dowry_type' => 'Mahr',
            'dowry_details' => 'Gold and money mahr',
            'image' => null
        ],
        [
            'id' => 'sample_na_2',
            'name' => 'Arabs (Algeria)',
            'country' => 'Algeria',
            'region' => 'North Africa',
            'customs' => ['Henna night', 'El Khouara proposal', 'Multi-day feasts', 'Family-headed obedience'],
            'dowry_type' => 'Mahr',
            'dowry_details' => 'Gold and money mahr',
            'image' => null
        ],
        [
            'id' => 'sample_na_3',
            'name' => 'Berbers/Amazigh',
            'country' => 'Algeria',
            'region' => 'North Africa',
            'customs' => ['Negaffa guides bride', 'Traditional silk dress'],
            'dowry_type' => 'Sheep/Jewelry',
            'dowry_details' => 'Sheep and jewelry as dowry',
            'image' => null
        ],
        [
            'id' => 'sample_na_4',
            'name' => 'Nubians',
            'country' => 'Egypt',
            'region' => 'North Africa',
            'customs' => ['Lelet el Hena', 'River dances', 'Three-day events'],
            'dowry_type' => 'Gold/Gifts',
            'dowry_details' => 'Gold and gifts to bride',
            'image' => null
        ],
        [
            'id' => 'sample_na_5',
            'name' => 'Tuareg',
            'country' => 'Algeria',
            'region' => 'North Africa',
            'customs' => ['Nomadic veiling', 'Saharan customs'],
            'dowry_type' => 'Camels',
            'dowry_details' => 'Camel transfers as dowry',
            'image' => null
        ]
    ];
    
    // Sample East Africa tribes
    $sampleEastAfrica = [
        [
            'id' => 'sample_ea_1',
            'name' => 'Kikuyu',
            'country' => 'Kenya',
            'region' => 'East Africa',
            'customs' => ['Ruracio', 'Goat slaughter', 'Family meetings'],
            'dowry_type' => 'Goats/Cash',
            'dowry_details' => 'Goats and cash',
            'image' => null
        ],
        [
            'id' => 'sample_ea_2',
            'name' => 'Baganda',
            'country' => 'Uganda',
            'region' => 'East Africa',
            'customs' => ['Kwanjula', 'Senga aunt', 'Kukyala'],
            'dowry_type' => 'Money/Drinks/Cloth',
            'dowry_details' => 'Cash, drinks and cloth',
            'image' => null
        ],
        [
            'id' => 'sample_ea_3',
            'name' => 'Maasai',
            'country' => 'Kenya',
            'region' => 'East Africa',
            'customs' => ['Warrior dances', 'Cow thigh ritual'],
            'dowry_type' => 'Cattle',
            'dowry_details' => 'Cattle transfers',
            'image' => null
        ],
        [
            'id' => 'sample_ea_4',
            'name' => 'Luo',
            'country' => 'Kenya',
            'region' => 'East Africa',
            'customs' => ['Ayie introduction', 'Endogamy'],
            'dowry_type' => 'Cattle',
            'dowry_details' => 'Cattle wealth',
            'image' => null
        ],
        [
            'id' => 'sample_ea_5',
            'name' => 'Sukuma',
            'country' => 'Tanzania',
            'region' => 'East Africa',
            'customs' => ['Dowry negotiations', 'Vibrant attire'],
            'dowry_type' => 'Cash/Livestock',
            'dowry_details' => 'Cash and animals',
            'image' => null
        ]
    ];
    
    // Sample West Africa tribes
    $sampleWestAfrica = [
        [
            'id' => 'sample_wa_1',
            'name' => 'Yoruba',
            'country' => 'Nigeria',
            'region' => 'West Africa',
            'customs' => ['Tasting four elements', 'Aso-ebi', 'Family consents'],
            'dowry_type' => 'Cash/Cloth',
            'dowry_details' => 'Cash and cloth',
            'image' => null
        ],
        [
            'id' => 'sample_wa_2',
            'name' => 'Igbo',
            'country' => 'Nigeria',
            'region' => 'West Africa',
            'customs' => ['Igba nkwu (wine carrying)', 'High bride price'],
            'dowry_type' => 'Cash/Yams',
            'dowry_details' => 'Cash and yam gifts',
            'image' => null
        ],
        [
            'id' => 'sample_wa_3',
            'name' => 'Akan',
            'country' => 'Ghana',
            'region' => 'West Africa',
            'customs' => ['Knocking ceremony', 'Fufu mashing', 'Consent three times'],
            'dowry_type' => 'Cloth/Drinks/Money',
            'dowry_details' => 'Cloth, drinks and money',
            'image' => null
        ],
        [
            'id' => 'sample_wa_4',
            'name' => 'Wolof',
            'country' => 'Senegal',
            'region' => 'West Africa',
            'customs' => ['Endogamous cousin marriages', 'Lavish feasts'],
            'dowry_type' => 'Mahr',
            'dowry_details' => 'Money and gold mahr',
            'image' => null
        ],
        [
            'id' => 'sample_wa_5',
            'name' => 'Hausa',
            'country' => 'Nigeria',
            'region' => 'West Africa',
            'customs' => ['Islamic walima', 'Sharo flogging'],
            'dowry_type' => 'Mahr',
            'dowry_details' => 'Money and livestock',
            'image' => null
        ]
    ];
    
    // Sample Southern Africa tribes
    $sampleSouthernAfrica = [
        [
            'id' => 'sample_sa_1',
            'name' => 'Zulu',
            'country' => 'South Africa',
            'region' => 'Southern Africa',
            'customs' => ['Lobola', 'Umemulo', 'Polygyny allowed'],
            'dowry_type' => 'Cattle/Money',
            'dowry_details' => 'Cows and cash',
            'image' => null
        ],
        [
            'id' => 'sample_sa_2',
            'name' => 'Xhosa',
            'country' => 'South Africa',
            'region' => 'Southern Africa',
            'customs' => ['Ukutwala', 'Negotiations'],
            'dowry_type' => 'Livestock/Cash',
            'dowry_details' => 'Cattle and money',
            'image' => null
        ],
        [
            'id' => 'sample_sa_3',
            'name' => 'Shona',
            'country' => 'Zimbabwe',
            'region' => 'Southern Africa',
            'customs' => ['Roora', 'Kukundikana test', 'White wedding after'],
            'dowry_type' => 'Cattle/Cash',
            'dowry_details' => 'Cattle and money',
            'image' => null
        ],
        [
            'id' => 'sample_sa_4',
            'name' => 'Tswana',
            'country' => 'Botswana',
            'region' => 'Southern Africa',
            'customs' => ['Bogwera/Bogadi initiation', 'Pre-arranged options', 'Polygyny possible'],
            'dowry_type' => 'Cattle (Heifers)',
            'dowry_details' => 'Heifers as bogadi',
            'image' => null
        ],
        [
            'id' => 'sample_sa_5',
            'name' => 'Ndebele',
            'country' => 'Zimbabwe',
            'region' => 'Southern Africa',
            'customs' => ['Lobola', 'House paintings'],
            'dowry_type' => 'Cattle',
            'dowry_details' => 'Cows as lobola',
            'image' => null
        ]
    ];
    
    echo json_encode(array_merge($sampleCentralAfrica, $sampleNorthAfrica, $sampleEastAfrica, $sampleWestAfrica, $sampleSouthernAfrica));
    ?>;
    </script>
    <?php $ver = time(); ?>
    <script>
        // Inject BASE_PATH from PHP into JavaScript before config.js loads
        window.PHP_BASE_PATH = '<?php echo BASE_PATH; ?>';
    </script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js?v=<?php echo $ver; ?>"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js?v=<?php echo $ver; ?>"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/dowry-calculator.js?v=<?php echo $ver; ?>"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/marketplace.js?v=<?php echo $ver; ?>"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/experts.js?v=<?php echo $ver; ?>"></script>
    <?php if (!isLoggedIn() || !isPremium()): ?>
    <script src="<?php echo BASE_PATH; ?>/assets/js/ads.js?v=<?php echo $ver; ?>"></script>
    <?php endif; ?>
</body>
</html>
