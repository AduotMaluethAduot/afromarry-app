// Experts functionality

let experts = {
    selectedExpert: null,
    bookingData: {}
};

// Initialize experts
function initializeExperts() {
    setupExpertEventListeners();
}

// Setup expert event listeners
function setupExpertEventListeners() {
    // Book expert buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('book-btn') || e.target.closest('.book-btn')) {
            e.preventDefault();
            const expertId = e.target.dataset.expertId || e.target.closest('.book-btn').dataset.expertId;
            if (expertId) {
                bookExpert(parseInt(expertId));
            }
        }
    });
    
    // Booking form submission
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'booking-form') {
            e.preventDefault();
            submitBooking();
        }
    });
}

// Book expert consultation
function bookExpert(expertId) {
    if (!currentUser) {
        if (typeof showSignupModal === 'function') {
            showSignupModal();
        } else {
            alert('Please login to book a consultation');
            window.location.href = baseUrl('auth/login.php');
        }
        return;
    }
    
    // Find expert data - first try the button, then find the card
    const expertButton = document.querySelector(`[data-expert-id="${expertId}"]`);
    const expertCard = expertButton ? expertButton.closest('.expert-card') : null;
    
    if (!expertCard) {
        // Fallback: try to get from stored expert data
        if (window.expertsData && window.expertsData[expertId]) {
            experts.selectedExpert = {
                id: expertId,
                name: window.expertsData[expertId].name,
                tribe: window.expertsData[expertId].tribe,
                hourlyRate: `$ ${window.expertsData[expertId].hourlyRate.toLocaleString()}/hour`
            };
            showBookingModal();
            return;
        }
        console.error('Expert card not found for ID:', expertId);
        alert('Expert information not found. Please refresh the page.');
        return;
    }
    
    const nameEl = expertCard.querySelector('.expert-name');
    const tribeEl = expertCard.querySelector('.expert-tribe');
    const rateEl = expertCard.querySelector('.expert-rate');
    
    if (!nameEl || !tribeEl || !rateEl) {
        console.error('Expert card missing required elements');
        alert('Expert information incomplete. Please refresh the page.');
        return;
    }
    
    experts.selectedExpert = {
        id: expertId,
        name: nameEl.textContent.trim(),
        tribe: tribeEl.textContent.trim(),
        hourlyRate: rateEl.textContent.trim()
    };
    
    showBookingModal();
}

// Show booking modal
function showBookingModal() {
    const modal = document.getElementById('booking-modal');
    if (!modal) return;
    
    const bookingForm = document.getElementById('booking-form');
    if (bookingForm) {
        bookingForm.innerHTML = createBookingForm();
    }
    
    modal.style.display = 'block';
}

// Create booking form
function createBookingForm() {
    const expert = experts.selectedExpert;
    if (!expert) return '';
    
    return `
        <div class="expert-booking-info">
            <h4>Booking with ${expert.name}</h4>
            <p>${expert.tribe}</p>
            <p>Rate: ${expert.hourlyRate}</p>
        </div>
        
        <form id="booking-form">
            <div class="form-group">
                <label for="booking-date">Preferred Date</label>
                <input type="date" id="booking-date" name="booking_date" required>
            </div>
            
            <div class="form-group">
                <label for="booking-time">Preferred Time</label>
                <select id="booking-time" name="booking_time" required>
                    <option value="">Select time</option>
                    <option value="09:00">9:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="12:00">12:00 PM</option>
                    <option value="14:00">2:00 PM</option>
                    <option value="15:00">3:00 PM</option>
                    <option value="16:00">4:00 PM</option>
                    <option value="17:00">5:00 PM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="duration">Duration (hours)</label>
                <select id="duration" name="duration" required>
                    <option value="1">1 hour</option>
                    <option value="2">2 hours</option>
                    <option value="3">3 hours</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="consultation-type">Consultation Type</label>
                <select id="consultation-type" name="consultation_type" required>
                    <option value="video">Video Call</option>
                    <option value="phone">Phone Call</option>
                    <option value="in-person">In-Person (if available)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" placeholder="Tell us about your specific needs or questions..."></textarea>
            </div>
            
            <div class="booking-summary">
                <h4>Booking Summary</h4>
                <div class="summary-item">
                    <span>Expert:</span>
                    <span>${expert.name}</span>
                </div>
                <div class="summary-item">
                    <span>Rate:</span>
                    <span>${expert.hourlyRate}</span>
                </div>
                <div class="summary-item">
                    <span>Duration:</span>
                    <span id="summary-duration">1 hour</span>
                </div>
                <div class="summary-item total">
                    <span>Total:</span>
                    <span id="summary-total">$ 5,000</span>
                </div>
            </div>
            
            <button type="submit" class="btn-primary btn-large">Confirm Booking</button>
        </form>
    `;
    
    // Add event listeners for dynamic updates
    setTimeout(() => {
        const durationSelect = document.getElementById('duration');
        const hourlyRate = parseFloat(expert.hourlyRate.replace(/[^\d]/g, ''));
        
        if (durationSelect) {
            durationSelect.addEventListener('change', function() {
                updateBookingSummary();
            });
        }
        
        function updateBookingSummary() {
            const duration = parseInt(durationSelect.value);
            const total = hourlyRate * duration;
            
            const summaryDuration = document.getElementById('summary-duration');
            const summaryTotal = document.getElementById('summary-total');
            
            if (summaryDuration) {
                summaryDuration.textContent = `${duration} hour${duration > 1 ? 's' : ''}`;
            }
            
            if (summaryTotal) {
                summaryTotal.textContent = `$ ${total.toLocaleString()}`;
            }
        }
    }, 100);
}

