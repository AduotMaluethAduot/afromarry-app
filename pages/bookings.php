<?php
require_once '../config/database.php';
require_once '../config/paths.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Fetch user's expert bookings
$query = "SELECT b.id, e.name as expert_name, b.booking_date, b.status, b.created_at, b.duration_hours, b.total_amount
          FROM expert_bookings b
          JOIN experts e ON e.id = b.expert_id
          WHERE b.user_id = :user_id
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user['id']]);
$bookings = $stmt->fetchAll();

// Get all experts for booking
$query = "SELECT * FROM experts ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$experts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - AfroMarry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 5px 20px rgba(0,0,0,.08); }
        .table th, .table td { padding:1rem; border-bottom:1px solid #e5e7eb; text-align:left; }
        .table th { background:#f9fafb; color:#374151; font-weight:600; }
        .status { padding:.3rem .8rem; border-radius:20px; font-size:.8rem; font-weight:600; }
        .status.pending { background:#fef3c7; color:#92400e; }
        .status.confirmed { background:#d1fae5; color:#065f46; }
        .status.completed { background:#dbeafe; color:#1e40af; }
        .status.cancelled { background:#fee2e2; color:#991b1b; }
        .empty { text-align:center; padding:3rem; color:#6b7280; background:#fff; border-radius:12px; box-shadow:0 5px 20px rgba(0,0,0,.08); }
        .back { text-decoration:none; color:#dc2626; }
    </style>
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
                <a href="<?php echo page_url('cart.php'); ?>" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    Cart
                </a>
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
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1>Expert Bookings</h1>
                    <p>Browse and book cultural experts, or manage your existing bookings</p>
                </div>
            </div>

            <!-- Browse Experts Section -->
            <div style="background: white; border-radius: 15px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);">
                <h2 style="margin: 0 0 1.5rem 0; color: #1f2937;">
                    <i class="fas fa-user-tie"></i> Browse Experts
                </h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;" id="experts-grid">
                    <?php foreach ($experts as $expert): 
                        $languages = json_decode($expert['languages'] ?? '[]', true) ?: [];
                        $image = $expert['image'] ?: '';
                    ?>
                        <div class="expert-card" style="background: #f9fafb; border-radius: 12px; overflow: hidden; transition: transform 0.3s ease; cursor: pointer;" onclick="bookExpert(<?php echo $expert['id']; ?>)">
                            <?php if ($image): ?>
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($expert['name']); ?>" 
                                     style="width: 100%; height: 200px; object-fit: cover;"
                                     onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'280\' height=\'200\'%3E%3Crect fill=\'%238B5CF6\' width=\'280\' height=\'200\'/%3E%3Ctext fill=\'white\' font-family=\'Arial\' font-size=\'20\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E<?php echo urlencode($expert['name']); ?>%3C/text%3E%3C/svg%3E';">
                            <?php else: ?>
                                <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #8B5CF6, #EC4899); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            <?php endif; ?>
                            <div style="padding: 1.5rem;">
                                <h3 style="margin: 0 0 0.5rem 0; color: #1f2937; font-size: 1.2rem;"><?php echo htmlspecialchars($expert['name']); ?></h3>
                                <p style="margin: 0 0 0.5rem 0; color: #8B5CF6; font-weight: 600;"><?php echo htmlspecialchars($expert['tribe']); ?> Expert</p>
                                <p style="margin: 0 0 0.5rem 0; color: #6b7280; font-size: 0.9rem;"><?php echo htmlspecialchars($expert['specialization']); ?></p>
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <div style="color: #fbbf24;">
                                        <?php 
                                        $rating = (float)($expert['rating'] ?? 0);
                                        echo str_repeat('★', floor($rating));
                                        echo str_repeat('☆', 5 - floor($rating));
                                        ?>
                                    </div>
                                    <span style="color: #6b7280; font-size: 0.9rem;"><?php echo number_format($rating, 1); ?>/5</span>
                                </div>
                                <p style="margin: 0 0 1rem 0; color: #8B5CF6; font-weight: 700; font-size: 1.1rem;">$ <?php echo number_format($expert['hourly_rate'], 0); ?>/hour</p>
                                <button style="width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-calendar-check"></i> Book Consultation
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- My Bookings Section -->
            <div style="background: white; border-radius: 15px; padding: 2rem; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);">
                <h2 style="margin: 0 0 1.5rem 0; color: #1f2937;">
                    <i class="fas fa-calendar-alt"></i> My Bookings
                </h2>
                <?php if (empty($bookings)): ?>
                    <div class="empty">
                        <i class="fas fa-calendar-alt" style="font-size:3rem; color:#d1d5db;"></i>
                        <h3>No bookings yet</h3>
                        <p>Book a consultation with one of our experts above.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Expert</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td>#<?php echo $b['id']; ?></td>
                                    <td><?php echo htmlspecialchars($b['expert_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                                    <td><?php echo ($b['duration_hours'] ?? 1); ?> hour<?php echo ($b['duration_hours'] ?? 1) > 1 ? 's' : ''; ?></td>
                                    <td>$ <?php echo number_format($b['total_amount'] ?? 0, 2); ?></td>
                                    <td><span class="status <?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($b['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Expert Booking Modal -->
    <div id="booking-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background: white; margin: 5% auto; padding: 2rem; border-radius: 15px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <span class="close" onclick="closeBookingModal()" style="float: right; font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer;">&times;</span>
            <h2 style="margin-top: 0;">Book Expert Consultation</h2>
            <form id="booking-form">
                <input type="hidden" id="booking-expert-id" name="expert_id">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Expert</label>
                    <input type="text" id="booking-expert-name" readonly style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Hourly Rate</label>
                    <input type="text" id="booking-expert-rate" readonly style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Booking Date *</label>
                    <input type="date" id="booking-date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Duration (hours) *</label>
                    <select id="booking-duration" name="duration_hours" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="3">3 hours</option>
                        <option value="4">4 hours</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151;">Special Request (Optional)</label>
                    <textarea id="booking-notes" name="notes" rows="3" placeholder="Any special requests or topics you'd like to discuss..." style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn-primary" style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #8B5CF6, #EC4899); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Confirm Booking</button>
                    <button type="button" onclick="closeBookingModal()" class="btn-secondary" style="flex: 1; padding: 0.75rem; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_PATH; ?>/assets/js/config.js"></script>
    <script>
        // Store experts data
        window.expertsData = {};
        <?php foreach ($experts as $expert): ?>
        window.expertsData[<?php echo $expert['id']; ?>] = {
            id: <?php echo $expert['id']; ?>,
            name: <?php echo json_encode($expert['name']); ?>,
            tribe: <?php echo json_encode($expert['tribe']); ?>,
            hourlyRate: <?php echo $expert['hourly_rate']; ?>,
            specialization: <?php echo json_encode($expert['specialization']); ?>
        };
        <?php endforeach; ?>

        function bookExpert(expertId) {
            const expert = window.expertsData[expertId];
            if (!expert) {
                alert('Expert not found');
                return;
            }

            document.getElementById('booking-expert-id').value = expert.id;
            document.getElementById('booking-expert-name').value = expert.name + ' - ' + expert.tribe + ' Expert';
            document.getElementById('booking-expert-rate').value = '$ ' + expert.hourlyRate.toLocaleString() + '/hour';
            document.getElementById('booking-modal').style.display = 'block';
        }

        function closeBookingModal() {
            document.getElementById('booking-modal').style.display = 'none';
        }

        document.getElementById('booking-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const bookingData = {
                expert_id: parseInt(formData.get('expert_id')),
                booking_date: formData.get('booking_date'),
                duration_hours: parseInt(formData.get('duration_hours')),
                notes: formData.get('notes') || ''
            };

            try {
                const response = await fetch(actionUrl('expert-bookings.php'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(bookingData)
                });

                const data = await response.json();
                if (data.success) {
                    alert('Booking confirmed! You will receive a confirmation email shortly.');
                    closeBookingModal();
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error booking expert:', error);
                alert('Error booking expert. Please try again.');
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('booking-modal');
            if (event.target === modal) {
                closeBookingModal();
            }
        }
    </script>
</body>
</html>

