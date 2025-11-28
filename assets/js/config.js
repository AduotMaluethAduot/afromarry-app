// AfroMarry Base Path Configuration
// Automatically detects the base path for API calls

// Get base path from the current page location
function getBasePath() {
    // First, try to use BASE_PATH injected from PHP
    if (typeof window.PHP_BASE_PATH !== 'undefined' && window.PHP_BASE_PATH !== null) {
        return window.PHP_BASE_PATH;
    }
    
    const path = window.location.pathname;
    const pathParts = path.split('/').filter(p => p);
    
    // If path is /AfroMarry/index.php, pathParts = ['AfroMarry', 'index.php']
    // If path is /AfroMarry/, pathParts = ['AfroMarry']
    // If path is /index.php, pathParts = ['index.php']
    
    // Check if we're in index.php in a subdirectory
    if (path.includes('index.php') && pathParts.length >= 2) {
        // We're at /AfroMarry/index.php
        return '/' + pathParts[0];
    }
    
    // Check if path ends with / and has at least one part (e.g., /AfroMarry/)
    if (path.endsWith('/') && pathParts.length > 0) {
        // Check if this is likely a project folder (not just root /)
        if (pathParts.length === 1 && pathParts[0] !== '' && pathParts[0] !== 'index.php') {
            return '/' + pathParts[0];
        }
    }
    
    // Check if we're in a subdirectory folder structure
    if (path.includes('/pages/') || path.includes('/admin/') || path.includes('/auth/') || path.includes('/actions/')) {
        // Extract the project folder name (first part before /pages/, /admin/, etc.)
        if (pathParts.length > 1) {
            const projectName = pathParts[0];
            // Make sure it's not 'pages', 'admin', etc.
            if (!['pages', 'admin', 'auth', 'actions'].includes(projectName)) {
                return '/' + projectName;
            }
        }
    }
    
    // Fallback: try regex match for /projectname/pages|admin|auth|actions
    const match = path.match(/^\/([^\/]+)\/(?:pages|admin|auth|actions)/);
    if (match && match[1]) {
        return '/' + match[1];
    }
    
    // If we're at root (/) with no subdirectory
    if (pathParts.length === 0 || (pathParts.length === 1 && pathParts[0] === 'index.php')) {
        return '';
    }
    
    // Default: assume first part is project folder if multiple parts exist
    if (pathParts.length > 0 && pathParts[0] !== 'index.php') {
        return '/' + pathParts[0];
    }
    
    // Final fallback: empty string for root installation
    return '';
}

// Set global base path
window.BASE_PATH = getBasePath();

// Payment gateway configuration (from backend)
// Only set defaults if not already defined by backend
if (typeof window.PAYSTACK_PUBLIC_KEY === 'undefined' || window.PAYSTACK_PUBLIC_KEY === null) {
    window.PAYSTACK_PUBLIC_KEY = 'pk_test_your_paystack_public_key';
}
if (typeof window.FLUTTERWAVE_PUBLIC_KEY === 'undefined' || window.FLUTTERWAVE_PUBLIC_KEY === null) {
    window.FLUTTERWAVE_PUBLIC_KEY = 'FLWPUBK_TEST-your_flutterwave_public_key';
}

// Helper functions for URL generation
window.baseUrl = function(path) {
    path = path.replace(/^\//, ''); // Remove leading slash
    return window.BASE_PATH + '/' + path;
};

window.actionUrl = function(path) {
    path = path.replace(/^\//, ''); // Remove leading slash
    return window.BASE_PATH + '/actions/' + path;
};

window.pageUrl = function(path) {
    path = path.replace(/^\//, ''); // Remove leading slash
    return window.BASE_PATH + '/pages/' + path;
};

window.authUrl = function(path) {
    path = path.replace(/^\//, ''); // Remove leading slash
    return window.BASE_PATH + '/auth/' + path;
};

window.adminUrl = function(path) {
    path = path.replace(/^\//, ''); // Remove leading slash
    return window.BASE_PATH + '/admin/' + path;
};

// Removed debug logging for production
// if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
//     console.log('[AfroMarry] Base path detected:', window.BASE_PATH);
// }