// Submit booking
async function submitBooking() {
    if (!experts.selectedExpert) return;
    
    const formData = new FormData(document.getElementById('booking-form'));
    const bookingData = {
        expert_id: experts.selectedExpert.id,
        booking_date: formData.get('booking_date'),
        booking_time: formData.get('booking_time'),
        duration_hours: parseInt(formData.get('duration')),
        consultation_type: formData.get('consultation_type'),
        notes: formData.get('notes')
    };
    
    // Calculate total amount
    const hourlyRate = parseFloat(experts.selectedExpert.hourlyRate.replace(/[^\d]/g, ''));
    bookingData.total_amount = hourlyRate * bookingData.duration_hours;
    
    try {
        const response = await fetch(actionUrl('expert-bookings.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Booking confirmed! You will receive a confirmation email shortly.', 'success');
            closeModal('booking-modal');
            experts.selectedExpert = null;
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error submitting booking:', error);
        showMessage('Error submitting booking. Please try again.', 'error');
    }
}

// Load expert bookings
async function loadExpertBookings() {
    if (!currentUser) return;
    
    try {
        const response = await fetch(actionUrl('expert-bookings.php'));
        const data = await response.json();
        
        if (data.success) {
            displayExpertBookings(data.data);
        }
    } catch (error) {
        console.error('Error loading expert bookings:', error);
    }
}

// Display expert bookings
function displayExpertBookings(bookings) {
    const bookingsContainer = document.getElementById('expert-bookings');
    if (!bookingsContainer) return;
    
    if (bookings.length === 0) {
        bookingsContainer.innerHTML = `
            <div class="empty-bookings">
                <i class="fas fa-calendar-alt"></i>
                <h3>No bookings yet</h3>
                <p>Book a consultation with our cultural experts to get started!</p>
                <a href="index.php#experts" class="btn-primary">Browse Experts</a>
            </div>
        `;
        return;
    }
    
    bookingsContainer.innerHTML = bookings.map(booking => `
        <div class="booking-card">
            <div class="booking-header">
                <h4>${booking.expert_name}</h4>
                <span class="booking-status ${booking.status}">${booking.status}</span>
            </div>
            <div class="booking-details">
                <div class="booking-detail">
                    <i class="fas fa-calendar"></i>
                    <span>${new Date(booking.booking_date).toLocaleDateString()}</span>
                </div>
                <div class="booking-detail">
                    <i class="fas fa-clock"></i>
                    <span>${booking.duration_hours} hour${booking.duration_hours > 1 ? 's' : ''}</span>
                </div>
                <div class="booking-detail">
                    <i class="fas fa-money-bill"></i>
                    <span>$ ${parseFloat(booking.total_amount).toLocaleString()}</span>
                </div>
            </div>
            <div class="booking-actions">
                ${booking.status === 'confirmed' ? `
                    <a href="${booking.meeting_link}" class="btn-primary" target="_blank">Join Meeting</a>
                ` : ''}
                ${booking.status === 'pending' ? `
                    <button class="btn-secondary" onclick="cancelBooking(${booking.id})">Cancel</button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Cancel booking
async function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) return;
    
    try {
        const response = await fetch(actionUrl(`expert-bookings.php?id=${bookingId}`), {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Booking cancelled successfully', 'success');
            loadExpertBookings();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error cancelling booking:', error);
        showMessage('Error cancelling booking', 'error');
    }
}

// Search experts
function searchExperts(query) {
    const expertCards = document.querySelectorAll('.expert-card');
    
    expertCards.forEach(card => {
        const name = card.querySelector('.expert-name').textContent.toLowerCase();
        const tribe = card.querySelector('.expert-tribe').textContent.toLowerCase();
        const specialization = card.querySelector('.expert-specialization').textContent.toLowerCase();
        
        if (name.includes(query.toLowerCase()) || 
            tribe.includes(query.toLowerCase()) || 
            specialization.includes(query.toLowerCase())) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// Filter experts by tribe
function filterExpertsByTribe(tribe) {
    const expertCards = document.querySelectorAll('.expert-card');
    
    expertCards.forEach(card => {
        const expertTribe = card.querySelector('.expert-tribe').textContent.toLowerCase();
        
        if (tribe === 'all' || expertTribe.includes(tribe.toLowerCase())) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

// Sort experts
function sortExperts(sortBy) {
    const expertsList = document.getElementById('experts-list');
    const expertCards = Array.from(expertsList.children);
    
    expertCards.sort((a, b) => {
        switch (sortBy) {
            case 'rating':
                const ratingA = parseFloat(a.querySelector('.expert-rating span').textContent);
                const ratingB = parseFloat(b.querySelector('.expert-rating span').textContent);
                return ratingB - ratingA;
            case 'price-low':
                const priceA = parseFloat(a.querySelector('.expert-rate').textContent.replace(/[^\d]/g, ''));
                const priceB = parseFloat(b.querySelector('.expert-rate').textContent.replace(/[^\d]/g, ''));
                return priceA - priceB;
            case 'price-high':
                const priceA2 = parseFloat(a.querySelector('.expert-rate').textContent.replace(/[^\d]/g, ''));
                const priceB2 = parseFloat(b.querySelector('.expert-rate').textContent.replace(/[^\d]/g, ''));
                return priceB2 - priceA2;
            case 'name':
                const nameA = a.querySelector('.expert-name').textContent;
                const nameB = b.querySelector('.expert-name').textContent;
                return nameA.localeCompare(nameB);
            default:
                return 0;
        }
    });
    
    expertCards.forEach(card => expertsList.appendChild(card));
}

// Initialize experts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeExperts();
});
