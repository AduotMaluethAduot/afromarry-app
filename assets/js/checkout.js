// Checkout functionality

let checkout = {
    orderData: {},
    paymentMethod: 'paystack'
};

// Initialize checkout
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Checkout] DOM loaded, initializing checkout');
    setupCheckoutEventListeners();
    initializePaymentMethods();
});

// Setup checkout event listeners
function setupCheckoutEventListeners() {
    console.log('[Checkout] Setting up event listeners');
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        console.log('[Checkout] Found checkout form, adding submit listener');
        checkoutForm.addEventListener('submit', handleCheckout);
    } else {
        console.error('[Checkout] Checkout form not found');
    }
    
    // Payment method selection - handle both radio input and label clicks
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    console.log('[Checkout] Found ' + paymentMethods.length + ' payment methods');
    paymentMethods.forEach(method => {
        // Listen for change event on radio button
        method.addEventListener('change', function(e) {
            console.log('[Checkout] Payment method changed to: ' + this.value);
            checkout.paymentMethod = this.value;
            updatePaymentUI();
        });
        
        // Also listen for click to ensure it fires immediately
        method.addEventListener('click', function(e) {
            console.log('[Checkout] Payment method clicked: ' + this.value);
            checkout.paymentMethod = this.value;
            this.checked = true;
            updatePaymentUI();
        });
    });
    
    // Also handle clicks on payment method labels/containers
    const paymentMethodContainers = document.querySelectorAll('.payment-method');
    console.log('[Checkout] Found ' + paymentMethodContainers.length + ' payment method containers');
    paymentMethodContainers.forEach((container, index) => {
        container.addEventListener('click', function(e) {
            console.log('[Checkout] Payment method container clicked');
            // Don't trigger if clicking the radio itself (already handled)
            if (e.target.type === 'radio') {
                return;
            }
            
            // Prevent default behavior
            e.preventDefault();
            e.stopPropagation();
            
            // Find and click the radio button inside this container
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                checkout.paymentMethod = radio.value;
                updatePaymentUI();
                // Trigger change event manually
                radio.dispatchEvent(new Event('change', { bubbles: true }));
                // Also trigger click event for compatibility
                radio.dispatchEvent(new Event('click', { bubbles: true }));
            } else {
                console.error('[Checkout] No radio button found in container');
            }
        });
    });
    
    // Form validation
    const formInputs = document.querySelectorAll('#checkout-form input, #checkout-form textarea, #checkout-form select');
    console.log('[Checkout] Setting up validation for ' + formInputs.length + ' form inputs');
    formInputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
    });
}

// Initialize payment methods
function initializePaymentMethods() {
    console.log('[Checkout] Initializing payment methods');
    updatePaymentUI();
    
    // Log initial state
    const selectedMethod = document.querySelector(`input[name="payment_method"]:checked`);
    if (selectedMethod) {
        checkout.paymentMethod = selectedMethod.value;
        console.log('[Checkout] Initial payment method: ' + checkout.paymentMethod);
    } else {
        console.warn('[Checkout] No initial payment method selected');
    }
}

// Update payment UI based on selected method
function updatePaymentUI() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.classList.remove('selected');
    });
    
    const selectedMethod = document.querySelector(`input[name="payment_method"]:checked`);
    if (selectedMethod) {
        const container = selectedMethod.closest('.payment-method');
        if (container) {
            container.classList.add('selected');
        }
    } else {
        console.warn('[Checkout] No payment method selected');
    }
}

