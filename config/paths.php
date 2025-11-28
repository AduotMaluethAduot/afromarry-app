<?php
/**
 * Path Configuration for AfroMarry
 * Automatically detects the base path based on project location
 */

// Absolute project root
if (!defined('BASE_DIR')) {
    define('BASE_DIR', str_replace('\\', '/', realpath(__DIR__ . '/..')));
}

function project_path($path = '') {
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if ($path === '') {
        return BASE_DIR;
    }
    return BASE_DIR . '/' . $path;
}

function ensure_directory($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function image_storage_path($path = '') {
    return project_path('images/' . ltrim(str_replace('\\', '/', $path), '/'));
}

// Detect base path automatically
function getBasePath() {
    // Get the document root
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    
    // Get the current file's directory
    $file_dir = str_replace('\\', '/', dirname(__DIR__));
    
    // Calculate relative path from document root
    $relative_path = str_replace($doc_root, '', $file_dir);
    $relative_path = trim($relative_path, '/');
    
    if (empty($relative_path)) {
        // Root installation
        return '';
    }
    
    // Return the project folder path
    return '/' . $relative_path;
}

// Set base path constant
if (!defined('BASE_PATH')) {
    define('BASE_PATH', getBasePath());
}

// Helper functions for URL generation
function base_url($path = '') {
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

function asset_url($path = '') {
    return base_url('assets/' . ltrim($path, '/'));
}

function page_url($path = '') {
    return base_url('pages/' . ltrim($path, '/'));
}

function action_url($path = '') {
    return base_url('actions/' . ltrim($path, '/'));
}

function auth_url($path = '') {
    return base_url('auth/' . ltrim($path, '/'));
}

function admin_url($path = '') {
    return base_url('admin/' . ltrim($path, '/'));
}

function image_url($path = '') {
    return base_url('images/' . ltrim($path, '/'));
}

?>

