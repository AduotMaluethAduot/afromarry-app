<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get all tribes for quiz results
$query = "SELECT id, name, country, region FROM tribes ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$allTribes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tribe Discovery Quiz - AfroMarry</title>
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
                <a href="<?php echo page_url('dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?php echo auth_url('logout.php'); ?>" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <?php
    // Get premium expiration for sidebar
    $premium_expires = null;
    if ($user['is_premium'] ?? false) {
        $premium_expires = $user['premium_expires_at'] ?? null;
    }
    ?>

    <div class="dashboard-container">
        <?php include 'includes/dashboard-sidebar.php'; ?>
        
        <div class="dashboard-content">
            <div class="container" style="max-width: 800px; margin: 0 auto;">
        <div class="quiz-container">
            <h1 class="section-title">
                <i class="fas fa-question-circle"></i>
                Discover Your Partner's Tribe
            </h1>
            <p class="text-center text-gray-600 mb-4">
                Answer these questions to discover which African tribe your partner's background might belong to
            </p>

            <div id="quiz-progress" class="quiz-progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                <span id="progress-text">Question <span id="current-question">1</span> of <span id="total-questions">10</span></span>
            </div>

            <form id="quiz-form">
                <div id="quiz-questions"></div>
                
                <div class="quiz-actions" id="quiz-actions" style="display: none;">
                    <button type="button" id="prev-btn" class="btn-secondary" onclick="previousQuestion()" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" id="next-btn" class="btn-primary" onclick="nextQuestion()">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" id="submit-btn" class="btn-primary" style="display: none;">
                        <i class="fas fa-check"></i> Get Results
                    </button>
                </div>
            </form>

            <div id="quiz-results" style="display: none;">
                <div class="quiz-result-card">
                    <h2><i class="fas fa-star"></i> Your Results</h2>
                    <div id="result-content"></div>
                    <div class="quiz-result-actions">
                        <button class="btn-primary" onclick="window.location.href=pageUrl('quiz.php')">
                            <i class="fas fa-redo"></i> Take Quiz Again
                        </button>
                        <button class="btn-secondary" onclick="window.location.href=pageUrl('compatibility-match.php?tribe_id=' + getResultTribeId())">
                            <i class="fas fa-heart"></i> Check Compatibility
                        </button>
                        <button class="btn-secondary" onclick="window.location.href=baseUrl('index.php#tribes')">
                            <i class="fas fa-search"></i> Explore Tribes
                        </button>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>

    <script>
        const quizData = {
            currentQuestion: 0,
            answers: {},
            questions: [
                {
                    id: 1,
                    question: "Which region of Africa is your partner's family from?",
                    type: "single",
                    options: [
                        { value: "east", label: "East Africa (Kenya, Tanzania, Uganda, etc.)" },
                        { value: "west", label: "West Africa (Nigeria, Ghana, Senegal, etc.)" },
                        { value: "southern", label: "Southern Africa (South Africa, Zimbabwe, etc.)" },
                        { value: "north", label: "North Africa (Egypt, Morocco, Algeria, etc.)" },
                        { value: "central", label: "Central Africa (DRC, Cameroon, CAR, etc.)" }
                    ]
                },
                {
                    id: 2,
                    question: "What type of dowry system does their family practice?",
                    type: "single",
                    options: [
                        { value: "livestock", label: "Livestock (cattle, goats, sheep)" },
                        { value: "cash", label: "Cash/Money" },
                        { value: "goods", label: "Goods (cloth, jewelry, household items)" },
                        { value: "mahr", label: "Mahr (Islamic marriage gift)" },
                        { value: "mixed", label: "Mixed (combination of above)" }
                    ]
                },
                {
                    id: 3,
                    question: "What languages does your partner's family speak?",
                    type: "multiple",
                    options: [
                        { value: "english", label: "English" },
                        { value: "french", label: "French" },
                        { value: "arabic", label: "Arabic" },
                        { value: "swahili", label: "Swahili" },
                        { value: "portuguese", label: "Portuguese" },
                        { value: "local", label: "Local/Tribal Language" }
                    ]
                },
                {
                    id: 4,
                    question: "What is the primary religion in their family?",
                    type: "single",
                    options: [
                        { value: "christian", label: "Christianity" },
                        { value: "islam", label: "Islam" },
                        { value: "traditional", label: "Traditional African Religion" },
                        { value: "mixed", label: "Mixed/Other" }
                    ]
                },
                {
                    id: 5,
                    question: "Which ceremonies are important in their culture?",
                    type: "multiple",
                    options: [
                        { value: "knocking", label: "Knocking/Introduction Ceremony" },
                        { value: "lobola", label: "Lobola Negotiations" },
                        { value: "engagement", label: "Engagement Ceremony" },
                        { value: "traditional_wedding", label: "Traditional Wedding" },
                        { value: "blessing", label: "Ancestral Blessing" },
                        { value: "gift_exchange", label: "Gift Exchange Ceremony" }
                    ]
                },
                {
                    id: 6,
                    question: "How traditional is their family approach to marriage?",
                    type: "single",
                    options: [
                        { value: "very_traditional", label: "Very Traditional (strict customs)" },
                        { value: "traditional", label: "Traditional (follows customs)" },
                        { value: "moderate", label: "Moderate (blends tradition with modern)" },
                        { value: "modern", label: "Modern (flexible customs)" }
                    ]
                },
                {
                    id: 7,
                    question: "What type of traditional attire is important?",
                    type: "single",
                    options: [
                        { value: "kente", label: "Kente/Ankara (West Africa)" },
                        { value: "dashiki", label: "Dashiki/Kaftan (West/North Africa)" },
                        { value: "traditional_robes", label: "Traditional Robes" },
                        { value: "beadwork", label: "Beadwork (East/Southern Africa)" },
                        { value: "not_important", label: "Not particularly important" }
                    ]
                },
                {
                    id: 8,
                    question: "What role do elders play in marriage arrangements?",
                    type: "single",
                    options: [
                        { value: "central", label: "Central (elders lead negotiations)" },
                        { value: "important", label: "Important (consulted regularly)" },
                        { value: "moderate", label: "Moderate (involved but not leading)" },
                        { value: "minimal", label: "Minimal (couple decides)" }
                    ]
                },
                {
                    id: 9,
                    question: "How many family members typically participate in wedding ceremonies?",
                    type: "single",
                    options: [
                        { value: "large", label: "Large (50+ people)" },
                        { value: "medium", label: "Medium (20-50 people)" },
                        { value: "small", label: "Small (10-20 people)" },
                        { value: "intimate", label: "Intimate (less than 10 people)" }
                    ]
                },
                {
                    id: 10,
                    question: "What special customs does their family practice?",
                    type: "multiple",
                    options: [
                        { value: "polygamy", label: "Polygamy is acceptable" },
                        { value: "arranged", label: "Arranged marriages" },
                        { value: "initiation", label: "Initiation ceremonies" },
                        { value: "dowry_negotiation", label: "Formal dowry negotiations" },
                        { value: "feasting", label: "Large feasting ceremonies" },
                        { value: "ancestral", label: "Ancestral blessings" }
                    ]
                }
            ],
            tribes: <?php echo json_encode($allTribes); ?>
        };

        let resultTribeId = null;

        function initializeQuiz() {
            renderQuestion(0);
            updateProgress();
        }

        function renderQuestion(index) {
            const question = quizData.questions[index];
            const container = document.getElementById('quiz-questions');
            
            container.innerHTML = `
                <div class="quiz-question-card">
                    <h3 class="quiz-question-title">${question.question}</h3>
                    <div class="quiz-options">
                        ${question.options.map((option, optIdx) => `
                            <label class="quiz-option ${question.type === 'multiple' ? 'checkbox' : 'radio'}">
                                <input 
                                    type="${question.type === 'multiple' ? 'checkbox' : 'radio'}" 
                                    name="question_${question.id}" 
                                    value="${option.value}"
                                    ${quizData.answers[question.id]?.includes(option.value) ? 'checked' : ''}
                                >
                                <span class="option-label">${option.label}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;

            // Show/hide navigation buttons
            document.getElementById('prev-btn').style.display = index === 0 ? 'none' : 'inline-block';
            document.getElementById('next-btn').style.display = index === quizData.questions.length - 1 ? 'none' : 'inline-block';
            document.getElementById('submit-btn').style.display = index === quizData.questions.length - 1 ? 'inline-block' : 'none';
            
            document.getElementById('quiz-actions').style.display = 'block';
        }

        function nextQuestion() {
            saveCurrentAnswer();
            if (quizData.currentQuestion < quizData.questions.length - 1) {
                quizData.currentQuestion++;
                renderQuestion(quizData.currentQuestion);
                updateProgress();
            }
        }

        function previousQuestion() {
            if (quizData.currentQuestion > 0) {
                quizData.currentQuestion--;
                renderQuestion(quizData.currentQuestion);
                updateProgress();
            }
        }

        function saveCurrentAnswer() {
            const question = quizData.questions[quizData.currentQuestion];
            const inputs = document.querySelectorAll(`input[name="question_${question.id}"]:checked`);
            
            if (question.type === 'multiple') {
                quizData.answers[question.id] = Array.from(inputs).map(input => input.value);
            } else {
                quizData.answers[question.id] = inputs.length > 0 ? [inputs[0].value] : [];
            }
        }

        function updateProgress() {
            const progress = ((quizData.currentQuestion + 1) / quizData.questions.length) * 100;
            document.getElementById('progress-fill').style.width = progress + '%';
            document.getElementById('current-question').textContent = quizData.currentQuestion + 1;
        }

        function getResultTribeId() {
            return resultTribeId;
        }

        document.getElementById('quiz-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            saveCurrentAnswer();

            // Calculate results
            const results = calculateQuizResults();
            
            // Save to database
            try {
                const response = await fetch(actionUrl('quiz.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        quiz_type: 'tribe_discovery',
                        answers: quizData.answers,
                        result_tribe_id: results.tribeId,
                        result_data: results,
                        score: results.score
                    })
                });

                const data = await response.json();
                if (data.success) {
                    displayResults(results);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error saving quiz results:', error);
                displayResults(results); // Still show results even if save fails
            }
        });

        function calculateQuizResults() {
            // Simple scoring algorithm
            const scores = {};
            
            quizData.questions.forEach((q, idx) => {
                const answers = quizData.answers[q.id] || [];
                
                // Match answers to tribes based on characteristics
                quizData.tribes.forEach(tribe => {
                    if (!scores[tribe.id]) scores[tribe.id] = 0;
                    
                    // Region match (high weight)
                    if (idx === 0 && answers[0]) {
                        const regionMap = {
                            'east': 'East Africa',
                            'west': 'West Africa',
                            'southern': 'Southern Africa',
                            'north': 'North Africa',
                            'central': 'Central Africa'
                        };
                        if (tribe.region === regionMap[answers[0]]) {
                            scores[tribe.id] += 30;
                        }
                    }
                });
            });

            // Find best match
            let maxScore = 0;
            let bestTribe = null;
            
            Object.keys(scores).forEach(tribeId => {
                if (scores[tribeId] > maxScore) {
                    maxScore = scores[tribeId];
                    bestTribe = quizData.tribes.find(t => t.id == tribeId);
                }
            });

            resultTribeId = bestTribe ? bestTribe.id : null;
            
            return {
                tribeId: resultTribeId,
                tribe: bestTribe,
                score: maxScore,
                recommendations: generateRecommendations(bestTribe),
                allScores: scores
            };
        }

        function generateRecommendations(tribe) {
            if (!tribe) return [];
            
            return [
                `Learn more about ${tribe.name} traditions from ${tribe.country}`,
                `Consult with a ${tribe.name} cultural expert`,
                `Explore ${tribe.name} dowry customs and requirements`,
                `Connect with ${tribe.name} community members`
            ];
        }

        function displayResults(results) {
            document.getElementById('quiz-questions').style.display = 'none';
            document.getElementById('quiz-actions').style.display = 'none';
            document.getElementById('quiz-progress').style.display = 'none';
            
            const resultsContainer = document.getElementById('quiz-results');
            const content = document.getElementById('result-content');
            
            if (results.tribe) {
                content.innerHTML = `
                    <div class="result-tribe-card">
                        <div class="result-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>${results.tribe.name}</h3>
                        <p class="result-location">${results.tribe.country} • ${results.tribe.region}</p>
                        <div class="result-score">
                            <span>Match Score: ${results.score}%</span>
                        </div>
                    </div>
                    <div class="result-recommendations">
                        <h4><i class="fas fa-lightbulb"></i> Recommendations</h4>
                        <ul>
                            ${results.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                `;
            } else {
                content.innerHTML = `
                    <div class="result-no-match">
                        <i class="fas fa-info-circle"></i>
                        <p>We couldn't determine a specific match. Try exploring tribes manually or consult with our experts!</p>
                    </div>
                `;
            }
            
            resultsContainer.style.display = 'block';
        }

        // Initialize on page load
        initializeQuiz();
    </script>

    <style>
        .quiz-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .quiz-progress {
            margin: 2rem 0;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #d4af37, #f4d03f);
            transition: width 0.3s ease;
        }

        .quiz-question-card {
            margin: 2rem 0;
        }

        .quiz-question-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: #333;
        }

        .quiz-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .quiz-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quiz-option:hover {
            border-color: #d4af37;
            background: #fffbf0;
        }

        .quiz-option input[type="radio"]:checked + .option-label,
        .quiz-option input[type="checkbox"]:checked + .option-label {
            font-weight: bold;
            color: #d4af37;
        }

        .quiz-option input[type="radio"]:checked ~ span,
        .quiz-option input[type="checkbox"]:checked ~ span {
            color: #d4af37;
        }

        .quiz-option input {
            margin-right: 1rem;
            cursor: pointer;
        }

        .quiz-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .quiz-result-card {
            text-align: center;
            padding: 2rem;
        }

        .result-tribe-card {
            background: linear-gradient(135deg, #d4af37, #f4d03f);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
        }

        .result-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .result-score {
            margin-top: 1rem;
            font-size: 1.2rem;
        }

        .result-recommendations {
            text-align: left;
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .result-recommendations ul {
            list-style: none;
            padding: 0;
        }

        .result-recommendations li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }

        .result-recommendations li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #d4af37;
            font-weight: bold;
        }

        .quiz-result-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
    </style>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
</body>
</html>

