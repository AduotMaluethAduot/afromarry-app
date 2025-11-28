# AfroMarry - African Marriage Traditions Platform

A comprehensive web application that celebrates love across African traditions, providing cultural education, marketplace, expert consultation services, and community engagement.

## Features

### 🌍 Cultural Discovery
- **54 African Countries** coverage
- **810+ Tribes** with detailed information
- **3000+ Customs** and traditions
- Interactive search and filtering
- Regional exploration
- Cultural articles and educational content

### 🛒 Marketplace
- Authentic cultural wedding items
- Categories: Fabrics, Jewelry, Ceremonial, Attire
- Shopping cart functionality
- Secure checkout process

### 👥 Expert Consultations
- Cultural experts and tribal elders
- Video consultation booking
- Specialized knowledge in tribal traditions
- Rating and review system

### 🧮 Tools
- **Dowry Calculator** - Estimate dowry amounts based on family size, tradition level, and region
- Wedding planning tools
- Custom cultural guides

### 💳 Payment System
- Multiple payment methods (Paystack, Flutterwave, MTN Mobile Money)
- Secure checkout process
- Order tracking and management

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Styling**: Custom CSS with responsive design
- **Payment**: Paystack, Flutterwave integration
- **Icons**: Font Awesome 6.0

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/AduotMaluethAduot/afromarry-app.git
   cd afromarry
   ```

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE afromarry;
   exit
   
   # Import database schema
   mysql -u root -p afromarry < database/database.sql
   ```

3. **Configure Database**
   Edit `config/database.php` with your database credentials or set environment variables:
   ```php
   // For local development (XAMPP)
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ecommerce_2025A_aduot_jok');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Empty for XAMPP default

   // For production
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ecommerce_2025A_aduot_jok');
   define('DB_USER', 'Aduot.jok');
   define('DB_PASS', 'Aduot12');
   ```

4. **Set Permissions**
   ```bash
   chmod 755 -R .
   chmod 777 -R uploads/ (if you have file uploads)
   ```