// Handle checkout form submission
async function handleCheckout(e) {
    console.log('[Checkout] Form submission triggered');
    e.preventDefault();
    
    if (!validateForm()) {
        console.log('[Checkout] Form validation failed');
        showMessage('Please fill in all required fields correctly', 'error');
        return;
    }
    
    console.log('[Checkout] Form validation passed');
    
    const formData = new FormData(e.target);
    checkout.orderData = {
        shipping: {
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            address: formData.get('address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zip_code: formData.get('zip_code'),
            country: formData.get('country')
        },
        payment_method: formData.get('payment_method'),
        notes: formData.get('notes')
    };
    
    console.log('[Checkout] Order data:', checkout.orderData);
    
    // Calculate totals
    const subtotal = await calculateSubtotal();
    const shipping = 2000;
    const tax = subtotal * 0.05;
    const total = subtotal + shipping + tax;
    
    checkout.orderData.total_amount = total;
    checkout.orderData.currency = 'USD';
    
    console.log('[Checkout] Payment method:', checkout.paymentMethod);
    console.log('[Checkout] Total amount:', total);
    
    // Process payment based on selected method
    switch (checkout.paymentMethod) {
        case 'paystack':
            console.log('[Checkout] Processing Paystack payment');
            await processPaystackPayment();
            break;
        case 'flutterwave':
            console.log('[Checkout] Processing Flutterwave payment');
            await processFlutterwavePayment();
            break;
        case 'bank_transfer':
            console.log('[Checkout] Processing Bank Transfer');
            await processBankTransfer();
            break;
        case 'mtn_momo':
            console.log('[Checkout] Processing MTN MoMo payment');
            // Load MTN MoMo handler
            if (typeof processMTNMoMoPayment === 'function') {
                await processMTNMoMoPayment(checkout.orderData);
            } else {
                // Load script dynamically
                const script = document.createElement('script');
                script.src = assetUrl('js/checkout-momo.js');
                script.onload = () => processMTNMoMoPayment(checkout.orderData);
                script.onerror = () => {
                    console.error('[Checkout] Failed to load MTN MoMo script');
                    hidePaymentModal();
                    showMessage('MTN Mobile Money payment handler not available', 'error');
                };
                document.head.appendChild(script);
            }
            break;
        default:
            console.log('[Checkout] No payment method selected');
            showMessage('Please select a payment method', 'error');
    }
}

// Process Paystack payment
async function processPaystackPayment() {
    try {
        console.log('[Checkout] Starting Paystack payment process');
        showPaymentModal();
        
        // Get cart items first
        const cartItems = await getCartItems();
        console.log('[Checkout] Cart items:', cartItems);
        
        const orderPayload = {
            total_amount: checkout.orderData.total_amount,
            currency: checkout.orderData.currency,
            payment_method: checkout.orderData.payment_method,
            payment_reference: generatePaymentReference(),
            shipping_address: JSON.stringify(checkout.orderData.shipping),
            items: cartItems
        };
        
        console.log('[Checkout] Order payload:', orderPayload);
        
        // Create order first
        const orderResponse = await fetch(actionUrl('orders.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(orderPayload)
        });
        
        console.log('[Checkout] Order response status:', orderResponse.status);
        console.log('[Checkout] Order response headers:', [...orderResponse.headers.entries()]);
        
        // Get the response text to see what's actually being returned
        const responseText = await orderResponse.text();
        console.log('[Checkout] Raw response text:', responseText);
        
        // Try to parse as JSON
        let orderData;
        try {
            orderData = JSON.parse(responseText);
            console.log('[Checkout] Parsed order data:', orderData);
        } catch (parseError) {
            console.error('[Checkout] Failed to parse JSON:', parseError);
            console.error('[Checkout] Response was not valid JSON');
            hidePaymentModal();
            showMessage('Payment initialization failed: Server returned invalid response', 'error');
            return;
        }
        
        if (!orderResponse.ok) {
            const errorText = responseText;
            console.error('[Checkout] Order API error:', errorText);
            throw new Error(`Order creation failed: ${orderResponse.status} - ${errorText}`);
        }
        
        if (!orderData.success) {
            throw new Error(orderData.message || 'Failed to create order');
        }
        
        // Check if Paystack is properly configured
        if (!window.PAYSTACK_PUBLIC_KEY || window.PAYSTACK_PUBLIC_KEY === '') {
            console.log('[Checkout] Paystack not configured');
            hidePaymentModal();
            showMessage('Paystack is not properly configured. Please contact the administrator.', 'error');
            return;
        }
        
        console.log('[Checkout] Paystack public key:', window.PAYSTACK_PUBLIC_KEY);
        
        // Initialize Paystack
        const handler = PaystackPop.setup({
            key: window.PAYSTACK_PUBLIC_KEY, // Use configured key only
            email: checkout.orderData.shipping.email,
            amount: checkout.orderData.total_amount * 100, // Convert to kobo
            currency: checkout.orderData.currency || 'GHC',
            ref: orderData.order_reference,
            callback: function(response) {
                console.log('[Checkout] Paystack callback:', response);
                handlePaymentSuccess(response, orderData.order_id);
            },
            onClose: function() {
                console.log('[Checkout] Paystack window closed');
                handlePaymentCancelled();
            }
        });
        
        console.log('[Checkout] Opening Paystack iframe');
        handler.openIframe();
        
    } catch (error) {
        console.error('[Checkout] Paystack payment error:', error);
        hidePaymentModal();
        showMessage('Payment initialization failed: ' + error.message, 'error');
    }
}

// Process Flutterwave payment
async function processFlutterwavePayment() {
    try {
        showPaymentModal();
        
        // Create order first
        const orderResponse = await fetch(actionUrl('orders.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                total_amount: checkout.orderData.total_amount,
                currency: checkout.orderData.currency,
                payment_method: checkout.orderData.payment_method,
                payment_reference: generatePaymentReference(),
                shipping_address: JSON.stringify(checkout.orderData.shipping),
                items: await getCartItems()
            })
        });
        
        if (!orderResponse.ok) {
            const errorText = await orderResponse.text();
            console.error('[Checkout] Flutterwave order API error:', errorText);
            throw new Error(`Order creation failed: ${orderResponse.status} - ${errorText}`);
        }
        
        const orderData = await orderResponse.json();
        
        if (!orderData.success) {
            throw new Error(orderData.message || 'Failed to create order');
        }
        
        // Check if Flutterwave is properly configured
        if (!window.FLUTTERWAVE_PUBLIC_KEY || window.FLUTTERWAVE_PUBLIC_KEY === '') {
            hidePaymentModal();
            showMessage('Flutterwave is not properly configured. Please contact the administrator.', 'error');
            return;
        }
        
        // Initialize Flutterwave
        FlutterwaveCheckout({
            public_key: window.FLUTTERWAVE_PUBLIC_KEY, // Use configured key only
            tx_ref: orderData.order_reference,
            amount: checkout.orderData.total_amount,
            currency: checkout.orderData.currency || "GHC",
            payment_options: "card,mobilemoney,ussd",
            redirect_url: `${window.location.origin}${window.BASE_PATH}/pages/payment-verification.php?order_id=${orderData.order_id}`,
            customer: {
                email: checkout.orderData.shipping.email,
                phone_number: checkout.orderData.shipping.phone,
                name: `${checkout.orderData.shipping.first_name} ${checkout.orderData.shipping.last_name}`
            },
            customizations: {
                title: "AfroMarry Payment",
                description: "Payment for cultural wedding items",
                logo: `${window.location.origin}${window.BASE_PATH}/assets/images/logo.png`
            }
        });
        
    } catch (error) {
        console.error('[Checkout] Flutterwave payment error:', error);
        hidePaymentModal();
        showMessage('Payment initialization failed: ' + error.message, 'error');
    }
}

