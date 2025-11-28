<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get user timelines
$query = "SELECT wt.*, t1.name as tribe1_name, t2.name as tribe2_name 
          FROM wedding_timelines wt
          LEFT JOIN tribes t1 ON wt.tribe_1_id = t1.id
          LEFT JOIN tribes t2 ON wt.tribe_2_id = t2.id
          WHERE wt.user_id = :user_id AND wt.is_active = TRUE
          ORDER BY wt.wedding_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$timelines = $stmt->fetchAll();

// Get milestones for each timeline
foreach ($timelines as &$timeline) {
    $milestoneQuery = "SELECT * FROM timeline_milestones WHERE timeline_id = :timeline_id ORDER BY due_date ASC";
    $milestoneStmt = $db->prepare($milestoneQuery);
    $milestoneStmt->execute([':timeline_id' => $timeline['id']]);
    $timeline['milestones'] = $milestoneStmt->fetchAll();
}

// Get all tribes for selection
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
    <title>Wedding Timeline Planner - AfroMarry</title>
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
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
        <div class="timeline-header">
            <h1 class="section-title">
                <i class="fas fa-calendar-alt"></i>
                Wedding Planning Timeline
            </h1>
            <button class="btn-primary" onclick="showCreateTimelineModal()">
                <i class="fas fa-plus"></i> Create New Timeline
            </button>
        </div>

        <div id="timelines-container">
            <?php if (empty($timelines)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Wedding Timelines Yet</h3>
                    <p>Create a personalized timeline to plan your traditional wedding</p>
                    <button class="btn-primary" onclick="showCreateTimelineModal()">
                        <i class="fas fa-plus"></i> Create Your First Timeline
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($timelines as $timeline): 
                    $milestones = $timeline['milestones'] ?? [];
                ?>
                    <div class="timeline-card" data-timeline-id="<?php echo $timeline['id']; ?>">
                        <div class="timeline-header-card">
                            <h3><?php echo htmlspecialchars($timeline['title']); ?></h3>
                            <div class="timeline-meta">
                                <span><i class="fas fa-calendar"></i> Wedding Date: <?php echo date('F j, Y', strtotime($timeline['wedding_date'])); ?></span>
                                <?php if ($timeline['tribe1_name']): ?>
                                    <span><i class="fas fa-users"></i> <?php echo htmlspecialchars($timeline['tribe1_name']); ?></span>
                                <?php endif; ?>
                                <?php if ($timeline['tribe2_name']): ?>
                                    <span><i class="fas fa-heart"></i> <?php echo htmlspecialchars($timeline['tribe2_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="timeline-milestones">
                            <h4>Milestones</h4>
                            <div id="milestones-<?php echo $timeline['id']; ?>">
                                <?php foreach ($milestones as $milestone): ?>
                                    <div class="milestone-item" data-milestone-id="<?php echo $milestone['id'] ?? ''; ?>">
                                        <input type="checkbox" <?php echo ($milestone['is_completed'] ?? false) ? 'checked' : ''; ?> 
                                               onchange="toggleMilestone(<?php echo $timeline['id']; ?>, this)">
                                        <div class="milestone-content">
                                            <h5><?php echo htmlspecialchars($milestone['title']); ?></h5>
                                            <p><?php echo htmlspecialchars($milestone['description'] ?? ''); ?></p>
                                            <span class="milestone-date">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('M j, Y', strtotime($milestone['due_date'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="timeline-actions">
                            <button class="btn-secondary" onclick="editTimeline(<?php echo $timeline['id']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn-secondary" onclick="addMilestone(<?php echo $timeline['id']; ?>)">
                                <i class="fas fa-plus"></i> Add Milestone
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
            </div>
        </div>

    <!-- Create Timeline Modal -->
    <div id="create-timeline-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('create-timeline-modal')">&times;</span>
            <h2>Create Wedding Timeline</h2>
            <form id="create-timeline-form">
                <div class="form-group">
                    <label>Timeline Title</label>
                    <input type="text" name="title" required placeholder="e.g., Our Traditional Wedding">
                </div>
                <div class="form-group">
                    <label>Wedding Date</label>
                    <input type="date" name="wedding_date" required>
                </div>
                <div class="form-group">
                    <label>First Partner's Tribe (Optional)</label>
                    <select name="tribe_1_id">
                        <option value="">Select Tribe</option>
                        <?php foreach ($allTribes as $tribe): ?>
                            <option value="<?php echo $tribe['id']; ?>">
                                <?php echo htmlspecialchars($tribe['name'] . ' - ' . $tribe['country']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Second Partner's Tribe (Optional - for inter-tribal)</label>
                    <select name="tribe_2_id">
                        <option value="">Select Tribe</option>
                        <?php foreach ($allTribes as $tribe): ?>
                            <option value="<?php echo $tribe['id']; ?>">
                                <?php echo htmlspecialchars($tribe['name'] . ' - ' . $tribe['country']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Create Timeline</button>
                    <button type="button" class="btn-secondary" onclick="closeModal('create-timeline-modal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const allTribes = <?php echo json_encode($allTribes); ?>;

        document.getElementById('create-timeline-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const timelineData = {
                title: formData.get('title'),
                wedding_date: formData.get('wedding_date'),
                tribe_1_id: formData.get('tribe_1_id') || null,
                tribe_2_id: formData.get('tribe_2_id') || null
            };

            try {
                const response = await fetch(actionUrl('timelines.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(timelineData)
                });

                const data = await response.json();
                if (data.success) {
                    // Generate default milestones
                    await generateDefaultMilestones(data.data.timeline_id, timelineData);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error creating timeline:', error);
                alert('Error creating timeline');
            }
        });

        async function generateDefaultMilestones(timelineId, timelineData) {
            const weddingDate = new Date(timelineData.wedding_date);
            
            // Default milestones based on wedding date
            const milestones = [
                {
                    title: 'Initial Family Meeting',
                    description: 'Meet with both families to discuss wedding plans',
                    due_date: new Date(weddingDate.getTime() - 180 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'preparation'
                },
                {
                    title: 'Dowry Negotiation',
                    description: 'Begin dowry negotiations with family elders',
                    due_date: new Date(weddingDate.getTime() - 150 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'dowry'
                },
                {
                    title: 'Knocking/Introduction Ceremony',
                    description: 'Traditional introduction ceremony',
                    due_date: new Date(weddingDate.getTime() - 120 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'ceremony'
                },
                {
                    title: 'Engagement Ceremony',
                    description: 'Formal engagement celebration',
                    due_date: new Date(weddingDate.getTime() - 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'ceremony'
                },
                {
                    title: 'Traditional Attire Selection',
                    description: 'Choose and order traditional wedding attire',
                    due_date: new Date(weddingDate.getTime() - 60 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'preparation'
                },
                {
                    title: 'Final Preparations',
                    description: 'Finalize all wedding arrangements',
                    due_date: new Date(weddingDate.getTime() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                    category: 'preparation'
                },
                {
                    title: 'Traditional Wedding Ceremony',
                    description: 'The main wedding celebration',
                    due_date: timelineData.wedding_date,
                    category: 'ceremony'
                }
            ];

            // Add milestones
            for (const milestone of milestones) {
                    await fetch(actionUrl('timelines.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add_milestone',
                        timeline_id: timelineId,
                        ...milestone
                    })
                });
            }
        }

        function showCreateTimelineModal() {
            document.getElementById('create-timeline-modal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        async function toggleMilestone(timelineId, checkbox) {
            const milestoneItem = checkbox.closest('.milestone-item');
            const milestoneId = milestoneItem.dataset.milestoneId;
            
            if (!milestoneId) return;

            try {
                    await fetch(actionUrl('timelines.php'), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle_milestone',
                        milestone_id: milestoneId,
                        is_completed: checkbox.checked
                    })
                });
            } catch (error) {
                console.error('Error updating milestone:', error);
            }
        }

        function editTimeline(timelineId) {
            const title = prompt('Enter new timeline title:');
            if (!title) return;
            
            fetch(actionUrl('timelines.php'), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_timeline',
                    timeline_id: timelineId,
                    title: title
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error updating timeline:', err);
                alert('Error updating timeline');
            });
        }

        function addMilestone(timelineId) {
            const title = prompt('Enter milestone title:');
            if (!title) return;
            
            const description = prompt('Enter milestone description (optional):') || '';
            const dueDate = prompt('Enter due date (YYYY-MM-DD):');
            if (!dueDate) return;
            
            fetch(actionUrl('timelines.php'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'add_milestone',
                    timeline_id: timelineId,
                    title: title,
                    description: description,
                    due_date: dueDate,
                    category: 'other'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error adding milestone:', err);
                alert('Error adding milestone');
            });
        }
    </script>

    <style>
        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .timeline-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .timeline-header-card h3 {
            margin-bottom: 1rem;
            color: #333;
        }

        .timeline-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            color: #666;
            font-size: 0.9rem;
        }

        .timeline-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .timeline-milestones {
            margin: 2rem 0;
        }

        .milestone-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .milestone-item input[type="checkbox"] {
            margin-top: 0.25rem;
            cursor: pointer;
        }

        .milestone-item input[type="checkbox"]:checked + .milestone-content {
            opacity: 0.6;
            text-decoration: line-through;
        }

        .milestone-content {
            flex: 1;
        }

        .milestone-content h5 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .milestone-date {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .timeline-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e0e0e0;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #d4af37;
            margin-bottom: 1rem;
        }
    </style>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script src="<?php echo BASE_PATH; ?>/assets/js/main.js"></script>
</body>
</html>

