<?php
// Dashboard Sidebar Component
// Include this in pages that need the dashboard sidebar navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="dashboard-sidebar">
    <div class="user-profile">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
        <p><?php echo htmlspecialchars($user['email']); ?></p>
        <div class="premium-badge <?php echo ($user['is_premium'] ?? false) ? 'active' : ''; ?>">
            <?php echo ($user['is_premium'] ?? false) ? 'Premium Member' : 'Free Member'; ?>
        </div>
        <?php if (($user['is_premium'] ?? false) && isset($premium_expires) && $premium_expires): ?>
            <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">
                Expires: <?php echo date('M j, Y', strtotime($premium_expires)); ?>
            </p>
        <?php elseif (($user['is_premium'] ?? false) && (!isset($premium_expires) || !$premium_expires)): ?>
            <p style="font-size: 0.8rem; color: #10b981; margin-top: 0.5rem;">
                Lifetime Premium
            </p>
        <?php endif; ?>
    </div>
    
    <nav class="dashboard-nav">
        <a href="dashboard.php" class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </a>
        <a href="regions.php" class="nav-item <?php echo $current_page === 'regions.php' ? 'active' : ''; ?>">
            <i class="fas fa-map"></i>
            Browse Regions
        </a>
        <a href="quiz.php" class="nav-item <?php echo $current_page === 'quiz.php' ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            Tribe Discovery Quiz
        </a>
        <a href="timeline.php" class="nav-item <?php echo $current_page === 'timeline.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            Wedding Timeline
        </a>
        <a href="chatbot.php" class="nav-item <?php echo $current_page === 'chatbot.php' ? 'active' : ''; ?>">
            <i class="fas fa-robot"></i>
            AI Chatbot
        </a>
        <a href="compatibility-match.php" class="nav-item <?php echo $current_page === 'compatibility-match.php' ? 'active' : ''; ?>">
            <i class="fas fa-heart"></i>
            Compatibility Match
        </a>
        <a href="orders.php" class="nav-item <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            My Orders
        </a>
        <a href="bookings.php" class="nav-item <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            Expert Bookings
        </a>
        <a href="submit-content.php" class="nav-item <?php echo $current_page === 'submit-content.php' ? 'active' : ''; ?>">
            <i class="fas fa-paper-plane"></i>
            Submit Content
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-edit"></i>
            Profile Settings
        </a>
        <?php if (!($user['is_premium'] ?? false)): ?>
        <a href="upgrade.php" class="nav-item upgrade <?php echo $current_page === 'upgrade.php' ? 'active' : ''; ?>">
            <i class="fas fa-crown"></i>
            Upgrade to Premium
        </a>
        <?php endif; ?>
    </nav>
</div>

<style>
    .dashboard-container {
        display: flex;
        min-height: 100vh;
        background: #f8fafc;
    }
    
    .dashboard-sidebar {
        width: 300px;
        background: white;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        padding: 2rem;
    }
    
    .user-profile {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 2rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #8B5CF6, #EC4899);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: white;
        font-size: 2rem;
    }
    
    .premium-badge {
        background: #f3f4f6;
        color: #6b7280;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 0.5rem;
        display: inline-block;
    }
    
    .premium-badge.active {
        background: linear-gradient(135deg, #8B5CF6, #EC4899);
        color: white;
    }
    
    .dashboard-nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: 10px;
        text-decoration: none;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .nav-item:hover,
    .nav-item.active {
        background: #f3f4f6;
        color: #8B5CF6;
    }
    
    .nav-item.upgrade {
        background: linear-gradient(135deg, #8B5CF6, #EC4899);
        color: white;
        margin-top: 1rem;
    }
    
    .nav-item.upgrade:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
    }
    
    .dashboard-content {
        flex: 1;
        padding: 2rem;
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            flex-direction: column;
        }
        
        .dashboard-sidebar {
            width: 100%;
        }
    }
</style>
