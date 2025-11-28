<?php
require_once '../config/database.php';

requireAuth();
$user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Get all experts
$query = "SELECT * FROM experts ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$experts = $stmt->fetchAll();

// Get premium expiration for sidebar
$premium_expires = null;
if ($user['is_premium'] ?? false) {
    $premium_expires = $user['premium_expires_at'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Experts - AfroMarry</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .experts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        .expert-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .expert-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .expert-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
        }
        .expert-info {
            padding: 1.5rem;
        }
        .expert-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .expert-tribe {
            color: #8B5CF6;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .expert-specialization {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .expert-languages {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .expert-languages span {
            background: #f3f4f6;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .expert-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .expert-rating .stars {
            color: #fbbf24;
        }
        .expert-rate {
            font-size: 1.2rem;
            font-weight: 700;
            color: #8B5CF6;
            margin-bottom: 1rem;
        }
        .book-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #8B5CF6, #EC4899);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
        }
        .search-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        .search-filter input,
        .search-filter select {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 1rem;
        }
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            .experts-grid {
                grid-template-columns: 1fr;
            }
        }
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

    <div class="dashboard-container">
        <?php include 'includes/dashboard-sidebar.php'; ?>
        
        <div class="dashboard-content">
            <div class="dashboard-header">
                <h1>Cultural Experts</h1>
                <p>Book consultations with experienced cultural experts</p>
            </div>

            <div class="search-filter">
                <div class="filter-row">
                    <input type="text" id="expert-search" placeholder="Search experts by name, tribe, or specialization..." onkeyup="filterExperts()">
                    <select id="tribe-filter" onchange="filterExperts()">
                        <option value="">All Tribes</option>
                        <?php
                        $tribes = array_unique(array_column($experts, 'tribe'));
                        sort($tribes);
                        foreach ($tribes as $tribe):
                        ?>
                            <option value="<?php echo htmlspecialchars($tribe); ?>"><?php echo htmlspecialchars($tribe); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="sort-filter" onchange="filterExperts()">
                        <option value="name">Sort by Name</option>
                        <option value="rate">Sort by Rate (Low to High)</option>
                        <option value="rate-desc">Sort by Rate (High to Low)</option>
                        <option value="rating">Sort by Rating</option>
                    </select>
                </div>
            </div>

            <div id="experts-container" class="experts-grid">
                <?php foreach ($experts as $expert): 
                    $languages = json_decode($expert['languages'] ?? '[]', true) ?: [];
                    $image = $expert['image'] ?: '';
                ?>
                    <div class="expert-card" data-expert-name="<?php echo strtolower(htmlspecialchars($expert['name'])); ?>" 
                         data-expert-tribe="<?php echo strtolower(htmlspecialchars($expert['tribe'])); ?>"
                         data-expert-specialization="<?php echo strtolower(htmlspecialchars($expert['specialization'])); ?>"
                         data-expert-rate="<?php echo $expert['hourly_rate']; ?>"
                         data-expert-rating="<?php echo $expert['rating'] ?? 0; ?>">
                        <?php if ($image): ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($expert['name']); ?>" class="expert-image" 
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%238B5CF6\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'white\' font-family=\'Arial\' font-size=\'24\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3E<?php echo urlencode($expert['name']); ?>%3C/text%3E%3C/svg%3E';">
                        <?php else: ?>
                            <div class="expert-image" style="display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        <?php endif; ?>
                        <div class="expert-info">
                            <h3 class="expert-name"><?php echo htmlspecialchars($expert['name']); ?></h3>
                            <p class="expert-tribe"><?php echo htmlspecialchars($expert['tribe']); ?> Expert</p>
                            <p class="expert-specialization"><?php echo htmlspecialchars($expert['specialization']); ?></p>
                            <?php if (!empty($languages)): ?>
                                <div class="expert-languages">
                                    <?php foreach ($languages as $lang): ?>
                                        <span><?php echo htmlspecialchars($lang); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="expert-rating">
                                <div class="stars">
                                    <?php 
                                    $rating = (float)($expert['rating'] ?? 0);
                                    echo str_repeat('★', floor($rating));
                                    echo str_repeat('☆', 5 - floor($rating));
                                    ?>
                                </div>
                                <span><?php echo number_format($rating, 1); ?>/5</span>
                            </div>
                            <p class="expert-rate">$ <?php echo number_format($expert['hourly_rate'], 0); ?>/hour</p>
                            <button type="button" class="book-btn" onclick="bookExpert(<?php echo $expert['id']; ?>)">
                                <i class="fas fa-calendar-check"></i> Book Consultation
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($experts)): ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);">
                    <i class="fas fa-user-tie" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                    <h3>No Experts Available</h3>
                    <p>Check back later for available cultural experts.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expert Booking Modal -->
    <div id="booking-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeBookingModal()">&times;</span>
            <h2>Book Expert Consultation</h2>
            <form id="booking-form">
                <input type="hidden" id="booking-expert-id" name="expert_id">
                <div class="form-group">
                    <label>Expert</label>
                    <input type="text" id="booking-expert-name" readonly>
                </div>
                <div class="form-group">
                    <label>Hourly Rate</label>
                    <input type="text" id="booking-expert-rate" readonly>
                </div>
                <div class="form-group">
                    <label>Booking Date *</label>
                    <input type="date" id="booking-date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Duration (hours) *</label>
                    <select id="booking-duration" name="duration_hours" required>
                        <option value="1">1 hour</option>
                        <option value="2">2 hours</option>
                        <option value="3">3 hours</option>
                        <option value="4">4 hours</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Special Request (Optional)</label>
                    <textarea id="booking-notes" name="notes" rows="3" placeholder="Any special requests or topics you'd like to discuss..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Confirm Booking</button>
                    <button type="button" class="btn-secondary" onclick="closeBookingModal()">Cancel</button>
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
                    window.location.href = pageUrl('bookings.php');
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error booking expert:', error);
                alert('Error booking expert. Please try again.');
            }
        });

        function filterExperts() {
            const search = document.getElementById('expert-search').value.toLowerCase();
            const tribeFilter = document.getElementById('tribe-filter').value.toLowerCase();
            const sortFilter = document.getElementById('sort-filter').value;
            const cards = document.querySelectorAll('.expert-card');
            
            let visibleCards = [];
            
            cards.forEach(card => {
                const name = card.dataset.expertName || '';
                const tribe = card.dataset.expertTribe || '';
                const specialization = card.dataset.expertSpecialization || '';
                const cardTribe = card.dataset.expertTribe || '';
                
                const matchesSearch = !search || name.includes(search) || tribe.includes(search) || specialization.includes(search);
                const matchesTribe = !tribeFilter || cardTribe === tribeFilter;
                
                if (matchesSearch && matchesTribe) {
                    card.style.display = 'block';
                    visibleCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            // Sort visible cards
            if (sortFilter === 'name') {
                visibleCards.sort((a, b) => {
                    const nameA = a.dataset.expertName || '';
                    const nameB = b.dataset.expertName || '';
                    return nameA.localeCompare(nameB);
                });
            } else if (sortFilter === 'rate') {
                visibleCards.sort((a, b) => {
                    return parseFloat(a.dataset.expertRate || 0) - parseFloat(b.dataset.expertRate || 0);
                });
            } else if (sortFilter === 'rate-desc') {
                visibleCards.sort((a, b) => {
                    return parseFloat(b.dataset.expertRate || 0) - parseFloat(a.dataset.expertRate || 0);
                });
            } else if (sortFilter === 'rating') {
                visibleCards.sort((a, b) => {
                    return parseFloat(b.dataset.expertRating || 0) - parseFloat(a.dataset.expertRating || 0);
                });
            }

            // Reorder in DOM
            const container = document.getElementById('experts-container');
            visibleCards.forEach(card => container.appendChild(card));
        }

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

