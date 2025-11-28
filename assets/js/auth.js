// ============================================================================
// Authentication Module - AfroMarry (Traditional Form Submission Version)
// ============================================================================

// Prevent multiple initializations
if (window.authModuleLoaded) {
  console.warn('⚠️ auth.js is being loaded multiple times! Check your HTML for duplicate script tags.');
  // Don't throw an error, just exit silently to prevent breaking the page
  return;
}
window.authModuleLoaded = true;

console.log('✅ auth.js loaded successfully');

// ---------------------------- 
// Configuration & Constants
// ---------------------------- 
const REDIRECT_PATHS = {
  DASHBOARD: '/AfroMarry/pages/dashboard.php',
  LOGIN: '/AfroMarry/auth/login.php'
};

const VALIDATION_RULES = {
  MIN_PASSWORD_LENGTH: 6,
  EMAIL_REGEX: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
};

// ---------------------------- 
// Initialize on DOM Ready
// ---------------------------- 
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeAuth);
} else {
  // DOM is already ready
  initializeAuth();
}

function initializeAuth() {
  console.log('🚀 Initializing auth module');
  
  // Check if already initialized
  if (window.authInitialized) {
    console.warn('⚠️ Auth module already initialized, skipping...');
    return;
  }
  window.authInitialized = true;
  
  setupAuthEventListeners();
}

// ---------------------------- 
// Event Listener Setup
// ---------------------------- 
function setupAuthEventListeners() {
  console.log('🔧 Setting up event listeners');
  
  // Use a more robust approach to find and setup forms
  const forms = {
    login: document.getElementById('login-form'),
    register: document.getElementById('register-form'),
    signup: document.getElementById('signup-form'),
    forgotPassword: document.getElementById('forgot-password-form'),
    resetPassword: document.getElementById('reset-password-form')
  };

  // Add event listeners with error handling
  if (forms.login) {
    try {
      forms.login.addEventListener('submit', handleLogin);
      console.log('✅ Login handler attached');
    } catch (error) {
      console.error('❌ Failed to attach login handler:', error);
    }
  }
  
  if (forms.register) {
    try {
      forms.register.addEventListener('submit', handleRegister);
      console.log('✅ Register handler attached');
    } catch (error) {
      console.error('❌ Failed to attach register handler:', error);
    }
  }
  
  if (forms.signup) {
    try {
      forms.signup.addEventListener('submit', handleSignup);
      console.log('✅ Signup handler attached');
    } catch (error) {
      console.error('❌ Failed to attach signup handler:', error);
    }
  }
  
  if (forms.forgotPassword) {
    try {
      forms.forgotPassword.addEventListener('submit', handleForgotPassword);
      console.log('✅ Forgot password handler attached');
    } catch (error) {
      console.error('❌ Failed to attach forgot password handler:', error);
    }
  }
  
  if (forms.resetPassword) {
    try {
      forms.resetPassword.addEventListener('submit', handleResetPassword);
      console.log('✅ Reset password handler attached');
    } catch (error) {
      console.error('❌ Failed to attach reset password handler:', error);
    }
  }
}

// ============================================================================
// Form Handlers (Enhanced for better user experience)
// ============================================================================

// ---------------------------- 
// Handle Login
// ---------------------------- 
function handleLogin(e) {
  console.log('🔐 Login form submitted');
  
  // Prevent multiple submissions
  if (window.loginSubmitting) {
    console.warn('⚠️ Login form already submitting, ignoring duplicate submission');
    e.preventDefault();
    return;
  }
  
  const form = e.target;
  const email = form.querySelector('input[name="email"]').value;
  const password = form.querySelector('input[name="password"]').value;
  
  // Basic client-side validation
  if (!email || !password) {
    e.preventDefault();
    showMessage('Please fill in all required fields.', 'error');
    return;
  }
  
  if (!validateEmail(email)) {
    e.preventDefault();
    showMessage('Please enter a valid email address.', 'error');
    return;
  }
  
  if (password.length < VALIDATION_RULES.MIN_PASSWORD_LENGTH) {
    e.preventDefault();
    showMessage(`Password must be at least ${VALIDATION_RULES.MIN_PASSWORD_LENGTH} characters.`, 'error');
    return;
  }
  
  // Show loading state
  const submitButton = form.querySelector('button[type="submit"]');
  setButtonLoading(submitButton, true, 'Logging in...');
  
  // Set submission state
  window.loginSubmitting = true;
  
  // Allow form to submit normally (server-side redirect will handle navigation)
  console.log('📤 Submitting login form to server');
}