// Process bank transfer
async function processBankTransfer() {
    try {
        const cartItems = await getCartItems();
        
        // Create order first
        const orderResponse = await fetch(actionUrl('orders.php'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                total_amount: checkout.orderData.total_amount,
                currency: checkout.orderData.currency,
                payment_method: checkout.orderData.payment_method,
                payment_reference: generatePaymentReference(),
                shipping_address: JSON.stringify(checkout.orderData.shipping),
                items: await getCartItems()
            })
        });
        
        if (!orderResponse.ok) {
            const errorText = await orderResponse.text();
            console.error('[Checkout] Bank transfer order API error:', errorText);
            throw new Error(`Order creation failed: ${orderResponse.status} - ${errorText}`);
        }
        
        const orderData = await orderResponse.json();
        
        if (!orderData.success) {
            throw new Error(orderData.message || 'Failed to create order');
        }
        
        // Show bank transfer details
        showBankTransferDetails(orderData.order_reference);
        
        // Redirect user to upload receipt/proof
        setTimeout(() => {
            window.location.href = pageUrl(`payment-verification.php?order_id=${orderData.order_id}`);
        }, 1200);
        
    } catch (error) {
        console.error('Bank transfer error:', error);
        showMessage('Error processing bank transfer. Please try again.', 'error');
    }
}

