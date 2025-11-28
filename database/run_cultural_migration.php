<?php
/**
 * Migration script for cultural content tables
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Create user_2fa table
    $createUser2FATable = "CREATE TABLE IF NOT EXISTS user_2fa (
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
    )";
    
    $db->exec($createUser2FATable);
    echo "Table user_2fa created successfully.\n";
    
    // Create user_posts table
    $createUserPostsTable = "CREATE TABLE IF NOT EXISTS user_posts (
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
    )";
    
    $db->exec($createUserPostsTable);
    echo "Table user_posts created successfully.\n";
    
    // Create post_comments table
    $createPostCommentsTable = "CREATE TABLE IF NOT EXISTS post_comments (
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
    )";
    
    $db->exec($createPostCommentsTable);
    echo "Table post_comments created successfully.\n";
    
    // Create post_likes table
    $createPostLikesTable = "CREATE TABLE IF NOT EXISTS post_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES user_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_like (post_id, user_id),
        INDEX idx_post_id (post_id),
        INDEX idx_user_id (user_id)
    )";
    
    $db->exec($createPostLikesTable);
    echo "Table post_likes created successfully.\n";
    
    // Create comment_likes table
    $createCommentLikesTable = "CREATE TABLE IF NOT EXISTS comment_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_like (comment_id, user_id),
        INDEX idx_comment_id (comment_id),
        INDEX idx_user_id (user_id)
    )";
    
    $db->exec($createCommentLikesTable);
    echo "Table comment_likes created successfully.\n";
    
    // Create cultural_categories table
    $createCulturalCategoriesTable = "CREATE TABLE IF NOT EXISTS cultural_categories (
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
    )";
    
    $db->exec($createCulturalCategoriesTable);
    echo "Table cultural_categories created successfully.\n";
    
    // Create cultural_articles table
    $createCulturalArticlesTable = "CREATE TABLE IF NOT EXISTS cultural_articles (
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
    )";
    
    $db->exec($createCulturalArticlesTable);
    echo "Table cultural_articles created successfully.\n";
    
    // Create article_likes table
    $createArticleLikesTable = "CREATE TABLE IF NOT EXISTS article_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        article_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (article_id) REFERENCES cultural_articles(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_like (article_id, user_id),
        INDEX idx_article_id (article_id),
        INDEX idx_user_id (user_id)
    )";
    
    $db->exec($createArticleLikesTable);
    echo "Table article_likes created successfully.\n";
    
    echo "\nCultural content migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>