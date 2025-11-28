<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community - AfroMarry</title>
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

    <div class="community-container">
        <div class="community-header">
            <h1>Community</h1>
            <p>Connect with others, share experiences, and celebrate African marriage traditions</p>
        </div>

        <div class="community-content">
            <!-- Create Post Form -->
            <div class="create-post-card">
                <div class="post-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4><?php echo $user['full_name']; ?></h4>
                        <p>Share your thoughts with the community</p>
                    </div>
                </div>
                <form id="create-post-form" class="post-form">
                    <textarea id="post-content" placeholder="What would you like to share with the community?" required></textarea>
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Post</button>
                    </div>
                </form>
            </div>

            <!-- Posts Feed -->
            <div id="posts-feed" class="posts-feed">
                <!-- Posts will be loaded here dynamically -->
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading community posts...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Community functionality
        document.addEventListener('DOMContentLoaded', function() {
            loadPosts();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Create post form submission
            const postForm = document.getElementById('create-post-form');
            if (postForm) {
                postForm.addEventListener('submit', createPost);
            }
        }

        async function loadPosts() {
            try {
                const response = await fetch('<?php echo action_url('posts.php'); ?>');
                const result = await response.json();
                
                if (result.success) {
                    displayPosts(result.data);
                } else {
                    showErrorMessage('Failed to load posts: ' + result.message);
                }
            } catch (error) {
                showErrorMessage('Error loading posts: ' + error.message);
            }
        }

        function displayPosts(posts) {
            const feedContainer = document.getElementById('posts-feed');
            if (!feedContainer) return;
            
            if (!posts || posts.length === 0) {
                feedContainer.innerHTML = '<div class="no-posts"><p>No posts yet. Be the first to share!</p></div>';
                return;
            }
            
            let postsHTML = '';
            posts.forEach(post => {
                postsHTML += `
                    <div class="post-card" data-post-id="${post.id}">
                        <div class="post-header">
                            <div class="post-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="author-info">
                                    <h4>${post.author_name}</h4>
                                    <p>${new Date(post.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                        <div class="post-content">
                            <h3>${post.title}</h3>
                            <p>${post.content}</p>
                        </div>
                        <div class="post-actions">
                            <button class="action-button like-button" onclick="likePost(${post.id})">
                                <i class="fas fa-heart${post.user_liked ? ' liked' : ''}"></i>
                                <span>${post.likes_count}</span>
                            </button>
                            <button class="action-button comment-button" onclick="showComments(${post.id})">
                                <i class="fas fa-comment"></i>
                                <span>${post.comments_count}</span>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            feedContainer.innerHTML = postsHTML;
        }

        async function createPost(e) {
            e.preventDefault();
            
            const content = document.getElementById('post-content').value.trim();
            if (!content) return;
            
            try {
                const response = await fetch('<?php echo action_url('posts.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: 'Community Post',
                        content: content
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('post-content').value = '';
                    loadPosts(); // Refresh posts
                    showMessage('Post created successfully!', 'success');
                } else {
                    showErrorMessage('Failed to create post: ' + result.message);
                }
            } catch (error) {
                showErrorMessage('Error creating post: ' + error.message);
            }
        }

        async function likePost(postId) {
            try {
                const response = await fetch(`<?php echo action_url('posts.php'); ?>/${postId}/like`, {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadPosts(); // Refresh posts to show updated like count
                } else {
                    showErrorMessage('Failed to like post: ' + result.message);
                }
            } catch (error) {
                showErrorMessage('Error liking post: ' + error.message);
            }
        }

        function showComments(postId) {
            // Implementation for showing comments
            alert('Comments feature coming soon!');
        }

        function showMessage(message, type = 'info') {
            // Simple message display
            const messageEl = document.createElement('div');
            messageEl.className = `message ${type}`;
            messageEl.textContent = message;
            document.body.appendChild(messageEl);
            
            setTimeout(() => {
                messageEl.remove();
            }, 3000);
        }

        function showErrorMessage(message) {
            showMessage(message, 'error');
        }
    </script>

    <style>
        .community-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .community-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .community-header h1 {
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .community-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .create-post-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .post-author {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .author-info h4 {
            margin: 0;
            color: #1f2937;
        }
        
        .author-info p {
            margin: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .post-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            margin-bottom: 1rem;
        }
        
        .post-form textarea:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
        }
        
        .posts-feed {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .post-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .post-header {
            margin-bottom: 1rem;
        }
        
        .post-content h3 {
            margin: 0 0 1rem 0;
            color: #1f2937;
        }
        
        .post-content p {
            color: #374151;
            line-height: 1.6;
            margin: 0;
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .action-button {
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
        
        .action-button:hover {
            background: #f3f4f6;
            color: #1f2937;
        }
        
        .action-button.like-button .liked {
            color: #ef4444;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }
        
        .loading-spinner i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #8B5CF6;
        }
        
        .no-posts {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        
        .message.success {
            background: #10B981;
        }
        
        .message.error {
            background: #EF4444;
        }
        
        .message.info {
            background: #3B82F6;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .community-container {
                margin: 1rem;
            }
            
            .create-post-card,
            .post-card {
                padding: 1rem;
            }
        }
    </style>
</body>
</html>