// Handle payment success
async function handlePaymentSuccess(paymentResponse, orderId) {
    try {
        hidePaymentModal();
        
        // For Paystack payments, verify the transaction first
        if (checkout.paymentMethod === 'paystack') {
            // Verify Paystack transaction
            const verifyResponse = await fetch(actionUrl('paystack-verify-transaction.php'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reference: paymentResponse.reference
                })
            });
            
            const verifyData = await verifyResponse.json();
            
            if (!verifyData.success) {
                throw new Error('Payment verification failed: ' + verifyData.message);
            }
            
            showMessage('Payment verified successfully!', 'success');
        } else {
            // For other payment methods, update order status
            const response = await fetch(actionUrl('orders.php'), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'paid'
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message);
            }
        }
        
        // Redirect to order success page
        showMessage('Order created successfully!', 'success');
        setTimeout(() => {
            window.location.href = pageUrl(`order-success.php?order_id=${orderId}`);
        }, 2000);
        
    } catch (error) {
        console.error('Payment success handling error:', error);
        showMessage('Payment successful but there was an error updating your order. Please contact support.', 'error');
    }
}

// Handle payment cancellation
function handlePaymentCancelled() {
    hidePaymentModal();
    showMessage('Payment was cancelled. You can try again or choose a different payment method.', 'info');
}

// Show bank transfer details
function showBankTransferDetails(orderReference) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>Bank Transfer Details</h3>
            <div class="bank-details">
                <div class="bank-detail">
                    <label>Bank Name:</label>
                    <span>Access Bank</span>
                </div>
                <div class="bank-detail">
                    <label>Account Name:</label>
                    <span>AfroMarry Limited</span>
                </div>
                <div class="bank-detail">
                    <label>Account Number:</label>
                    <span>1234567890</span>
                </div>
            <div class="bank-detail">
                <label>Amount:</label>
                <span>$ ${checkout.orderData.total_amount.toLocaleString()}</span>
            </div>
                <div class="bank-detail">
                    <label>Reference:</label>
                    <span>${orderReference}</span>
                </div>
            </div>
            <p class="bank-instructions">
                Please transfer the exact amount to the account above using the reference number. 
                Your order will be processed once payment is confirmed.
            </p>
            <div class="modal-actions">
                <button class="btn-primary" onclick="this.closest('.modal').remove()">I Understand</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Get cart items
async function getCartItems() {
    try {
        const response = await fetch(actionUrl('cart.php'));
        const data = await response.json();
        
        if (data.success) {
            return data.data.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                price: item.price
            }));
        }
        return [];
    } catch (error) {
        console.error('Error getting cart items:', error);
        return [];
    }
}

// Calculate subtotal from cart items
async function calculateSubtotal() {
    try {
        const cartItems = await getCartItems();
        let subtotal = 0;
        cartItems.forEach(item => {
            // Normalize price to max $10,000 per item for display
            const normalizedPrice = Math.min(parseFloat(item.price) || 0, 10000);
            subtotal += normalizedPrice * (item.quantity || 1);
        });
        return subtotal;
    } catch (error) {
        console.error('Error calculating subtotal:', error);
        return 0;
    }
}

// Generate payment reference
function generatePaymentReference() {
    return 'AFM' + Date.now() + Math.random().toString(36).substr(2, 9);
}

// Validate form
function validateForm() {
    const requiredFields = document.querySelectorAll('#checkout-form [required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField({ target: field })) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Validate individual field
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove existing error
    clearFieldError(e);
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address';
    }
    
    // Phone validation
    if (field.type === 'tel' && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid phone number';
    }
    
    // Show error if invalid
    if (!isValid) {
        showFieldError(field, errorMessage);
    }
    
    return isValid;
}

// Show field error
function showFieldError(field, message) {
    field.classList.add('error');
    
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
}

// Clear field error
function clearFieldError(e) {
    const field = e.target;
    field.classList.remove('error');
    
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
}

// Validate email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate phone
function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Show payment modal
function showPaymentModal() {
    const modal = document.getElementById('payment-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Hide payment modal
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
