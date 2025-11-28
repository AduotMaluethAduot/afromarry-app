<?php
require_once '../config/database.php';
requireAuth();
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_advertiser'])) {
            // Add new advertiser
            $query = "INSERT INTO advertisers (company_name, contact_person, email, phone, website) 
                      VALUES (:company_name, :contact_person, :email, :phone, :website)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':company_name' => sanitize($_POST['company_name']),
                ':contact_person' => sanitize($_POST['contact_person']),
                ':email' => sanitize($_POST['email']),
                ':phone' => sanitize($_POST['phone']),
                ':website' => sanitize($_POST['website'])
            ]);
            $message = "Advertiser added successfully!";
        } elseif (isset($_POST['add_campaign'])) {
            // Add new campaign
            $query = "INSERT INTO ad_campaigns (advertiser_id, name, description, budget, daily_budget, start_date, end_date, status) 
                      VALUES (:advertiser_id, :name, :description, :budget, :daily_budget, :start_date, :end_date, :status)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':advertiser_id' => intval($_POST['advertiser_id']),
                ':name' => sanitize($_POST['name']),
                ':description' => sanitize($_POST['description']),
                ':budget' => floatval($_POST['budget']),
                ':daily_budget' => floatval($_POST['daily_budget']),
                ':start_date' => $_POST['start_date'],
                ':end_date' => $_POST['end_date'],
                ':status' => sanitize($_POST['status'])
            ]);
            $message = "Campaign added successfully!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch data for display
$advertisersQuery = "SELECT * FROM advertisers ORDER BY company_name";
$advertisersStmt = $db->prepare($advertisersQuery);
$advertisersStmt->execute();
$advertisers = $advertisersStmt->fetchAll();

$campaignsQuery = "SELECT ac.*, a.company_name FROM ad_campaigns ac JOIN advertisers a ON ac.advertiser_id = a.id ORDER BY ac.created_at DESC";
$campaignsStmt = $db->prepare($campaignsQuery);
$campaignsStmt->execute();
$campaigns = $campaignsStmt->fetchAll();

$placementsQuery = "SELECT ap.*, ac.name as campaign_name, a.company_name 
                    FROM ad_placements ap
                    JOIN ad_campaigns ac ON ap.campaign_id = ac.id
                    JOIN advertisers a ON ac.advertiser_id = a.id
                    ORDER BY ap.created_at DESC";
$placementsStmt = $db->prepare($placementsQuery);
$placementsStmt->execute();
$placements = $placementsStmt->fetchAll();

include '../config/security.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Management - AfroMarry Admin</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="container">
        <h1>Advertisement Management</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Advertisers Section -->
        <section class="admin-section">
            <h2>Advertisers</h2>
            <button class="btn-primary" onclick="showAddAdvertiserForm()">Add Advertiser</button>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($advertisers as $advertiser): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($advertiser['company_name']); ?></td>
                        <td><?php echo htmlspecialchars($advertiser['contact_person']); ?></td>
                        <td><?php echo htmlspecialchars($advertiser['email']); ?></td>
                        <td><?php echo htmlspecialchars($advertiser['phone']); ?></td>
                        <td><?php echo $advertiser['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td>
                            <a href="#" class="btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <!-- Campaigns Section -->
        <section class="admin-section">
            <h2>Ad Campaigns</h2>
            <button class="btn-primary" onclick="showAddCampaignForm()">Add Campaign</button>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Advertiser</th>
                        <th>Budget</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                        <td><?php echo htmlspecialchars($campaign['company_name']); ?></td>
                        <td>$<?php echo number_format($campaign['budget'], 2); ?></td>
                        <td><?php echo $campaign['start_date']; ?> to <?php echo $campaign['end_date']; ?></td>
                        <td><?php echo ucfirst($campaign['status']); ?></td>
                        <td>
                            <a href="#" class="btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        
        <!-- Ad Placements Section -->
        <section class="admin-section">
            <h2>Ad Placements</h2>
            <button class="btn-primary" onclick="showAddPlacementForm()">Add Placement</button>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Placement</th>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Impressions</th>
                        <th>Clicks</th>
                        <th>CTR</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($placements as $placement): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($placement['title']); ?></td>
                        <td><?php echo htmlspecialchars($placement['campaign_name']); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $placement['type'])); ?></td>
                        <td>$<?php echo number_format($placement['price'], 2); ?></td>
                        <td><?php echo number_format($placement['impressions_count']); ?></td>
                        <td><?php echo number_format($placement['clicks_count']); ?></td>
                        <td>
                            <?php 
                            $ctr = $placement['impressions_count'] > 0 ? ($placement['clicks_count'] / $placement['impressions_count']) * 100 : 0;
                            echo number_format($ctr, 2) . '%';
                            ?>
                        </td>
                        <td>
                            <a href="#" class="btn-secondary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
    
    <!-- Add Advertiser Form (Hidden by default) -->
    <div id="add-advertiser-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-advertiser-modal')">&times;</span>
            <h2>Add New Advertiser</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" required>
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website">
                </div>
                <button type="submit" name="add_advertiser" class="btn-primary">Add Advertiser</button>
            </form>
        </div>
    </div>
    
    <!-- Add Campaign Form (Hidden by default) -->
    <div id="add-campaign-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-campaign-modal')">&times;</span>
            <h2>Add New Campaign</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Advertiser *</label>
                    <select name="advertiser_id" required>
                        <option value="">Select Advertiser</option>
                        <?php foreach ($advertisers as $advertiser): ?>
                            <option value="<?php echo $advertiser['id']; ?>"><?php echo htmlspecialchars($advertiser['company_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Campaign Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Total Budget ($)</label>
                    <input type="number" name="budget" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Daily Budget ($)</label>
                    <input type="number" name="daily_budget" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Start Date *</label>
                    <input type="date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label>End Date *</label>
                    <input type="date" name="end_date" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <button type="submit" name="add_campaign" class="btn-primary">Add Campaign</button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddAdvertiserForm() {
            document.getElementById('add-advertiser-modal').style.display = 'block';
        }
        
        function showAddCampaignForm() {
            document.getElementById('add-campaign-modal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>