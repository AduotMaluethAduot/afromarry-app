<?php
/**
 * Simple CSS and JS minification functions
 */

/**
 * Minify CSS
 */
function minify_css($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // Remove whitespace around special characters
    $css = preg_replace('/\s*([{}|:;,])\s*/', '$1', $css);
    
    // Remove unnecessary semicolons
    $css = preg_replace('/;}/', '}', $css);
    
    // Remove extra whitespace
    $css = preg_replace('/\s+/', ' ', $css);
    
    // Remove leading and trailing whitespace
    $css = trim($css);
    
    return $css;
}

/**
 * Minify JavaScript
 */
function minify_js($js) {
    // Remove comments
    $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
    $js = preg_replace('!//.*!', '', $js);
    
    // Remove whitespace around special characters
    $js = preg_replace('/\s*([{}|:;,()=\[\]])\s*/', '$1', $js);
    
    // Remove extra whitespace
    $js = preg_replace('/\s+/', ' ', $js);
    
    // Remove leading and trailing whitespace
    $js = trim($js);
    
    return $js;
}

/**
 * Generate minified CSS file
 */
function generate_minified_css($source_file, $dest_file) {
    if (!file_exists($source_file)) {
        return false;
    }
    
    $css = file_get_contents($source_file);
    $minified_css = minify_css($css);
    
    return file_put_contents($dest_file, $minified_css) !== false;
}

/**
 * Generate minified JS file
 */
function generate_minified_js($source_file, $dest_file) {
    if (!file_exists($source_file)) {
        return false;
    }
    
    $js = file_get_contents($source_file);
    $minified_js = minify_js($js);
    
    return file_put_contents($dest_file, $minified_js) !== false;
}
?>