// ---------------------------- 
// Handle Register
// ---------------------------- 
function handleRegister(e) {
  console.log('📝 Register form submitted');
  
  // Prevent multiple submissions
  if (window.registerSubmitting) {
    console.warn('⚠️ Register form already submitting, ignoring duplicate submission');
    e.preventDefault();
    return;
  }
  
  const form = e.target;
  const full_name = form.querySelector('input[name="full_name"]').value;
  const email = form.querySelector('input[name="email"]').value;
  const password = form.querySelector('input[name="password"]').value;
  const phone = form.querySelector('input[name="phone"]').value;
  
  // Basic client-side validation
  if (!full_name || !email || !password) {
    e.preventDefault();
    showMessage('Please fill in all required fields.', 'error');
    return;
  }
  
  if (full_name.length < 2) {
    e.preventDefault();
    showMessage('Full name must be at least 2 characters.', 'error');
    return;
  }
  
  if (!validateEmail(email)) {
    e.preventDefault();
    showMessage('Please enter a valid email address.', 'error');
    return;
  }
  
  if (password.length < VALIDATION_RULES.MIN_PASSWORD_LENGTH) {
    e.preventDefault();
    showMessage(`Password must be at least ${VALIDATION_RULES.MIN_PASSWORD_LENGTH} characters.`, 'error');
    return;
  }
  
  // Show loading state
  const submitButton = form.querySelector('button[type="submit"]');
  setButtonLoading(submitButton, true, 'Creating account...');
  
  // Set submission state
  window.registerSubmitting = true;
  
  // Allow form to submit normally (server-side redirect will handle navigation)
  console.log('📤 Submitting register form to server');
}

// ---------------------------- 
// Handle Signup (Modal)
// ---------------------------- 
function handleSignup(e) {
  console.log('📝 Signup form submitted');
  
  // Prevent multiple submissions
  if (window.signupSubmitting) {
    console.warn('⚠️ Signup form already submitting, ignoring duplicate submission');
    e.preventDefault();
    return;
  }
  
  const form = e.target;
  const full_name = form.querySelector('input[name="full_name"]').value;
  const email = form.querySelector('input[name="email"]').value;
  const password = form.querySelector('input[name="password"]').value;
  
  // Basic client-side validation
  if (!full_name || !email || !password) {
    e.preventDefault();
    showMessage('Please fill in all required fields.', 'error');
    return;
  }
  
  if (full_name.length < 2) {
    e.preventDefault();
    showMessage('Full name must be at least 2 characters.', 'error');
    return;
  }
  
  if (!validateEmail(email)) {
    e.preventDefault();
    showMessage('Please enter a valid email address.', 'error');
    return;
  }
  
  if (password.length < VALIDATION_RULES.MIN_PASSWORD_LENGTH) {
    e.preventDefault();
    showMessage(`Password must be at least ${VALIDATION_RULES.MIN_PASSWORD_LENGTH} characters.`, 'error');
    return;
  }
  
  // Show loading state
  const submitButton = form.querySelector('button[type="submit"]');
  setButtonLoading(submitButton, true, 'Signing up...');
  
  // Set submission state
  window.signupSubmitting = true;
  
  // Allow form to submit normally (server-side redirect will handle navigation)
  console.log('📤 Submitting signup form to server');
}

