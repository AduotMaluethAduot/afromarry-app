// MTN Mobile Money Payment Handler

async function processMTNMoMoPayment(orderData) {
    try {
        showPaymentModal();
        
        // Create order first
        const orderResponse = await fetch(actionUrl('orders.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                total_amount: orderData.total_amount,
                currency: orderData.currency,
                payment_method: 'mtn_momo',
                payment_reference: generatePaymentReference(),
                shipping_address: JSON.stringify(orderData.shipping),
                items: orderData.items
            })
        });
        
        const orderData_response = await orderResponse.json();
        
        if (!orderData_response.success) {
            throw new Error(orderData_response.message);
        }
        
        // Initiate MTN Mobile Money payment
        const momoResponse = await fetch(actionUrl('mtn-momo.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderData_response.order_id,
                phone_number: orderData.shipping.phone,
                amount: orderData.total_amount,
                currency: orderData.currency || 'GHS',
                order_reference: orderData_response.order_reference
            })
        });
        
        const momoData = await momoResponse.json();
        
        if (momoData.success) {
            // Show MTN MoMo payment prompt
            showMTNMoMoPrompt(momoData);
        } else {
            throw new Error(momoData.message || 'Payment initiation failed');
        }
        
    } catch (error) {
        console.error('MTN MoMo payment error:', error);
        hidePaymentModal();
        showMessage('Payment initialization failed. Please try again.', 'error');
    }
}

function showMTNMoMoPrompt(momoData) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>MTN Mobile Money Payment</h3>
            <div class="momo-instructions">
                <p><strong>Please approve the payment on your phone</strong></p>
                <p>A payment request has been sent to <strong>${momoData.phone_number}</strong></p>
                <p>Transaction ID: <code>${momoData.transaction_id || momoData.order_reference}</code></p>
                <div class="momo-status" id="momo-status">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Waiting for payment confirmation...</span>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Poll for payment status
    pollPaymentStatus(momoData.order_id, modal);
}

function pollPaymentStatus(orderId, modal) {
    const maxAttempts = 60; // 5 minutes max
    let attempts = 0;
    
    const interval = setInterval(async () => {
        attempts++;
        
        try {
            const response = await fetch(actionUrl(`mtn-momo.php?order_id=${orderId}`));
            const data = await response.json();
            
            if (data.success && data.status === 'completed') {
                clearInterval(interval);
                hidePaymentModal();
                modal.remove();
                
                showMessage('Payment successful!', 'success');
                setTimeout(() => {
                    window.location.href = pageUrl(`order-success.php?order_id=${orderId}`);
                }, 1500);
            } else if (data.status === 'failed' || data.status === 'cancelled') {
                clearInterval(interval);
                const statusDiv = document.getElementById('momo-status');
                statusDiv.innerHTML = `
                    <i class="fas fa-times-circle"></i>
                    <span>Payment ${data.status}</span>
                `;
            } else if (attempts >= maxAttempts) {
                clearInterval(interval);
                const statusDiv = document.getElementById('momo-status');
                statusDiv.innerHTML = `
                    <i class="fas fa-clock"></i>
                    <span>Payment is taking longer than expected. Please check your phone or contact support.</span>
                `;
            }
        } catch (error) {
            console.error('Error polling payment status:', error);
            if (attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }
    }, 5000); // Poll every 5 seconds
}

// Generate payment reference
function generatePaymentReference() {
    return 'AFM-MOMO-' + Date.now() + Math.random().toString(36).substr(2, 9);
}

// Show/hide payment modal
function showPaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function hidePaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Show message
function showMessage(message, type = 'info') {
    const messageEl = document.createElement('div');
    messageEl.className = `message ${type}`;
    messageEl.textContent = message;
    
    document.body.appendChild(messageEl);
    
    setTimeout(() => {
        messageEl.remove();
    }, 5000);
}

