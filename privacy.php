<?php
require_once 'config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - AfroMarry</title>
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
                <h1>Privacy Policy</h1>
                <p>Effective Date: December 1, 2025</p>
            </div>

            <div class="policy-content">
                <div class="policy-section">
                    <h2>Introduction</h2>
                    <p>AfroMarry ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>
                </div>

                <div class="policy-section">
                    <h2>Information We Collect</h2>
                    <h3>Personal Information</h3>
                    <p>We may collect personally identifiable information, such as your name, email address, phone number, and other information when you register for an account or use our services.</p>
                    
                    <h3>Usage Data</h3>
                    <p>We may also collect information that your browser sends whenever you visit our site or use our services. This may include your IP address, browser type, browser version, the pages of our site that you visit, the time and date of your visit, the time spent on those pages, and other statistics.</p>
                </div>

                <div class="policy-section">
                    <h2>How We Use Your Information</h2>
                    <p>We may use the information we collect in the following ways:</p>
                    <ul>
                        <li>To provide, maintain, and improve our services</li>
                        <li>To personalize your experience on our site</li>
                        <li>To communicate with you, including sending updates and promotional materials</li>
                        <li>To process transactions and send related information</li>
                        <li>To monitor and analyze usage and trends</li>
                        <li>To detect, prevent, and address technical issues</li>
                        <li>To comply with legal obligations</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Information Sharing</h2>
                    <p>We may share your information in the following situations:</p>
                    <ul>
                        <li>With service providers who perform services on our behalf</li>
                        <li>With cultural experts when you book consultations</li>
                        <li>With marketplace vendors when you make purchases</li>
                        <li>For legal purposes, such as complying with a subpoena or similar legal process</li>
                        <li>When we believe in good faith that disclosure is necessary to protect our rights, protect your safety or the safety of others, investigate fraud, or respond to a government request</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Data Security</h2>
                    <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable.</p>
                </div>

                <div class="policy-section">
                    <h2>Data Retention</h2>
                    <p>We will retain your information for as long as your account is active or as needed to provide you services. We will retain and use your information as necessary to comply with our legal obligations, resolve disputes, and enforce our agreements.</p>
                </div>

                <div class="policy-section">
                    <h2>Your Rights</h2>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access, update, or delete your personal information</li>
                        <li>Object to or restrict the processing of your personal information</li>
                        <li>Request a copy of your personal information in a portable format</li>
                        <li>Withdraw your consent at any time where we rely on your consent to process your personal information</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Changes to This Privacy Policy</h2>
                    <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Effective Date" at the top of this Privacy Policy.</p>
                </div>

                <div class="policy-section">
                    <h2>Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                    <p>Email: privacy@afromarry.com</p>
                    <p>Address: 123 Cultural Avenue, Accra, Ghana</p>
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
</body>
</html>