5. **Configure Web Server**
   
   **Apache (.htaccess)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```
   
   **Nginx**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

6. **Payment Gateway Setup**
   
   **Paystack**
   - Get your public and secret keys from [Paystack Dashboard](https://dashboard.paystack.com)
   - Update the keys in `.env` file
   
   **Flutterwave**
   - Get your public key from [Flutterwave Dashboard](https://dashboard.flutterwave.com)
   - Update the key in `.env` file
   
   **MTN Mobile Money**
   - Configure API credentials in `.env` file

7. **Access the Application**
   Open your browser and navigate to your domain or localhost

## Project Structure

```
afromarry/
├── actions/               # API action endpoints
│   ├── ads.php
│   ├── cart.php
│   ├── chatbot.php
│   ├── compatibility.php
│   ├── cultural-content.php
│   ├── download.php
│   ├── expert-bookings.php
│   ├── experts.php
│   ├── mtn-momo.php
│   ├── orders.php
│   ├── posts.php
│   ├── products.php
│   ├── quiz.php
│   ├── timelines.php
│   ├── track-ad-click.php
│   └── tribes.php
│   └── user-content.php
├── admin/                 # Admin dashboard
│   ├── ads.php
│   ├── coupons.php
│   ├── dashboard.php
│   ├── experts.php
│   ├── invoices.php
│   ├── login.php
│   ├── orders.php
│   ├── payments.php
│   ├── products.php
│   ├── reports.php
│   ├── settings.php
│   └── users.php
├── controllers/           # MVC Controllers
│   ├── BaseController.php
│   ├── CartController.php
│   ├── CulturalContentController.php
│   ├── ExpertBookingController.php
│   ├── ExpertController.php
│   ├── OrderController.php
│   ├── PostController.php
│   ├── ProductController.php
│   ├── TribeController.php
│   └── order_controller.php
├── database/              # Database files
│   ├── database.sql
│   ├── .env.example
│   ├── get_admin_info.php
│   ├── migration_add_ad_tables.php
│   ├── reset_admin_password.php
│   ├── run_all_seeds.php
│   ├── run_cultural_migration.php
│   ├── seed_all_regions.php
│   ├── seed_all_regions_standalone.php
│   ├── seed_central_africa.php
│   ├── seed_cultural_content.php
│   ├── seed_east_africa.php
│   ├── seed_experts_africa.php
│   ├── seed_north_africa.php
│   ├── seed_products.php
│   ├── seed_southern_africa.php
│   ├── seed_west_africa.php
│   ├── test_admin_password.php
│   └── verify_ad_tables.php
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── ads.js
│       ├── auth.js
│       ├── checkout-momo.js
│       ├── checkout.js
│       ├── config.js
│       ├── dowry-calculator.js
│       ├── experts.js
│       ├── main.js
│       └── marketplace.js
├── pages/                # Application pages
│   ├── includes/
│   │   └── dashboard-sidebar.php
│   ├── article.php
│   ├── bookings.php
│   ├── cart.php
│   ├── chatbot.php
│   ├── checkout.php
│   ├── community.php
│   ├── compatibility-match.php
│   ├── cultural-articles.php
│   ├── custom-guide.php
│   ├── dashboard.php
│   ├── experts.php
│   ├── notifications.php
│   ├── order-success.php
│   ├── orders.php
│   ├── payment-verification.php
│   ├── planner.php
│   ├── profile.php
│   ├── quiz.php
│   ├── regions.php
│   ├── submit-content.php
│   ├── timeline.php
│   └── upgrade.php
├── auth/                  # Authentication
│   ├── forgot-password.php
│   ├── login.php
│   ├── logout.php
│   ├── register.php
│   └── reset-password.php
├── config/                # Configuration
│   ├── admin_config.php
│   ├── admin_security.php
│   ├── database.php
│   ├── env_loader.php
│   ├── paths.php
│   ├── payment_config.php
│   └── security.php
├── helpers/               # Helper functions
│   ├── cache.php
│   ├── generate_minified.php
│   ├── minify.php
│   ├── performance.php
│   └── uploads.php
├── cache/                 # Cache files
├── images/                # Uploaded images
├── index.php             # Main page
└── README.md
```

## API Endpoints

### Tribes
- `GET /actions/tribes.php` - Get all tribes
- `GET /actions/tribes.php?search=query` - Search tribes
- `GET /actions/tribes.php?region=region` - Filter by region

### Products
- `GET /actions/products.php` - Get all products
- `GET /actions/products.php?category=category` - Filter by category

### Cart
- `GET /actions/cart.php` - Get cart items
- `POST /actions/cart.php` - Add item to cart
- `PUT /actions/cart.php/{id}` - Update cart item
- `DELETE /actions/cart.php/{id}` - Remove item from cart

### Orders
- `GET /actions/orders.php` - Get user orders
- `POST /actions/orders.php` - Create new order
- `PUT /actions/orders.php/{id}` - Update order status

### Cultural Content
- `GET /actions/cultural-content.php` - Get cultural articles
- `POST /actions/cultural-content.php` - Create new article
- `PUT /actions/cultural-content.php/{id}` - Update article
- `DELETE /actions/cultural-content.php/{id}` - Delete article

### Expert Bookings
- `GET /actions/expert-bookings.php` - Get user bookings
- `POST /actions/expert-bookings.php` - Create new booking
- `PUT /actions/expert-bookings.php/{id}` - Update booking status
- `DELETE /actions/expert-bookings.php/{id}` - Cancel booking

### User Content
- `GET /actions/user-content.php` - Get user submitted content
- `POST /actions/user-content.php` - Submit new content
- `PUT /actions/user-content.php/{id}` - Update content
- `DELETE /actions/user-content.php/{id}` - Delete content

## Features in Detail

### Dowry Calculator
The dowry calculator helps estimate traditional dowry amounts based on:
- Family size (1-20 members)
- Tradition level (0-100% traditional)
- Regional variations (East, West, Southern, North, Central Africa)

### Compatibility Quiz
Interactive quiz to match users with compatible cultural traditions

### Community Features
- Social posts and discussions
- Timeline of cultural events
- User-generated content sharing

### Marketplace
- Product categories: Fabrics, Jewelry, Ceremonial items, Traditional attire
- Shopping cart with quantity management
- Secure checkout with multiple payment options
- Order tracking and confirmation

### Expert Consultations
- Book video consultations with cultural experts
- Expert profiles with ratings and specializations
- Meeting link generation
- Booking management system

## Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection (recommended to implement)
- Session management
- Input validation and sanitization

## Browser Support

- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

Optimized for both desktop and mobile experiences with responsive design.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email support@afromarry.com or create an issue in the repository.

## Acknowledgments

- African cultural experts and tribal elders
- Traditional wedding communities
- Open source contributors
- Cultural preservation organizations

---

**AfroMarry** - Celebrating love across African traditions ❤️