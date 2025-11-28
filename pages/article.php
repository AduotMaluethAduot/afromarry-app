<?php
require_once '../config/database.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if (!$slug) {
    header('Location: ' . page_url('cultural-articles.php'));
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get the article
$query = "SELECT ca.*, cc.name as category_name, u.full_name as author_name
         FROM cultural_articles ca
         JOIN cultural_categories cc ON ca.category_id = cc.id
         JOIN users u ON ca.author_id = u.id
         WHERE ca.slug = :slug AND ca.is_published = TRUE";

$stmt = $db->prepare($query);
$stmt->execute([':slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    header('Location: ' . page_url('cultural-articles.php'));
    exit;
}

// Increment views count
$query = "UPDATE cultural_articles SET views_count = views_count + 1 WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $article['id']]);

// Check if user liked the article
$user_liked = false;
if (isLoggedIn()) {
    $user = getCurrentUser();
    $query = "SELECT id FROM article_likes WHERE article_id = :article_id AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':article_id' => $article['id'],
        ':user_id' => $user['id']
    ]);
    $user_liked = $stmt->fetch() ? true : false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article['title']; ?> - AfroMarry</title>
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

    <div class="article-container">
        <article class="article-content">
            <header class="article-header">
                <div class="article-meta">
                    <a href="<?php echo page_url('cultural-articles.php?category=' . $article['category_id']); ?>" class="category-tag">
                        <?php echo $article['category_name']; ?>
                    </a>
                    <span class="article-date">
                        <?php echo date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])); ?>
                    </span>
                </div>
                
                <h1><?php echo $article['title']; ?></h1>
                
                <div class="article-author">
                    <div class="author-info">
                        <p>By <strong><?php echo $article['author_name']; ?></strong></p>
                    </div>
                    <div class="article-stats">
                        <span><i class="fas fa-eye"></i> <?php echo $article['views_count']; ?></span>
                        <button id="like-button" class="like-button <?php echo $user_liked ? 'liked' : ''; ?>" onclick="likeArticle(<?php echo $article['id']; ?>)">
                            <i class="fas fa-heart"></i>
                            <span id="likes-count"><?php echo $article['likes_count']; ?></span>
                        </button>
                    </div>
                </div>
            </header>

            <?php if ($article['featured_image']): ?>
                <figure class="article-featured-image">
                    <img src="<?php echo $article['featured_image']; ?>" alt="<?php echo $article['title']; ?>">
                </figure>
            <?php endif; ?>

            <div class="article-body">
                <?php echo $article['content']; ?>
            </div>

            <footer class="article-footer">
                <div class="article-actions">
                    <button id="like-button-footer" class="like-button <?php echo $user_liked ? 'liked' : ''; ?>" onclick="likeArticle(<?php echo $article['id']; ?>)">
                        <i class="fas fa-heart"></i>
                        <span>Like</span>
                    </button>
                    <button class="share-button" onclick="shareArticle()">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                </div>
            </footer>
        </article>
    </div>

    <script>
        async function likeArticle(articleId) {
            <?php if (!isLoggedIn()): ?>
                window.location.href = '<?php echo auth_url('login.php'); ?>';
                return;
            <?php endif; ?>
            
            try {
                const response = await fetch(`<?php echo action_url('cultural-content.php'); ?>/articles/${articleId}/like`, {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Toggle like button state
                    const likeButtons = document.querySelectorAll('.like-button');
                    likeButtons.forEach(button => {
                        button.classList.toggle('liked');
                    });
                    
                    // Update likes count
                    const likesCountElement = document.getElementById('likes-count');
                    if (likesCountElement) {
                        const currentCount = parseInt(likesCountElement.textContent);
                        likesCountElement.textContent = button.classList.contains('liked') ? currentCount + 1 : currentCount - 1;
                    }
                } else {
                    alert('Failed to like article: ' + result.message);
                }
            } catch (error) {
                alert('Error liking article: ' + error.message);
            }
        }

        function shareArticle() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($article['title']); ?>',
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback for browsers that don't support Web Share API
                const el = document.createElement('textarea');
                el.value = window.location.href;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                alert('Link copied to clipboard!');
            }
        }
    </script>

    <style>
        .article-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .article-content {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .article-header {
            margin-bottom: 2rem;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .category-tag {
            background: #ede9fe;
            color: #8B5CF6;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
        }
        
        .category-tag:hover {
            background: #ddd6fe;
        }
        
        .article-date {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .article-header h1 {
            color: #1f2937;
            margin: 0 0 1.5rem 0;
            line-height: 1.3;
        }
        
        .article-author {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .author-info p {
            margin: 0;
            color: #6b7280;
        }
        
        .article-stats {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .like-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .like-button:hover,
        .like-button.liked {
            color: #ef4444;
            background: #fee2e2;
        }
        
        .article-featured-image {
            margin: 2rem 0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .article-featured-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .article-body {
            color: #374151;
            line-height: 1.8;
            font-size: 1.1rem;
        }
        
        .article-body p {
            margin-bottom: 1.5rem;
        }
        
        .article-body h2 {
            color: #1f2937;
            margin: 2rem 0 1rem 0;
        }
        
        .article-body h3 {
            color: #1f2937;
            margin: 1.5rem 0 1rem 0;
        }
        
        .article-body ul,
        .article-body ol {
            margin-bottom: 1.5rem;
            padding-left: 2rem;
        }
        
        .article-body li {
            margin-bottom: 0.5rem;
        }
        
        .article-body blockquote {
            border-left: 4px solid #8B5CF6;
            padding: 0.5rem 1rem;
            margin: 1.5rem 0;
            background: #f5f3ff;
            border-radius: 0 8px 8px 0;
        }
        
        .article-footer {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .article-actions {
            display: flex;
            gap: 1rem;
        }
        
        .share-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .share-button:hover {
            color: #1f2937;
            background: #f3f4f6;
        }
        
        @media (max-width: 768px) {
            .article-container {
                margin: 1rem;
            }
            
            .article-content {
                padding: 1rem;
            }
            
            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .article-author {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .article-stats {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</body>
</html>