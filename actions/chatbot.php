<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get user if logged in
$user = getCurrentUser();
$user_id = $user ? $user['id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message = $data['message'] ?? '';
    $session_id = $data['session_id'] ?? session_id();
    $conversation_history = $data['conversation_history'] ?? [];

    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    // Generate AI response (placeholder - would integrate with OpenAI in production)
    $response = generateAIResponse($message, $conversation_history, $db);

    // Save conversation to database
    try {
        $query = "INSERT INTO chatbot_conversations (user_id, session_id, message, response, message_type, context) 
                 VALUES (:user_id, :session_id, :message, :response, 'user', :context)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':session_id' => $session_id,
            ':message' => $message,
            ':response' => $response,
            ':context' => json_encode($conversation_history)
        ]);

        // Save bot response
        $query = "INSERT INTO chatbot_conversations (user_id, session_id, message, response, message_type) 
                 VALUES (:user_id, :session_id, :message, :response, 'bot')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':session_id' => $session_id,
            ':message' => '',
            ':response' => $response
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the request
        error_log('Error saving chatbot conversation: ' . $e->getMessage());
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get conversation history
    $session_id = $_GET['session_id'] ?? session_id();
    
    try {
        $query = "SELECT * FROM chatbot_conversations 
                 WHERE session_id = :session_id OR (user_id = :user_id AND user_id IS NOT NULL)
                 ORDER BY created_at ASC
                 LIMIT 50";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':session_id' => $session_id,
            ':user_id' => $user_id
        ]);
        $conversations = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'conversations' => $conversations
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function generateAIResponse($message, $conversation_history, $db) {
    $message_lower = strtolower($message);
    
    // Check for tribe-specific questions
    $tribe_keywords = ['yoruba', 'igbo', 'hausa', 'akan', 'zulu', 'kikuyu', 'maasai', 'fulani', 'swahili', 'xhosa'];
    $found_tribe = null;
    foreach ($tribe_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            $found_tribe = $keyword;
            break;
        }
    }

    if ($found_tribe) {
        return generateTribeResponse($found_tribe, $db);
    }

    // Check for dowry-related questions
    if (strpos($message_lower, 'dowry') !== false || strpos($message_lower, 'bride price') !== false) {
        return "Dowry (also called bride price or lobola) varies significantly across African tribes. It can include:\n\n" .
               "• **Livestock** (cattle, goats, sheep)\n" .
               "• **Cash/Money** (negotiable amounts)\n" .
               "• **Goods** (cloth, jewelry, household items)\n" .
               "• **Mahr** (Islamic marriage gift)\n\n" .
               "Would you like me to help you calculate dowry for a specific tribe? You can also use our Dowry Calculator tool!";
    }

    // Check for lobola questions
    if (strpos($message_lower, 'lobola') !== false) {
        return "**Lobola** is a traditional Southern African practice, particularly among Zulu, Xhosa, and Ndebele communities.\n\n" .
               "It typically involves:\n" .
               "• Negotiation between families\n" .
               "• Payment in cattle (10-20 cattle is common) or cash equivalent\n" .
               "• Symbolic gesture of respect and commitment\n\n" .
               "The amount is negotiable and depends on family status, education, and regional customs. Would you like more specific information?";
    }

    // Check for compatibility questions
    if (strpos($message_lower, 'compatibility') !== false || strpos($message_lower, 'inter-tribal') !== false) {
        return "Inter-tribal marriages are beautiful and increasingly common! I can help you with:\n\n" .
               "• Understanding how to combine customs from different tribes\n" .
               "• Dowry fusion recommendations\n" .
               "• Planning ceremonies that honor both traditions\n\n" .
               "Try our **Compatibility Matching Tool** for personalized analysis, or ask me about specific tribes you're interested in!";
    }

    // Check for wedding planning
    if (strpos($message_lower, 'wedding') !== false || strpos($message_lower, 'plan') !== false || strpos($message_lower, 'timeline') !== false) {
        return "I can help you plan your traditional wedding! Here's what I recommend:\n\n" .
               "• **Use our Timeline Planner** to create a personalized wedding timeline\n" .
               "• Book consultations with cultural experts\n" .
               "• Plan ceremonies 3-6 months in advance\n" .
               "• Include key milestones: knocking ceremony, engagement, dowry negotiation, and main ceremony\n\n" .
               "Would you like help with a specific aspect of planning?";
    }

    // Check for greeting
    if (strpos($message_lower, 'hello') !== false || strpos($message_lower, 'hi') !== false || strpos($message_lower, 'hey') !== false) {
        return "Hello! I'm here to help you with African marriage traditions, customs, and wedding planning. What would you like to know?";
    }

    // Default response
    return "Thank you for your question! I can help you with:\n\n" .
           "• **Tribal customs** - Ask about specific tribes (Yoruba, Igbo, Zulu, etc.)\n" .
           "• **Dowry information** - Calculations and requirements\n" .
           "• **Wedding planning** - Timelines and ceremonies\n" .
           "• **Compatibility** - Inter-tribal marriage guidance\n" .
           "• **General traditions** - Marriage customs across Africa\n\n" .
           "Try asking: 'Tell me about Yoruba customs' or 'How do I calculate dowry?'";
}

function generateTribeResponse($tribe_name, $db) {
    try {
        $query = "SELECT * FROM tribes WHERE LOWER(name) LIKE :tribe_name LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([':tribe_name' => '%' . $tribe_name . '%']);
        $tribe = $stmt->fetch();

        if ($tribe) {
            $customs = json_decode($tribe['customs'], true) ?: [];
            $customs_list = !empty($customs) ? "\n• " . implode("\n• ", $customs) : "Customs data is being updated.";

            return "**" . $tribe['name'] . "** from **" . $tribe['country'] . "** (" . $tribe['region'] . ")\n\n" .
                   "**Customs:**\n" . $customs_list . "\n\n" .
                   "**Dowry Type:** " . ($tribe['dowry_type'] ?: 'Varies') . "\n" .
                   "**Dowry Details:** " . ($tribe['dowry_details'] ?: 'Consult with family elders') . "\n\n" .
                   "Would you like more detailed information or help planning a " . $tribe['name'] . " wedding?";
        }
    } catch (Exception $e) {
        error_log('Error fetching tribe: ' . $e->getMessage());
    }

    return "I'd love to tell you about " . ucfirst($tribe_name) . " traditions! However, I need more specific information. " .
           "You can browse all tribes on our website or ask me about a specific aspect like 'Yoruba dowry customs' or 'Zulu wedding ceremonies'.";
}

