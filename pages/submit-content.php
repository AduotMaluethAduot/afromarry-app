<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get all tribes
$query = "SELECT id, name, country, region FROM tribes ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$allTribes = $stmt->fetchAll();

// Get user submissions
$query = "SELECT uc.*, t.name as tribe_name 
          FROM user_content_submissions uc
          LEFT JOIN tribes t ON uc.tribe_id = t.id
          WHERE uc.user_id = :user_id
          ORDER BY uc.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$submissions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Content - AfroMarry</title>
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
            <div class="container" style="max-width: 900px; margin: 0 auto; padding: 0 1rem;">
        <h1 class="section-title">
            <i class="fas fa-paper-plane"></i>
            Submit Cultural Content
        </h1>
        <p class="text-center text-gray-600 mb-4">
            Share your knowledge about African marriage traditions and help expand our cultural database
        </p>

        <div class="submit-content-card">
            <form id="submit-content-form">
                <div class="form-group">
                    <label>Content Type *</label>
                    <select name="submission_type" required>
                        <option value="">Select Type</option>
                        <option value="tribe">Tribe Information</option>
                        <option value="custom">Custom/Tradition</option>
                        <option value="dowry_info">Dowry Information</option>
                        <option value="story">Personal Story</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required placeholder="Brief title for your submission">
                </div>

                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" rows="8" required placeholder="Describe the tradition, custom, or information you'd like to share..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Tribe (Optional)</label>
                        <select name="tribe_id">
                            <option value="">Select Tribe</option>
                            <?php foreach ($allTribes as $tribe): ?>
                                <option value="<?php echo $tribe['id']; ?>">
                                    <?php echo htmlspecialchars($tribe['name'] . ' - ' . $tribe['country']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Country (if tribe not listed)</label>
                        <input type="text" name="country" placeholder="Country name">
                    </div>
                </div>

                <div class="form-group">
                    <label>Region (if not selected via tribe)</label>
                    <select name="region">
                        <option value="">Select Region</option>
                        <option value="East Africa">East Africa</option>
                        <option value="West Africa">West Africa</option>
                        <option value="Southern Africa">Southern Africa</option>
                        <option value="North Africa">North Africa</option>
                        <option value="Central Africa">Central Africa</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit for Review
                    </button>
                    <button type="reset" class="btn-secondary">Clear Form</button>
                </div>
            </form>
        </div>

        <div class="my-submissions">
            <h2>My Submissions</h2>
            <?php if (empty($submissions)): ?>
                <div class="empty-state">
                    <p>You haven't submitted any content yet.</p>
                </div>
            <?php else: ?>
                <div class="submissions-list">
                    <?php foreach ($submissions as $submission): ?>
                        <div class="submission-card status-<?php echo $submission['status']; ?>">
                            <div class="submission-header">
                                <h3><?php echo htmlspecialchars($submission['title']); ?></h3>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </div>
                            <div class="submission-meta">
                                <span><i class="fas fa-tag"></i> <?php echo ucfirst(str_replace('_', ' ', $submission['submission_type'])); ?></span>
                                <?php if ($submission['tribe_name']): ?>
                                    <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($submission['tribe_name']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($submission['created_at'])); ?></span>
                            </div>
                            <p class="submission-content"><?php echo htmlspecialchars(substr($submission['content'], 0, 200)); ?>...</p>
                            <?php if ($submission['review_notes']): ?>
                                <div class="review-notes">
                                    <strong>Review Notes:</strong> <?php echo htmlspecialchars($submission['review_notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
            </div>
        </div>

    <script>
        document.getElementById('submit-content-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const submissionData = {
                submission_type: formData.get('submission_type'),
                title: formData.get('title'),
                content: formData.get('content'),
                tribe_id: formData.get('tribe_id') || null,
                country: formData.get('country') || null,
                region: formData.get('region') || null
            };

            try {
                const response = await fetch(actionUrl('user-content.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(submissionData)
                });

                const data = await response.json();
                if (data.success) {
                    alert('Thank you! Your submission has been received and will be reviewed by our team.');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error submitting content:', error);
                alert('Error submitting content. Please try again.');
            }
        });
    </script>

    <style>
        .submit-content-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .submission-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .submission-card.status-approved {
            border-left-color: #4caf50;
        }

        .submission-card.status-rejected {
            border-left-color: #f44336;
        }

        .submission-card.status-pending {
            border-left-color: #ff9800;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-badge.status-approved {
            background: #e8f5e9;
            color: #4caf50;
        }

        .status-badge.status-rejected {
            background: #ffebee;
            color: #f44336;
        }

        .status-badge.status-pending {
            background: #fff3e0;
            color: #ff9800;
        }

        .submission-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .submission-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .review-notes {
            margin-top: 1rem;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 0.9rem;
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

