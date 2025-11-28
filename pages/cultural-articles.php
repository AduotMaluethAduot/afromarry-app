<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get categories for navigation
$query = "SELECT * FROM cultural_categories WHERE is_active = TRUE ORDER BY sort_order ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll();

// Get articles
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT ca.*, cc.name as category_name, u.full_name as author_name
         FROM cultural_articles ca
         JOIN cultural_categories cc ON ca.category_id = cc.id
         JOIN users u ON ca.author_id = u.id
         WHERE ca.is_published = TRUE";

$params = [];

if ($category_id) {
    $query .= " AND ca.category_id = :category_id";
    $params[':category_id'] = $category_id;
}

if (!empty($search)) {
    $query .= " AND (ca.title LIKE :search OR ca.excerpt LIKE :search OR ca.content LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY ca.published_at DESC, ca.created_at DESC LIMIT 12";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$articles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Articles - AfroMarry</title>
    <base href="<?php echo BASE_PATH; ?>/">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo base_url('index.php'); ?>">
                    <i class="fas fa-heart"></i>
                    <span>AfroMarry</span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="<?php echo base_url('index.php'); ?>" class="nav-link">Home</a>
            </div>
        </div>
    </nav>

    <div class="articles-container">
        <div class="articles-header">
            <h1>Cultural Articles</h1>
            <p>Explore the rich traditions and customs of African marriage ceremonies</p>
        </div>

        <!-- Search and Filter -->
        <div class="articles-controls">
            <form method="GET" class="search-form">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-primary">Search</button>
            </form>
            
            <div class="category-filter">
                <a href="<?php echo page_url('cultural-articles.php'); ?>" class="category-btn <?php echo !$category_id ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a href="<?php echo page_url('cultural-articles.php?category=' . $category['id']); ?>" 
                       class="category-btn <?php echo $category_id == $category['id'] ? 'active' : ''; ?>">
                        <?php echo $category['name']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Articles Grid -->
        <div class="articles-grid">
            <?php if (empty($articles)): ?>
                <div class="no-articles">
                    <i class="fas fa-book-open fa-3x"></i>
                    <h3>No articles found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="article-card">
                        <?php if ($article['featured_image']): ?>
                            <div class="article-image">
                                <img src="<?php echo $article['featured_image']; ?>" alt="<?php echo $article['title']; ?>">
                            </div>
                        <?php endif; ?>
                        <div class="article-content">
                            <div class="article-meta">
                                <span class="category-tag"><?php echo $article['category_name']; ?></span>
                                <span class="article-date"><?php echo date('M j, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?></span>
                            </div>
                            <h3><a href="<?php echo page_url('article.php?slug=' . $article['slug']); ?>"><?php echo $article['title']; ?></a></h3>
                            <?php if ($article['excerpt']): ?>
                                <p class="article-excerpt"><?php echo $article['excerpt']; ?></p>
                            <?php else: ?>
                                <p class="article-excerpt"><?php echo substr(strip_tags($article['content']), 0, 150) . '...'; ?></p>
                            <?php endif; ?>
                            <div class="article-stats">
                                <span><i class="fas fa-eye"></i> <?php echo $article['views_count']; ?></span>
                                <span><i class="fas fa-heart"></i> <?php echo $article['likes_count']; ?></span>
                                <span><i class="fas fa-user"></i> <?php echo $article['author_name']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .articles-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .articles-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .articles-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .articles-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .articles-controls {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            position: relative;
            min-width: 250px;
        }
        
        .search-input i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .search-input input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
        }
        
        .search-input input:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .category-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .category-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            color: #4b5563;
            background: #f3f4f6;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #8B5CF6;
            color: white;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
        }
        
        .article-image {
            height: 200px;
            overflow: hidden;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-content {
            padding: 1.5rem;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .category-tag {
            background: #ede9fe;
            color: #8B5CF6;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .article-date {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .article-content h3 {
            margin: 0 0 1rem 0;
            color: #1f2937;
        }
        
        .article-content h3 a {
            color: inherit;
            text-decoration: none;
        }
        
        .article-content h3 a:hover {
            color: #8B5CF6;
        }
        
        .article-excerpt {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .article-stats {
            display: flex;
            gap: 1rem;
            color: #9ca3af;
            font-size: 0.9rem;
        }
        
        .article-stats span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .no-articles {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }
        
        .no-articles i {
            margin-bottom: 1rem;
            color: #d1d5db;
        }
        
        @media (max-width: 768px) {
            .articles-container {
                margin: 1rem;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                min-width: auto;
            }
        }
    </style>
</body>
</html>