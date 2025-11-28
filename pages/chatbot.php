<?php
require_once '../config/database.php';

// Chatbot works for both logged-in and anonymous users
$user = getCurrentUser();
$session_id = session_id();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chatbot - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo base_url('index.php'); ?>"><i class="fas fa-heart"></i><span>AfroMarry</span></a>
            </div>
            <div class="nav-menu">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo page_url('dashboard.php'); ?>" class="nav-link">Dashboard</a>
                    <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
                <?php else: ?>
                    <a href="<?php echo auth_url('login.php'); ?>" class="nav-link">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isLoggedIn()): ?>
        <?php
        // Get premium expiration for sidebar
        $premium_expires = null;
        if ($user && ($user['is_premium'] ?? false)) {
            $premium_expires = $user['premium_expires_at'] ?? null;
        }
        ?>
        <div class="dashboard-container">
            <?php include 'includes/dashboard-sidebar.php'; ?>
            
            <div class="dashboard-content">
                <div class="container" style="max-width: 900px; margin: 0 auto; padding: 0 1rem;">
    <?php else: ?>
        <div class="container" style="max-width: 900px; margin: 2rem auto; padding: 0 1rem;">
    <?php endif; ?>
        <div class="chatbot-container">
            <div class="chatbot-header">
                <div class="chatbot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chatbot-info">
                    <h2>AfroMarry AI Assistant</h2>
                    <p>24/7 Cultural Marriage Guidance</p>
                    <span class="status-badge online"><i class="fas fa-circle"></i> Online</span>
                </div>
            </div>

            <div class="chatbot-messages" id="chatbot-messages">
                <div class="message bot-message">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>Hello! I'm your AfroMarry AI assistant. I can help you with:</p>
                        <ul>
                            <li>Questions about African marriage traditions</li>
                            <li>Dowry calculations and customs</li>
                            <li>Tribe-specific information</li>
                            <li>Wedding planning advice</li>
                            <li>Compatibility questions</li>
                        </ul>
                        <p>What would you like to know?</p>
                    </div>
                </div>
            </div>

            <div class="chatbot-input-container">
                <div class="quick-questions">
                    <button class="quick-question-btn" onclick="sendQuickQuestion('Tell me about Yoruba marriage customs')">
                        Yoruba Customs
                    </button>
                    <button class="quick-question-btn" onclick="sendQuickQuestion('How do I calculate dowry?')">
                        Dowry Calculator
                    </button>
                    <button class="quick-question-btn" onclick="sendQuickQuestion('What is lobola?')">
                        What is Lobola?
                    </button>
                </div>
                <form id="chatbot-form" class="chatbot-form">
                    <input type="text" id="chatbot-input" placeholder="Type your question here..." required>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const userId = <?php echo $user ? $user['id'] : 'null'; ?>;
        const sessionId = '<?php echo $session_id; ?>';
        let conversationHistory = [];

        document.getElementById('chatbot-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const input = document.getElementById('chatbot-input');
            const message = input.value.trim();
            
            if (!message) return;

            // Add user message to chat
            addMessage(message, 'user');
            input.value = '';

            // Show typing indicator
            showTypingIndicator();

            try {
                // Get AI response
                const response = await fetch(actionUrl('chatbot.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        user_id: userId,
                        session_id: sessionId,
                        conversation_history: conversationHistory
                    })
                });

                const data = await response.json();
                
                // Hide typing indicator
                hideTypingIndicator();

                if (data.success) {
                    addMessage(data.response, 'bot');
                    
                    // Add to conversation history
                    conversationHistory.push({ role: 'user', content: message });
                    conversationHistory.push({ role: 'assistant', content: data.response });
                } else {
                    addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                }
            } catch (error) {
                hideTypingIndicator();
                console.error('Error:', error);
                addMessage('Sorry, I\'m having trouble connecting. Please try again later.', 'bot');
            }
        });

        function addMessage(text, type) {
            const messagesContainer = document.getElementById('chatbot-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;

            if (type === 'bot') {
                messageDiv.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <p>${formatMessage(text)}</p>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="message-content">
                        <p>${formatMessage(text)}</p>
                    </div>
                    <div class="message-avatar user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                `;
            }

            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function formatMessage(text) {
            // Convert markdown-style formatting
            return text
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');
        }

        function showTypingIndicator() {
            const messagesContainer = document.getElementById('chatbot-messages');
            const typingDiv = document.createElement('div');
            typingDiv.className = 'message bot-message typing-indicator';
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function hideTypingIndicator() {
            const typingIndicator = document.getElementById('typing-indicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function sendQuickQuestion(question) {
            document.getElementById('chatbot-input').value = question;
            document.getElementById('chatbot-form').dispatchEvent(new Event('submit'));
        }

        // Load conversation history on page load
        window.addEventListener('DOMContentLoaded', async function() {
            if (userId || sessionId) {
                try {
                    const response = await fetch(actionUrl(`chatbot.php?session_id=${sessionId}`));
                    const data = await response.json();
                    
                    if (data.success && data.conversations) {
                        // Display recent conversations
                        data.conversations.slice(-10).forEach(conv => {
                            if (conv.message_type === 'user') {
                                addMessage(conv.message, 'user');
                            } else if (conv.message_type === 'bot') {
                                addMessage(conv.response, 'bot');
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error loading conversation history:', error);
                }
            }
        });
    </script>

    <style>
        .chatbot-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }

        .chatbot-header {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .chatbot-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .chatbot-info h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .chatbot-info p {
            margin: 0.25rem 0;
            opacity: 0.9;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .status-badge.online {
            color: #4caf50;
        }

        .chatbot-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #f5f5f5;
        }

        .message {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            animation: fadeIn 0.3s;
        }

        .bot-message {
            justify-content: flex-start;
        }

        .user-message {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #d4af37;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .user-avatar {
            background: #4caf50;
        }

        .message-content {
            max-width: 70%;
            background: white;
            padding: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-message .message-content {
            background: #d4af37;
            color: white;
        }

        .typing-indicator .message-content {
            background: white;
            padding: 0.5rem 1rem;
        }

        .typing-dots {
            display: flex;
            gap: 0.5rem;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d4af37;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chatbot-input-container {
            background: white;
            padding: 1rem;
            border-top: 1px solid #e0e0e0;
        }

        .quick-questions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .quick-question-btn {
            padding: 0.5rem 1rem;
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .quick-question-btn:hover {
            background: #d4af37;
            color: white;
            border-color: #d4af37;
        }

        .chatbot-form {
            display: flex;
            gap: 0.5rem;
        }

        .chatbot-form input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1rem;
        }

        .send-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #d4af37;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .send-btn:hover {
            background: #c19a26;
            transform: scale(1.05);
        }
    </style>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
    <?php if (isLoggedIn()): ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>

