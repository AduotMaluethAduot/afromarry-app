<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get tribe IDs from query or default
$tribe1_id = $_GET['tribe1_id'] ?? $_GET['tribe_id'] ?? null;
$tribe2_id = $_GET['tribe2_id'] ?? null;

// Get all tribes
$query = "SELECT id, name, country, region, customs, dowry_type, dowry_details FROM tribes ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$allTribes = $stmt->fetchAll();

// Decode customs
foreach ($allTribes as &$tribe) {
    $tribe['customs'] = json_decode($tribe['customs'], true) ?: [];
}

// Get selected tribes
$tribe1 = null;
$tribe2 = null;
if ($tribe1_id) {
    $tribe1 = array_filter($allTribes, fn($t) => $t['id'] == $tribe1_id);
    $tribe1 = reset($tribe1);
}
if ($tribe2_id) {
    $tribe2 = array_filter($allTribes, fn($t) => $t['id'] == $tribe2_id);
    $tribe2 = reset($tribe2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compatibility Matching - AfroMarry</title>
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
            <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0 1rem;">
        <h1 class="section-title">
            <i class="fas fa-heart"></i>
            Inter-Tribal Marriage Compatibility
        </h1>
        <p class="text-center text-gray-600 mb-4">
            Discover how two different tribes' customs can be harmoniously combined
        </p>

        <div class="compatibility-form-card">
            <form id="compatibility-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Partner's Tribe</label>
                        <select id="tribe1-select" name="tribe1_id" required>
                            <option value="">Select Tribe</option>
                            <?php foreach ($allTribes as $tribe): ?>
                                <option value="<?php echo $tribe['id']; ?>" <?php echo ($tribe1 && $tribe1['id'] == $tribe['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tribe['name'] . ' - ' . $tribe['country']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Second Partner's Tribe</label>
                        <select id="tribe2-select" name="tribe2_id" required>
                            <option value="">Select Tribe</option>
                            <?php foreach ($allTribes as $tribe): ?>
                                <option value="<?php echo $tribe['id']; ?>" <?php echo ($tribe2 && $tribe2['id'] == $tribe['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tribe['name'] . ' - ' . $tribe['country']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-large">
                    <i class="fas fa-search"></i> Analyze Compatibility
                </button>
            </form>
        </div>

        <div id="compatibility-results" style="display: none;">
            <div class="compatibility-score-card">
                <h2>Compatibility Score</h2>
                <div class="score-circle" id="score-circle">
                    <span id="score-value">0</span>
                    <small>%</small>
                </div>
            </div>

            <div class="compatibility-sections">
                <div class="compatibility-section">
                    <h3><i class="fas fa-gift"></i> Dowry Fusion Recommendations</h3>
                    <div id="dowry-fusion"></div>
                </div>

                <div class="compatibility-section">
                    <h3><i class="fas fa-lightbulb"></i> Recommendations</h3>
                    <div id="recommendations"></div>
                </div>

                <div class="compatibility-section">
                    <h3><i class="fas fa-exclamation-triangle"></i> Potential Challenges</h3>
                    <div id="challenges"></div>
                </div>

                <div class="compatibility-section">
                    <h3><i class="fas fa-check-circle"></i> Suggested Solutions</h3>
                    <div id="solutions"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allTribes = <?php echo json_encode($allTribes); ?>;

        document.getElementById('compatibility-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const tribe1Id = parseInt(formData.get('tribe1_id'));
            const tribe2Id = parseInt(formData.get('tribe2_id'));
            
            if (!tribe1Id || !tribe2Id) {
                alert('Please select both tribes');
                return;
            }

            const tribe1 = allTribes.find(t => t.id == tribe1Id);
            const tribe2 = allTribes.find(t => t.id == tribe2Id);

            // Calculate compatibility
            const compatibility = calculateCompatibility(tribe1, tribe2);

            // Save to database
            try {
                const response = await fetch(actionUrl('compatibility.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        tribe_1_id: tribe1Id,
                        tribe_2_id: tribe2Id,
                        compatibility_score: compatibility.score,
                        dowry_fusion: compatibility.dowryFusion,
                        recommendations: compatibility.recommendations,
                        challenges: compatibility.challenges,
                        solutions: compatibility.solutions
                    })
                });

                const data = await response.json();
                if (data.success) {
                    displayResults(compatibility, tribe1, tribe2);
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error saving compatibility:', error);
                displayResults(compatibility, tribe1, tribe2);
            }
        });

        function calculateCompatibility(tribe1, tribe2) {
            let score = 50; // Base score

            // Region similarity (max +20 points)
            if (tribe1.region === tribe2.region) {
                score += 20;
            }

            // Dowry type compatibility (max +15 points)
            const dowryTypes = [tribe1.dowry_type, tribe2.dowry_type];
            if (new Set(dowryTypes).size === 1) {
                score += 15;
            } else if (dowryTypes.some(t => t && t.toLowerCase().includes('livestock')) && 
                       dowryTypes.some(t => t && t.toLowerCase().includes('livestock'))) {
                score += 10;
            }

            // Customs overlap (max +15 points)
            const customs1 = tribe1.customs || [];
            const customs2 = tribe2.customs || [];
            const commonCustoms = customs1.filter(c => customs2.includes(c));
            score += Math.min(commonCustoms.length * 3, 15);

            // Generate dowry fusion
            const dowryFusion = generateDowryFusion(tribe1, tribe2);

            // Generate recommendations
            const recommendations = generateRecommendations(tribe1, tribe2, score);
            
            // Generate challenges
            const challenges = generateChallenges(tribe1, tribe2);
            
            // Generate solutions
            const solutions = generateSolutions(tribe1, tribe2, challenges);

            return {
                score: Math.min(Math.round(score), 100),
                dowryFusion,
                recommendations,
                challenges,
                solutions
            };
        }

        function generateDowryFusion(tribe1, tribe2) {
            const fusion = {
                approach: 'Combined',
                details: []
            };

            if (tribe1.dowry_type && tribe2.dowry_type) {
                if (tribe1.dowry_type === tribe2.dowry_type) {
                    fusion.approach = 'Unified';
                    fusion.details.push(`Both tribes use ${tribe1.dowry_type}, making negotiation straightforward.`);
                    fusion.details.push(`Recommended: Follow ${tribe1.name} or ${tribe2.name} traditional amounts, with flexibility.`);
                } else {
                    fusion.approach = 'Hybrid';
                    fusion.details.push(`Combine ${tribe1.dowry_type} from ${tribe1.name} with ${tribe2.dowry_type} from ${tribe2.name}.`);
                    fusion.details.push(`Suggested: 50% ${tribe1.dowry_type}, 50% ${tribe2.dowry_type}, or cash equivalent.`);
                }
            }

            return fusion;
        }

        function generateRecommendations(tribe1, tribe2, score) {
            const recommendations = [];
            
            if (score >= 80) {
                recommendations.push('High compatibility! Both tribes share many similar customs.');
                recommendations.push(`Consider a unified ceremony honoring both ${tribe1.name} and ${tribe2.name} traditions.`);
            } else if (score >= 60) {
                recommendations.push('Good compatibility with some differences to navigate.');
                recommendations.push(`Plan separate ceremonies or a combined ceremony with clear representation of both cultures.`);
            } else {
                recommendations.push('Moderate compatibility - significant cultural differences to address.');
                recommendations.push(`Strongly recommend consulting with cultural experts from both tribes.`);
            }

            recommendations.push(`Engage elders from both ${tribe1.name} and ${tribe2.name} communities early.`);
            recommendations.push(`Use our timeline planner to schedule ceremonies that respect both traditions.`);

            return recommendations;
        }

        function generateChallenges(tribe1, tribe2) {
            const challenges = [];

            if (tribe1.region !== tribe2.region) {
                challenges.push({
                    title: 'Regional Differences',
                    description: `${tribe1.name} (${tribe1.region}) and ${tribe2.name} (${tribe2.region}) come from different regions with distinct customs.`
                });
            }

            if (tribe1.dowry_type !== tribe2.dowry_type) {
                challenges.push({
                    title: 'Different Dowry Systems',
                    description: `One tribe uses ${tribe1.dowry_type} while the other uses ${tribe2.dowry_type}, requiring negotiation.`
                });
            }

            challenges.push({
                title: 'Language Barriers',
                description: 'Family members may speak different languages, requiring translation services.'
            });

            return challenges;
        }

        function generateSolutions(tribe1, tribe2, challenges) {
            const solutions = [];

            solutions.push({
                title: 'Book Expert Consultations',
                description: `Consult with ${tribe1.name} and ${tribe2.name} cultural experts to understand both traditions.`
            });

            solutions.push({
                title: 'Create Hybrid Timeline',
                description: 'Use our wedding timeline planner to schedule ceremonies that accommodate both traditions.'
            });

            solutions.push({
                title: 'Engage Family Elders',
                description: 'Have family elders from both sides meet early to discuss and harmonize customs.'
            });

            if (challenges.some(c => c.title === 'Different Dowry Systems')) {
                solutions.push({
                    title: 'Flexible Dowry Approach',
                    description: 'Consider a cash equivalent or hybrid dowry that satisfies both traditions.'
                });
            }

            return solutions;
        }

        function displayResults(compatibility, tribe1, tribe2) {
            document.getElementById('compatibility-results').style.display = 'block';
            
            // Display score
            document.getElementById('score-value').textContent = compatibility.score;
            const scoreCircle = document.getElementById('score-circle');
            scoreCircle.style.background = `conic-gradient(
                ${getScoreColor(compatibility.score)} 0% ${compatibility.score}%,
                #e0e0e0 ${compatibility.score}% 100%
            )`;

            // Display dowry fusion
            const dowryFusionDiv = document.getElementById('dowry-fusion');
            dowryFusionDiv.innerHTML = `
                <p><strong>Approach:</strong> ${compatibility.dowryFusion.approach}</p>
                <ul>
                    ${compatibility.dowryFusion.details.map(d => `<li>${d}</li>`).join('')}
                </ul>
            `;

            // Display recommendations
            document.getElementById('recommendations').innerHTML = `
                <ul>
                    ${compatibility.recommendations.map(r => `<li>${r}</li>`).join('')}
                </ul>
            `;

            // Display challenges
            document.getElementById('challenges').innerHTML = `
                ${compatibility.challenges.map(c => `
                    <div class="challenge-item">
                        <h4>${c.title}</h4>
                        <p>${c.description}</p>
                    </div>
                `).join('')}
            `;

            // Display solutions
            document.getElementById('solutions').innerHTML = `
                ${compatibility.solutions.map(s => `
                    <div class="solution-item">
                        <h4>${s.title}</h4>
                        <p>${s.description}</p>
                    </div>
                `).join('')}
            `;

            // Scroll to results
            document.getElementById('compatibility-results').scrollIntoView({ behavior: 'smooth' });
        }

        function getScoreColor(score) {
            if (score >= 80) return '#4caf50';
            if (score >= 60) return '#ff9800';
            return '#f44336';
        }

        // Auto-submit if tribes are pre-selected
        <?php if ($tribe1 && $tribe2): ?>
        window.addEventListener('DOMContentLoaded', function() {
            document.getElementById('compatibility-form').dispatchEvent(new Event('submit'));
        });
        <?php endif; ?>
    </script>

    <style>
        .compatibility-form-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .compatibility-score-card {
            text-align: center;
            background: white;
            border-radius: 12px;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .score-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2rem auto;
            background: conic-gradient(#4caf50 0% 50%, #e0e0e0 50% 100%);
            position: relative;
        }

        .score-circle::before {
            content: '';
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: white;
        }

        .score-circle span {
            font-size: 3rem;
            font-weight: bold;
            z-index: 1;
            position: relative;
        }

        .compatibility-sections {
            display: grid;
            gap: 2rem;
        }

        .compatibility-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .compatibility-section h3 {
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .challenge-item, .solution-item {
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #d4af37;
            background: #fffbf0;
        }

        .challenge-item {
            border-left-color: #f44336;
            background: #ffebee;
        }

        .solution-item {
            border-left-color: #4caf50;
            background: #e8f5e9;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
            </div>
        </div>
</body>
</html>

