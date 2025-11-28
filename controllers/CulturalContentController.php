<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../config/database.php';

class CulturalContentController extends BaseController {
    
    public function getCategories() {
        try {
            $query = "SELECT * FROM cultural_categories WHERE is_active = TRUE ORDER BY sort_order ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $categories = $stmt->fetchAll();
            
            $this->sendResponse(true, 'Categories retrieved successfully', $categories);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function getArticles() {
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        try {
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
            
            $query .= " ORDER BY ca.published_at DESC, ca.created_at DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $articles = $stmt->fetchAll();
            
            $this->sendResponse(true, 'Articles retrieved successfully', $articles);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function getArticleBySlug($slug) {
        try {
            // Get the article
            $query = "SELECT ca.*, cc.name as category_name, u.full_name as author_name
                     FROM cultural_articles ca
                     JOIN cultural_categories cc ON ca.category_id = cc.id
                     JOIN users u ON ca.author_id = u.id
                     WHERE ca.slug = :slug AND ca.is_published = TRUE";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':slug' => $slug]);
            $article = $stmt->fetch();
            
            if (!$article) {
                $this->sendResponse(false, 'Article not found', null, 404);
            }
            
            // Increment views count
            $query = "UPDATE cultural_articles SET views_count = views_count + 1 WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $article['id']]);
            
            // Check if user liked the article
            $article['user_liked'] = $this->user ? $this->userLikedArticle($article['id']) : false;
            
            $this->sendResponse(true, 'Article retrieved successfully', $article);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function likeArticle($article_id) {
        $this->requireAuth();
        
        try {
            // Check if user already liked this article
            $query = "SELECT id FROM article_likes WHERE article_id = :article_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':article_id' => $article_id,
                ':user_id' => $this->user['id']
            ]);
            
            if ($stmt->fetch()) {
                // Unlike the article
                $query = "DELETE FROM article_likes WHERE article_id = :article_id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':article_id' => $article_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE cultural_articles SET likes_count = likes_count - 1 WHERE id = :article_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':article_id' => $article_id]);
                
                $this->sendResponse(true, 'Article unliked successfully');
            } else {
                // Like the article
                $query = "INSERT INTO article_likes (article_id, user_id) VALUES (:article_id, :user_id)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':article_id' => $article_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE cultural_articles SET likes_count = likes_count + 1 WHERE id = :article_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':article_id' => $article_id]);
                
                $this->sendResponse(true, 'Article liked successfully');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    // Admin methods
    public function createCategory() {
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['name']);
            
            $query = "INSERT INTO cultural_categories (name, description, icon, color, is_active, sort_order) 
                     VALUES (:name, :description, :icon, :color, :is_active, :sort_order)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':name' => $this->sanitizeInput($data['name']),
                ':description' => isset($data['description']) ? $this->sanitizeInput($data['description']) : null,
                ':icon' => isset($data['icon']) ? $this->sanitizeInput($data['icon']) : null,
                ':color' => isset($data['color']) ? $this->sanitizeInput($data['color']) : null,
                ':is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
                ':sort_order' => isset($data['sort_order']) ? (int)$data['sort_order'] : 0
            ]);
            
            $category_id = $this->db->lastInsertId();
            
            // Get the created category
            $query = "SELECT * FROM cultural_categories WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $category_id]);
            $category = $stmt->fetch();
            
            $this->sendResponse(true, 'Category created successfully', $category);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function createArticle() {
        requireAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['title', 'content', 'category_id', 'slug']);
            
            $query = "INSERT INTO cultural_articles (category_id, title, slug, excerpt, content, featured_image, author_id, is_published, published_at) 
                     VALUES (:category_id, :title, :slug, :excerpt, :content, :featured_image, :author_id, :is_published, :published_at)";
            
            $published_at = null;
            if (isset($data['is_published']) && $data['is_published'] && isset($data['published_at'])) {
                $published_at = $data['published_at'];
            } elseif (isset($data['is_published']) && $data['is_published']) {
                $published_at = date('Y-m-d H:i:s');
            }
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':category_id' => (int)$data['category_id'],
                ':title' => $this->sanitizeInput($data['title']),
                ':slug' => $this->sanitizeInput($data['slug']),
                ':excerpt' => isset($data['excerpt']) ? $this->sanitizeInput($data['excerpt']) : null,
                ':content' => $this->sanitizeInput($data['content']),
                ':featured_image' => isset($data['featured_image']) ? $this->sanitizeInput($data['featured_image']) : null,
                ':author_id' => $this->user['id'],
                ':is_published' => isset($data['is_published']) ? (bool)$data['is_published'] : false,
                ':published_at' => $published_at
            ]);
            
            $article_id = $this->db->lastInsertId();
            
            // Get the created article
            $query = "SELECT ca.*, cc.name as category_name, u.full_name as author_name
                     FROM cultural_articles ca
                     JOIN cultural_categories cc ON ca.category_id = cc.id
                     JOIN users u ON ca.author_id = u.id
                     WHERE ca.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $article_id]);
            $article = $stmt->fetch();
            
            $this->sendResponse(true, 'Article created successfully', $article);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    // Helper methods
    private function userLikedArticle($article_id) {
        if (!$this->user) return false;
        
        $query = "SELECT id FROM article_likes WHERE article_id = :article_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':article_id' => $article_id,
            ':user_id' => $this->user['id']
        ]);
        return $stmt->fetch() ? true : false;
    }
}
?>