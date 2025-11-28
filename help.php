<?php
require_once 'config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - AfroMarry</title>
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
                <h1>Help Center</h1>
                <p>Find answers to common questions and get help with using AfroMarry</p>
            </div>

            <div class="help-content">
                <div class="faq-section">
                    <h2>Frequently Asked Questions</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I create an account?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>To create an account, click the "Sign Up" button in the top right corner of the homepage. Fill in your details and verify your email address. You'll then have access to all of AfroMarry's features.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>Is there a mobile app?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Yes! AfroMarry is available as a mobile app for both iOS and Android devices. You can download it from the App Store or Google Play Store. The app offers all the same features as the web version.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I find information about a specific tribe?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>You can browse tribes by region using the "Tribes" section on our homepage, or use the search function to find specific tribes. Each tribe page contains detailed information about their marriage traditions, customs, and dowry practices.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What is the Dowry Calculator?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Our Dowry Calculator helps you estimate traditional dowry amounts based on factors like family size, region, and tradition level. It's a tool to help you understand and plan for cultural expectations, not a requirement.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>How do I book a consultation with a cultural expert?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Visit the "Experts" section on our homepage to browse available cultural experts. You can filter by region, specialty, and availability. Click "Book Consultation" on an expert's profile to schedule a video call.</p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <h3>What is included in a Premium subscription?</h3>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer">
                            <p>Premium subscribers get access to exclusive content, priority expert consultations, personalized wedding planning tools, ad-free browsing, and early access to new features. You can upgrade from your profile page.</p>
                        </div>
                    </div>
                </div>

                <div class="help-sidebar">
                    <div class="help-card">
                        <h3>Need More Help?</h3>
                        <p>Can't find what you're looking for?</p>
                        <a href="contact.php" class="btn-primary">Contact Support</a>
                    </div>
                    
                    <div class="help-card">
                        <h3>Community</h3>
                        <p>Join our community discussions</p>
                        <a href="pages/community.php" class="btn-secondary">Visit Community</a>
                    </div>
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
        // FAQ accordion functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const answer = faqItem.querySelector('.faq-answer');
                const icon = question.querySelector('i');
                
                faqItem.classList.toggle('active');
                
                if (faqItem.classList.contains('active')) {
                    answer.style.display = 'block';
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                } else {
                    answer.style.display = 'none';
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            });
        });
    </script>
</body>
</html>