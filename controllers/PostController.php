<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../helpers/uploads.php';

class PostController extends BaseController {
    
    public function index() {
        $this->requireAuth();
        
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        
        try {
            // Get posts with user information
            $query = "SELECT p.*, u.full_name as author_name, u.email as author_email 
                     FROM user_posts p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.is_public = TRUE 
                     ORDER BY p.created_at DESC 
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll();
            
            // Get likes and comments count for each post
            foreach ($posts as &$post) {
                $post['likes_count'] = $this->getPostLikesCount($post['id']);
                $post['comments_count'] = $this->getPostCommentsCount($post['id']);
                $post['user_liked'] = $this->userLikedPost($post['id']);
            }
            
            $this->sendResponse(true, 'Posts retrieved successfully', $posts);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['title', 'content']);
            
            $query = "INSERT INTO user_posts (user_id, title, content, image_url, is_public) 
                     VALUES (:user_id, :title, :content, :image_url, :is_public)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':user_id' => $this->user['id'],
                ':title' => $this->sanitizeInput($data['title']),
                ':content' => $this->sanitizeInput($data['content']),
                ':image_url' => isset($data['image_url']) ? $this->sanitizeInput($data['image_url']) : null,
                ':is_public' => isset($data['is_public']) ? (bool)$data['is_public'] : true
            ]);
            
            $post_id = $this->db->lastInsertId();
            
            // Get the created post
            $query = "SELECT p.*, u.full_name as author_name, u.email as author_email 
                     FROM user_posts p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $post_id]);
            $post = $stmt->fetch();
            
            $post['likes_count'] = 0;
            $post['comments_count'] = 0;
            $post['user_liked'] = false;
            
            $this->sendResponse(true, 'Post created successfully', $post);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function show($id) {
        $this->requireAuth();
        
        try {
            $query = "SELECT p.*, u.full_name as author_name, u.email as author_email 
                     FROM user_posts p 
                     JOIN users u ON p.user_id = u.id 
                     WHERE p.id = :id AND (p.is_public = TRUE OR p.user_id = :user_id)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':user_id' => $this->user['id']
            ]);
            $post = $stmt->fetch();
            
            if (!$post) {
                $this->sendResponse(false, 'Post not found', null, 404);
            }
            
            // Get comments for this post
            $query = "SELECT c.*, u.full_name as author_name, u.email as author_email 
                     FROM post_comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.post_id = :post_id 
                     ORDER BY c.created_at ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':post_id' => $id]);
            $comments = $stmt->fetchAll();
            
            // Get likes count for comments
            foreach ($comments as &$comment) {
                $comment['likes_count'] = $this->getCommentLikesCount($comment['id']);
                $comment['user_liked'] = $this->userLikedComment($comment['id']);
            }
            
            $post['comments'] = $comments;
            $post['likes_count'] = $this->getPostLikesCount($id);
            $post['user_liked'] = $this->userLikedPost($id);
            
            $this->sendResponse(true, 'Post retrieved successfully', $post);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function addComment($post_id) {
        $this->requireAuth();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $this->validateRequired($data, ['content']);
            
            $query = "INSERT INTO post_comments (post_id, user_id, content) 
                     VALUES (:post_id, :user_id, :content)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':post_id' => $post_id,
                ':user_id' => $this->user['id'],
                ':content' => $this->sanitizeInput($data['content'])
            ]);
            
            $comment_id = $this->db->lastInsertId();
            
            // Update comments count in post
            $query = "UPDATE user_posts SET comments_count = comments_count + 1 WHERE id = :post_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':post_id' => $post_id]);
            
            // Get the created comment
            $query = "SELECT c.*, u.full_name as author_name, u.email as author_email 
                     FROM post_comments c 
                     JOIN users u ON c.user_id = u.id 
                     WHERE c.id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $comment_id]);
            $comment = $stmt->fetch();
            
            $comment['likes_count'] = 0;
            $comment['user_liked'] = false;
            
            $this->sendResponse(true, 'Comment added successfully', $comment);
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function likePost($post_id) {
        $this->requireAuth();
        
        try {
            // Check if user already liked this post
            $query = "SELECT id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':post_id' => $post_id,
                ':user_id' => $this->user['id']
            ]);
            
            if ($stmt->fetch()) {
                // Unlike the post
                $query = "DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':post_id' => $post_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE user_posts SET likes_count = likes_count - 1 WHERE id = :post_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':post_id' => $post_id]);
                
                $this->sendResponse(true, 'Post unliked successfully');
            } else {
                // Like the post
                $query = "INSERT INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':post_id' => $post_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE user_posts SET likes_count = likes_count + 1 WHERE id = :post_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':post_id' => $post_id]);
                
                $this->sendResponse(true, 'Post liked successfully');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    public function likeComment($comment_id) {
        $this->requireAuth();
        
        try {
            // Check if user already liked this comment
            $query = "SELECT id FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':comment_id' => $comment_id,
                ':user_id' => $this->user['id']
            ]);
            
            if ($stmt->fetch()) {
                // Unlike the comment
                $query = "DELETE FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':comment_id' => $comment_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE post_comments SET likes_count = likes_count - 1 WHERE id = :comment_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':comment_id' => $comment_id]);
                
                $this->sendResponse(true, 'Comment unliked successfully');
            } else {
                // Like the comment
                $query = "INSERT INTO comment_likes (comment_id, user_id) VALUES (:comment_id, :user_id)";
                $stmt = $this->db->prepare($query);
                $stmt->execute([
                    ':comment_id' => $comment_id,
                    ':user_id' => $this->user['id']
                ]);
                
                // Update likes count
                $query = "UPDATE post_comments SET likes_count = likes_count + 1 WHERE id = :comment_id";
                $stmt = $this->db->prepare($query);
                $stmt->execute([':comment_id' => $comment_id]);
                
                $this->sendResponse(true, 'Comment liked successfully');
            }
        } catch (Exception $e) {
            $this->sendResponse(false, $e->getMessage(), null, 500);
        }
    }
    
    // Helper methods
    private function getPostLikesCount($post_id) {
        $query = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = :post_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':post_id' => $post_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    private function getPostCommentsCount($post_id) {
        $query = "SELECT COUNT(*) as count FROM post_comments WHERE post_id = :post_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':post_id' => $post_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    private function getCommentLikesCount($comment_id) {
        $query = "SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = :comment_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':comment_id' => $comment_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    private function userLikedPost($post_id) {
        if (!$this->user) return false;
        
        $query = "SELECT id FROM post_likes WHERE post_id = :post_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $this->user['id']
        ]);
        return $stmt->fetch() ? true : false;
    }
    
    private function userLikedComment($comment_id) {
        if (!$this->user) return false;
        
        $query = "SELECT id FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':comment_id' => $comment_id,
            ':user_id' => $this->user['id']
        ]);
        return $stmt->fetch() ? true : false;
    }
}
?>