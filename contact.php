<?php
require_once 'config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
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
            <div class="nav-menu">
                <a href="index.php" class="nav-link">Home</a>
                <a href="index.php#tribes" class="nav-link">Tribes</a>
                <a href="index.php#marketplace" class="nav-link">Marketplace</a>
                <a href="index.php#experts" class="nav-link">Experts</a>
                <a href="index.php#tools" class="nav-link">Tools</a>
                <?php if (isLoggedIn()): ?>
                    <a href="pages/profile.php" class="nav-link">Profile</a>
                    <a href="auth/logout.php" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="auth/login.php" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="page-container">
        <div class="container">
            <div class="page-header">
                <h1>Contact Us</h1>
                <p>We'd love to hear from you. Here's how you can reach us.</p>
            </div>

            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email</h3>
                        <p>support@afromarry.com</p>
                        <p>For general inquiries and support</p>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Phone</h3>
                        <p>+233530389018</p>
                        <p>Monday-Friday, 9AM-5PM EST</p>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Office</h3>
                        <p>123 Cultural Avenue</p>
                        <p>Accra, Ghana</p>
                    </div>
                </div>

                <div class="contact-form">
                    <h2>Send us a message</h2>
                    <form id="contact-form">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                        <li><a href="index.php#tribes">Tribes</a></li>
                        <li><a href="index.php#marketplace">Marketplace</a></li>
                        <li><a href="index.php#experts">Experts</a></li>
                        <li><a href="index.php#tools">Tools</a></li>
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

    <script>
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will get back to you soon.');
            this.reset();
        });
    </script>
</body>
</html>