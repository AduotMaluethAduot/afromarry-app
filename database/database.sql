-- AfroMarry Database Schema
CREATE DATABASE IF NOT EXISTS ecommerce_2025A_aduot_jok;
USE ecommerce_2025A_aduot_jok;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_verified BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    premium_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tribes table
CREATE TABLE tribes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    country VARCHAR(255) NOT NULL,
    region VARCHAR(255) NOT NULL,
    customs TEXT,
    dowry_type VARCHAR(255),
    dowry_details TEXT,
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table (digital_product_id foreign key will be added after digital_products table is created)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NULL COMMENT 'User who created/owns this product (vendors list for FREE)',
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    category VARCHAR(100) NOT NULL,
    tribe VARCHAR(255),
    image VARCHAR(500),
    description TEXT,
    stock_quantity INT DEFAULT 0,
    platform_commission_rate DECIMAL(5,2) DEFAULT 0.05 COMMENT 'Platform commission rate (5% default)',
    is_digital BOOLEAN DEFAULT FALSE,
    digital_product_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Experts table
CREATE TABLE experts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    tribe VARCHAR(255) NOT NULL,
    specialization TEXT,
    languages TEXT,
    rating DECIMAL(3,2) DEFAULT 0.00,
    hourly_rate DECIMAL(10,2) NOT NULL,
    payment_percentage DECIMAL(5,2) DEFAULT 85.00 COMMENT 'Percentage expert receives (admin keeps 15%)',
    image VARCHAR(500),
    availability VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Orders table (escrow_id foreign key will be added after escrow_transactions table is created)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vendor_id INT NULL COMMENT 'Primary vendor for this order (if single vendor)',
    total_amount DECIMAL(10,2) NOT NULL,
    platform_commission DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Platform commission (5% of marketplace sales)',
    vendor_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Amount paid to vendor (95% of marketplace sales)',
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(100),
    payment_reference VARCHAR(255),
    payment_verified BOOLEAN DEFAULT FALSE,
    payment_proof VARCHAR(500),
    shipping_address TEXT,
    notes TEXT,
    requires_escrow BOOLEAN DEFAULT FALSE,
    escrow_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    platform_commission DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Platform commission for this item (5%)',
    vendor_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Vendor amount for this item (95%)',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Expert bookings table
CREATE TABLE expert_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    expert_id INT NOT NULL,
    booking_date DATETIME NOT NULL,
    duration_hours INT DEFAULT 1,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    meeting_link VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (expert_id) REFERENCES experts(id)
);

-- Expert payments table (tracks admin payments to experts)
CREATE TABLE expert_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expert_id INT NOT NULL,
    booking_ids TEXT COMMENT 'Comma-separated booking IDs included in this payment',
    total_earnings DECIMAL(10,2) NOT NULL COMMENT 'Total amount from bookings',
    payment_percentage DECIMAL(5,2) NOT NULL COMMENT 'Percentage paid to expert',
    expert_amount DECIMAL(10,2) NOT NULL COMMENT 'Amount paid to expert',
    admin_commission DECIMAL(10,2) NOT NULL COMMENT 'Amount kept by admin',
    payment_method VARCHAR(100) COMMENT 'Payment method used',
    payment_reference VARCHAR(255) COMMENT 'Transaction reference',
    payment_date DATE NOT NULL,
    paid_by INT NOT NULL COMMENT 'Admin who made the payment',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO tribes (name, country, region, customs, dowry_type, dowry_details, image) VALUES