// ---------------------------- 
// Handle Forgot Password
// ---------------------------- 
function handleForgotPassword(e) {
  console.log('🔑 Forgot password form submitted');
  
  // Prevent multiple submissions
  if (window.forgotPasswordSubmitting) {
    console.warn('⚠️ Forgot password form already submitting, ignoring duplicate submission');
    e.preventDefault();
    return;
  }
  
  const form = e.target;
  const email = form.querySelector('input[name="email"]').value;
  
  // Basic client-side validation
  if (!email) {
    e.preventDefault();
    showMessage('Please enter your email address.', 'error');
    return;
  }
  
  if (!validateEmail(email)) {
    e.preventDefault();
    showMessage('Please enter a valid email address.', 'error');
    return;
  }
  
  // Show loading state
  const submitButton = form.querySelector('button[type="submit"]');
  setButtonLoading(submitButton, true, 'Sending...');
  
  // Set submission state
  window.forgotPasswordSubmitting = true;
  
  // Allow form to submit normally (server-side redirect will handle navigation)
  console.log('📤 Submitting forgot password form to server');
}

// ---------------------------- 
// Handle Reset Password
// ---------------------------- 
function handleResetPassword(e) {
  console.log('🔄 Reset password form submitted');
  
  // Prevent multiple submissions
  if (window.resetPasswordSubmitting) {
    console.warn('⚠️ Reset password form already submitting, ignoring duplicate submission');
    e.preventDefault();
    return;
  }
  
  const form = e.target;
  const password = form.querySelector('input[name="password"]').value;
  const confirm_password = form.querySelector('input[name="confirm_password"]').value;
  
  // Basic client-side validation
  if (!password || !confirm_password) {
    e.preventDefault();
    showMessage('Please fill in all password fields.', 'error');
    return;
  }
  
  if (password.length < VALIDATION_RULES.MIN_PASSWORD_LENGTH) {
    e.preventDefault();
    showMessage(`Password must be at least ${VALIDATION_RULES.MIN_PASSWORD_LENGTH} characters.`, 'error');
    return;
  }
  
  if (password !== confirm_password) {
    e.preventDefault();
    showMessage('Passwords do not match.', 'error');
    return;
  }
  
  // Show loading state
  const submitButton = form.querySelector('button[type="submit"]');
  setButtonLoading(submitButton, true, 'Resetting...');
  
  // Set submission state
  window.resetPasswordSubmitting = true;
  
  // Allow form to submit normally (server-side redirect will handle navigation)
  console.log('📤 Submitting reset password form to server');
}

// ============================================================================
// Validation Functions
// ============================================================================

function validateEmail(email) {
  return VALIDATION_RULES.EMAIL_REGEX.test(email);
}

// ============================================================================
// UI Helper Functions
// ============================================================================

function showMessage(message, type = 'info') {
  const existing = document.querySelector('.floating-message');
  if (existing) {
    existing.remove();
  }
  
  const messageDiv = document.createElement('div');
  messageDiv.className = `floating-message alert-${type}`;
  messageDiv.textContent = message;
  
  Object.assign(messageDiv.style, {
    position: 'fixed',
    top: '20px',
    right: '20px',
    padding: '1rem 1.5rem',
    borderRadius: '8px',
    backgroundColor: type === 'success' ? '#10b981' : type === 'error' ? '#dc3545' : '#3b82f6',
    color: 'white',
    fontWeight: '500',
    boxShadow: '0 4px 6px rgba(0, 0, 0, 0.1)',
    zIndex: '10000',
    animation: 'slideIn 0.3s ease-out'
  });
  
  document.body.appendChild(messageDiv);
  
  setTimeout(() => {
    messageDiv.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => messageDiv.remove(), 300);
  }, 4000);
}

function setButtonLoading(button, isLoading, text = '') {
  if (!button) return;
  
  if (isLoading) {
    button.disabled = true;
    button.dataset.originalText = button.textContent;
    button.textContent = text || 'Loading...';
    button.style.opacity = '0.7';
    button.style.cursor = 'not-allowed';
  } else {
    button.disabled = false;
    button.textContent = text || button.dataset.originalText || 'Submit';
    button.style.opacity = '1';
    button.style.cursor = 'pointer';
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = 'none';
    
    const form = modal.querySelector('form');
    if (form) {
      form.reset();
    }
  }
}

// Add CSS animations
if (!document.getElementById('auth-animations')) {
  const style = document.createElement('style');
  style.id = 'auth-animations';
  style.textContent = `
    @keyframes slideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    .field-error { animation: fadeIn 0.2s ease-out; }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
  `;
  document.head.appendChild(style);
}
