<?php
require_once 'config/database.php';
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - AfroMarry</title>
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
                <h1>Terms of Service</h1>
                <p>Effective Date: December 1, 2025</p>
            </div>

            <div class="policy-content">
                <div class="policy-section">
                    <h2>Acceptance of Terms</h2>
                    <p>By accessing or using the AfroMarry website and services, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>
                </div>

                <div class="policy-section">
                    <h2>Use License</h2>
                    <p>Permission is granted to temporarily download one copy of the materials (information or software) on AfroMarry's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>
                    
                    <p>Under this license, you may not:</p>
                    <ul>
                        <li>Modify or copy the materials</li>
                        <li>Use the materials for any commercial purpose</li>
                        <li>Attempt to decompile or reverse engineer any software contained on AfroMarry's website</li>
                        <li>Remove any copyright or other proprietary notations from the materials</li>
                        <li>Transfer the materials to another person or "mirror" the materials on any other server</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>User Accounts</h2>
                    <p>When you create an account with us, you must provide accurate and complete information. You are responsible for maintaining the confidentiality of your account and password and for restricting access to your computer. You agree to accept responsibility for all activities that occur under your account or password.</p>
                </div>

                <div class="policy-section">
                    <h2>User Conduct</h2>
                    <p>You agree not to use the service for any purpose that is unlawful or prohibited by these terms. You agree not to:</p>
                    <ul>
                        <li>Post or transmit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, vulgar, obscene, or otherwise objectionable</li>
                        <li>Impersonate any person or entity, or falsely state or otherwise misrepresent your affiliation with a person or entity</li>
                        <li>Interfere with or disrupt the service or servers or networks connected to the service</li>
                        <li>Attempt to gain unauthorized access to any portion of the service</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h2>Intellectual Property</h2>
                    <p>All content included on the site, such as text, graphics, logos, button icons, images, audio clips, digital downloads, data compilations, and software, is the property of AfroMarry or its content suppliers and protected by international copyright laws.</p>
                </div>

                <div class="policy-section">
                    <h2>Third-Party Services</h2>
                    <p>Our services may contain links to third-party websites or services that are not owned or controlled by AfroMarry. We have no control over, and assume no responsibility for, the content, privacy policies, or practices of any third-party websites or services.</p>
                </div>

                <div class="policy-section">
                    <h2>Disclaimer of Warranties</h2>
                    <p>The service is provided on an "as is" and "as available" basis. AfroMarry makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
                </div>

                <div class="policy-section">
                    <h2>Limitation of Liability</h2>
                    <p>In no event shall AfroMarry be liable for any damages arising out of the use or inability to use the service, including but not limited to, damages for loss of data or profit, or due to business interruption.</p>
                </div>

                <div class="policy-section">
                    <h2>Indemnification</h2>
                    <p>You agree to indemnify and hold harmless AfroMarry and its affiliates, officers, agents, and employees from any claim or demand, including reasonable attorneys' fees, made by any third party due to or arising out of your use of the service, your violation of these terms, or your violation of any rights of another.</p>
                </div>

                <div class="policy-section">
                    <h2>Changes to Terms</h2>
                    <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. What constitutes a material change will be determined at our sole discretion.</p>
                </div>

                <div class="policy-section">
                    <h2>Governing Law</h2>
                    <p>These Terms shall be governed and construed in accordance with the laws of Ethiopia, Ghana or any Africa countries, without regard to its conflict of law provisions.</p>
                </div>

                <div class="policy-section">
                    <h2>Contact Us</h2>
                    <p>If you have any questions about these Terms, please contact us at:</p>
                    <p>Email: legal@afromarry.com</p>
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