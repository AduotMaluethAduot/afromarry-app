<?php
/**
 * Generate minified versions of CSS and JS files
 */

require_once __DIR__ . '/minify.php';

// Minify CSS
$css_files = [
    __DIR__ . '/../assets/css/style.css' => __DIR__ . '/../assets/css/style.min.css'
];

foreach ($css_files as $source => $dest) {
    if (generate_minified_css($source, $dest)) {
        echo "Minified CSS: $source -> $dest\n";
    } else {
        echo "Failed to minify CSS: $source\n";
    }
}

// Minify JS
$js_files = [
    __DIR__ . '/../assets/js/ads.js' => __DIR__ . '/../assets/js/ads.min.js',
    __DIR__ . '/../assets/js/auth.js' => __DIR__ . '/../assets/js/auth.min.js',
    __DIR__ . '/../assets/js/checkout-momo.js' => __DIR__ . '/../assets/js/checkout-momo.min.js',
    __DIR__ . '/../assets/js/checkout.js' => __DIR__ . '/../assets/js/checkout.min.js',
    __DIR__ . '/../assets/js/config.js' => __DIR__ . '/../assets/js/config.min.js',
    __DIR__ . '/../assets/js/dowry-calculator.js' => __DIR__ . '/../assets/js/dowry-calculator.min.js',
    __DIR__ . '/../assets/js/experts.js' => __DIR__ . '/../assets/js/experts.min.js',
    __DIR__ . '/../assets/js/main.js' => __DIR__ . '/../assets/js/main.min.js',
    __DIR__ . '/../assets/js/marketplace.js' => __DIR__ . '/../assets/js/marketplace.min.js'
];

foreach ($js_files as $source => $dest) {
    if (generate_minified_js($source, $dest)) {
        echo "Minified JS: $source -> $dest\n";
    } else {
        echo "Failed to minify JS: $source\n";
    }
}

echo "Minification complete!\n";
?>