('Kikuyu', 'Kenya', 'East Africa', '["Ruracio ceremony", "Goat slaughter ritual", "Family negotiations", "Traditional beer sharing"]', 'Livestock & Cash', '10-30 goats, negotiable cash amount based on family status', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410853969_273696b3.webp'),
('Yoruba', 'Nigeria', 'West Africa', '["Knocking ceremony (Ikun Afia)", "Introduction (Mo mi mo e)", "Engagement (Idana)", "Traditional wedding"]', 'Kola Nuts & Money', 'Kola nuts, bitter kola, alligator pepper, cash bride price (negotiable)', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410855685_37193717.webp'),
('Zulu', 'South Africa', 'Southern Africa', '["Lobola negotiation", "Umembeso gift exchange", "Traditional attire ceremony", "Ancestral blessings"]', 'Cattle (Lobola)', '10-20 cattle or equivalent cash (R50,000-R150,000)', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410857432_8d2f5127.webp');

INSERT INTO products (name, price, currency, category, tribe, image, description, stock_quantity) VALUES
('Royal Kente Cloth', 5200.00, 'USD', 'Fabrics', 'Akan', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410823359_9055dec3.webp', 'Authentic handwoven kente with gold patterns', 50),
('Mudcloth Wedding Fabric', 8000.00, 'USD', 'Fabrics', 'Bambara', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410825395_3e87fe8f.webp', 'Traditional Mali mudcloth for ceremonies', 30),
('Ankara Print Bundle', 6000.00, 'USD', 'Fabrics', 'Yoruba', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410827142_73be90d9.webp', 'Vibrant Ankara prints for Aso-Ebi', 100),
('Coral Bead Necklace', 45000.00, 'USD', 'Jewelry', 'Edo', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410827851_3cbd249f.webp', 'Traditional coral beads for brides', 25),
('Gold Wedding Necklace', 85000.00, 'USD', 'Jewelry', 'Ashanti', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410829558_c635f12d.webp', 'Handcrafted gold necklace with cultural motifs', 15),
('Beaded Bridal Set', 25000.00, 'USD', 'Jewelry', 'Maasai', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410831980_477c4163.webp', 'Colorful Maasai beadwork set', 40),
('Ceremonial Calabash', 3500.00, 'USD', 'Ceremonial', 'Kikuyu', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410833183_bc7fbf6b.webp', 'Carved calabash for traditional drinks', 60),
('Kola Nut Gift Box', 2500.00, 'USD', 'Ceremonial', 'Yoruba', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410835299_333d7708.webp', 'Premium kola nuts in decorative box', 80),
('Wedding Gourd Set', 4500.00, 'USD', 'Ceremonial', 'Zulu', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410837340_ddd1889b.webp', 'Traditional gourds for ceremonies', 35),
('Embroidered Dashiki', 15000.00, 'USD', 'Attire', 'Hausa', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410844483_d35b6cd7.webp', 'Premium embroidered dashiki for grooms', 45),
('Agbada Royal Set', 35000.00, 'USD', 'Attire', 'Yoruba', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410846290_2c0eef14.webp', 'Complete agbada with cap and accessories', 20),
('Kaftan Wedding Robe', 28000.00, 'USD', 'Attire', 'Fulani', 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410848048_94c991d1.webp', 'Elegant kaftan with intricate embroidery', 30);

INSERT INTO experts (name, tribe, specialization, languages, rating, hourly_rate, payment_percentage, image, availability) VALUES
('Chief Okonkwo Nnamdi', 'Igbo', 'Traditional marriage negotiations & Wine carrying ceremony', '["Igbo", "English"]', 4.90, 150.00, 85.00, 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410848755_66e52423.webp', 'Available this week'),
('Mama Amina Diallo', 'Fulani', 'Fulani wedding customs & Sharo ceremony guidance', '["Fulfulde", "French", "English"]', 5.00, 150.00, 85.00, 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410850443_d70956c6.webp', 'Available today'),
('Elder Kofi Mensah', 'Akan', 'Akan knocking ceremony & Customary marriage rites', '["Twi", "English"]', 4.80, 150.00, 85.00, 'https://d64gsuwffb70l.cloudfront.net/68fcfebef8ea68585421298c_1761410852791_409c8a03.webp', 'Available next week');

-- Payment verification table
CREATE TABLE payment_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('mobile_money', 'bank_transfer', 'receipt_upload') NOT NULL,
    payment_reference VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    proof_image VARCHAR(500),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT NULL,
    verification_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

-- Invoices table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft', 'sent', 'paid', 'overdue') DEFAULT 'draft',
    due_date DATE,
    sent_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Admin logs table
CREATE TABLE admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Coupons table
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    maximum_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    used_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- User notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password resets table (for forgot password functionality)
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- Quiz results table (for tribe discovery quiz)
CREATE TABLE quiz_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_type ENUM('tribe_discovery', 'compatibility', 'cultural_match') DEFAULT 'tribe_discovery',
    answers JSON NOT NULL COMMENT 'Stores quiz answers as JSON',
    result_tribe_id INT NULL COMMENT 'Recommended tribe based on quiz',
    result_data JSON NULL COMMENT 'Detailed results and recommendations',
    score DECIMAL(5,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (result_tribe_id) REFERENCES tribes(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_quiz_type (quiz_type)
);

-- Wedding timelines table (personalized wedding planning)
CREATE TABLE wedding_timelines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    wedding_date DATE NOT NULL,
    tribe_1_id INT NULL COMMENT 'First partner tribe',
    tribe_2_id INT NULL COMMENT 'Second partner tribe (for inter-tribal)',
    timeline_data JSON NOT NULL COMMENT 'Timeline milestones and tasks',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tribe_1_id) REFERENCES tribes(id) ON DELETE SET NULL,
    FOREIGN KEY (tribe_2_id) REFERENCES tribes(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_wedding_date (wedding_date)
);

-- Timeline milestones table
CREATE TABLE timeline_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeline_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    completed_at TIMESTAMP NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    category ENUM('ceremony', 'preparation', 'logistics', 'customs', 'dowry', 'other') DEFAULT 'other',
    tribe_id INT NULL COMMENT 'Which tribe this milestone relates to',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (timeline_id) REFERENCES wedding_timelines(id) ON DELETE CASCADE,
    FOREIGN KEY (tribe_id) REFERENCES tribes(id) ON DELETE SET NULL,
    INDEX idx_timeline_id (timeline_id),
    INDEX idx_category (category)
);

-- Chatbot conversations table
CREATE TABLE chatbot_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'Null for anonymous users',
    session_id VARCHAR(64) NOT NULL COMMENT 'Session identifier for anonymous users',
    message TEXT NOT NULL,
    response TEXT NOT NULL,
    message_type ENUM('user', 'bot', 'system') DEFAULT 'user',
    context JSON NULL COMMENT 'Stores conversation context for AI',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
);

-- Compatibility matches table (AI-driven matching)
CREATE TABLE compatibility_matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tribe_1_id INT NOT NULL,
    tribe_2_id INT NOT NULL,
    compatibility_score DECIMAL(5,2) NOT NULL COMMENT 'Score out of 100',
    dowry_fusion JSON NULL COMMENT 'Combined dowry recommendations',
    recommendations TEXT NULL COMMENT 'AI-generated recommendations',
    challenges JSON NULL COMMENT 'Potential challenges identified',
    solutions JSON NULL COMMENT 'Suggested solutions',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tribe_1_id) REFERENCES tribes(id) ON DELETE CASCADE,
    FOREIGN KEY (tribe_2_id) REFERENCES tribes(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_tribes (tribe_1_id, tribe_2_id)
);

-- User-generated content submissions
CREATE TABLE user_content_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    submission_type ENUM('tribe', 'custom', 'dowry_info', 'story', 'other') NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    tribe_id INT NULL,
    country VARCHAR(255) NULL,
    region VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected', 'needs_review') DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tribe_id) REFERENCES tribes(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_submission_type (submission_type)
);

-- Escrow transactions table
CREATE TABLE escrow_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('held', 'released', 'refunded', 'disputed') DEFAULT 'held',
    held_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    release_reason TEXT NULL,
    dispute_reason TEXT NULL,
    is_high_value BOOLEAN DEFAULT FALSE COMMENT 'True if order exceeds high-value threshold',
    threshold_amount DECIMAL(10,2) DEFAULT 50000.00 COMMENT 'Amount threshold for escrow',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_status (status)
);

-- Digital products table (for e-books/guides)
CREATE TABLE digital_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL COMMENT 'Links to main products table',
    file_path VARCHAR(500) NOT NULL COMMENT 'Path to downloadable file',
    file_size BIGINT NULL COMMENT 'File size in bytes',
    file_type VARCHAR(50) NULL COMMENT 'pdf, epub, etc.',
    download_limit INT DEFAULT 5 COMMENT 'Maximum downloads per purchase',
    download_count INT DEFAULT 0,
    is_instant_delivery BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
);

-- Digital product downloads table
CREATE TABLE digital_product_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    digital_product_id INT NOT NULL,
    user_id INT NOT NULL,
    download_token VARCHAR(64) NOT NULL COMMENT 'Secure token for download',
    download_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL COMMENT 'Token expiration',
    downloaded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (digital_product_id) REFERENCES digital_products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_id (order_id),
    INDEX idx_user_id (user_id),
    INDEX idx_download_token (download_token)
);

-- Zoom meeting credentials (for video consultations)
CREATE TABLE zoom_meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    meeting_id VARCHAR(100) NULL COMMENT 'Zoom meeting ID',
    meeting_password VARCHAR(100) NULL,
    join_url VARCHAR(500) NULL,
    start_url VARCHAR(500) NULL,
    zoom_account_email VARCHAR(255) NULL,
    status ENUM('scheduled', 'started', 'finished', 'cancelled') DEFAULT 'scheduled',
    scheduled_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES expert_bookings(id) ON DELETE CASCADE,
    INDEX idx_booking_id (booking_id),
    INDEX idx_meeting_id (meeting_id)
);

-- AI translation cache (for OpenAI GPT translations)
CREATE TABLE ai_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_text TEXT NOT NULL,
    source_language VARCHAR(10) NOT NULL,
    target_language VARCHAR(10) NOT NULL,
    translated_text TEXT NOT NULL,
    context VARCHAR(100) NULL COMMENT 'tribe_custom, dowry_info, etc.',
    tribe_id INT NULL,
    model_used VARCHAR(50) DEFAULT 'gpt-4',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tribe_id) REFERENCES tribes(id) ON DELETE SET NULL,
    INDEX idx_languages (source_language, target_language),
    INDEX idx_tribe_id (tribe_id)
);

-- Payment method settings (for MTN MoMo and other payment providers)
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(100) NOT NULL UNIQUE COMMENT 'mtn_momo, airtel_money, etc.',
    display_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    api_key VARCHAR(255) NULL COMMENT 'Encrypted API key',
    api_secret VARCHAR(255) NULL COMMENT 'Encrypted API secret',
    merchant_id VARCHAR(255) NULL,
    webhook_url VARCHAR(500) NULL,
    config_json JSON NULL COMMENT 'Additional configuration',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_method_name (method_name),
    INDEX idx_is_active (is_active)
);

-- Add foreign key constraints for escrow and digital products
ALTER TABLE orders 
ADD FOREIGN KEY (escrow_id) REFERENCES escrow_transactions(id) ON DELETE SET NULL;

ALTER TABLE products
ADD FOREIGN KEY (digital_product_id) REFERENCES digital_products(id) ON DELETE SET NULL;

-- Insert default payment methods
INSERT INTO payment_methods (method_name, display_name, is_active) VALUES
('mtn_momo', 'MTN Mobile Money', FALSE),
('airtel_money', 'Airtel Money', FALSE),
('orange_money', 'Orange Money', FALSE),
('paystack', 'Paystack', TRUE),
('flutterwave', 'Flutterwave', TRUE);

-- Vendor payments table (tracks vendor payouts from marketplace sales)
CREATE TABLE vendor_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    order_ids TEXT COMMENT 'Comma-separated order IDs included in this payment',
    total_sales DECIMAL(10,2) NOT NULL COMMENT 'Total sales amount before commission',
    platform_commission DECIMAL(10,2) NOT NULL COMMENT 'Platform commission (5%)',
    vendor_amount DECIMAL(10,2) NOT NULL COMMENT 'Amount paid to vendor (95%)',
    commission_rate DECIMAL(5,2) DEFAULT 5.00 COMMENT 'Platform commission percentage',
    payment_method VARCHAR(100) COMMENT 'Payment method used (bank_transfer, mobile_money, etc.)',
    payment_reference VARCHAR(255) COMMENT 'Payment reference number',
    payment_date DATE NOT NULL,
    paid_by INT NOT NULL COMMENT 'Admin user who processed the payment',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id),
    INDEX idx_vendor_id (vendor_id),
    INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Advertisers table (for ad system - free users see ads, premium users don't)
CREATE TABLE advertisers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    website VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad campaigns table
CREATE TABLE ad_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advertiser_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    budget DECIMAL(10,2) NOT NULL,
    daily_budget DECIMAL(10,2) NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (advertiser_id) REFERENCES advertisers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad placements table (individual ad units)
CREATE TABLE ad_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    type ENUM('banner', 'sidebar', 'inline', 'popup') DEFAULT 'banner',
    image_url VARCHAR(500) NOT NULL,
    link_url VARCHAR(500) NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Cost per click or impression',
    tribe_targeting JSON NULL COMMENT 'Target specific tribes (JSON array)',
    region_targeting JSON NULL COMMENT 'Target specific regions (JSON array)',
    is_active BOOLEAN DEFAULT TRUE,
    impressions_count INT DEFAULT 0,
    clicks_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad interactions table (tracks impressions and clicks)
CREATE TABLE ad_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT 'Null for anonymous users',
    ad_placement_id INT NOT NULL,
    interaction_type ENUM('impression', 'click') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ad_placement_id) REFERENCES ad_placements(id) ON DELETE CASCADE,
    INDEX idx_ad_placement_id (ad_placement_id),
    INDEX idx_interaction_type (interaction_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin login logs table (tracks all admin login attempts for security)
CREATE TABLE admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN NOT NULL,
    failure_reason VARCHAR(255) NULL,
    user_id INT NULL COMMENT 'User ID if login was successful',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_success (success),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Two-factor authentication table
CREATE TABLE user_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    secret VARCHAR(255) NOT NULL,
    backup_codes JSON NULL COMMENT 'Encrypted backup codes',
    is_enabled BOOLEAN DEFAULT FALSE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User posts table (for social features)
CREATE TABLE user_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(500) NULL,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post comments table
CREATE TABLE post_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES user_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Post likes table
CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES user_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id),
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comment likes table
CREATE TABLE comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (comment_id, user_id),
    INDEX idx_comment_id (comment_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cultural content categories table
CREATE TABLE cultural_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    icon VARCHAR(100),
    color VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_sort_order (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cultural content articles table
CREATE TABLE cultural_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(500),
    author_id INT NOT NULL,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    is_published BOOLEAN DEFAULT FALSE,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES cultural_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_slug (slug),
    INDEX idx_category_id (category_id),
    INDEX idx_is_published (is_published),
    INDEX idx_published_at (published_at),
    INDEX idx_author_id (author_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Article likes table
CREATE TABLE article_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES cultural_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (article_id, user_id),
    INDEX idx_article_id (article_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user
INSERT INTO users (full_name, email, password, role, is_verified, is_premium) VALUES
('System Administrator', 'admin@afromarry.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE);

-- Add foreign key constraints for escrow and digital products
ALTER TABLE orders 
ADD FOREIGN KEY (escrow_id) REFERENCES escrow_transactions(id) ON DELETE SET NULL;

ALTER TABLE products
ADD FOREIGN KEY (digital_product_id) REFERENCES digital_products(id) ON DELETE SET NULL;

-- Settings table for storing application settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'float', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
