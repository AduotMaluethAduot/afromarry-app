<?php
require_once __DIR__ . '/../config/paths.php';

if (!function_exists('has_uploaded_file_field')) {
    function has_uploaded_file_field(string $fieldName): bool {
        return isset($_FILES[$fieldName]) &&
               isset($_FILES[$fieldName]['error']) &&
               $_FILES[$fieldName]['error'] !== UPLOAD_ERR_NO_FILE;
    }
}

if (!function_exists('get_product_image_directory')) {
    function get_product_image_directory(int $userId, int $productId): array {
        $relative = 'u' . $userId . '/p' . $productId;
        $absolute = image_storage_path($relative);
        ensure_directory($absolute);
        return [$relative, $absolute];
    }
}

if (!function_exists('upload_product_image')) {
    function upload_product_image(array $file, int $userId, int $productId): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Unable to upload product image. Please try again.');
        }
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowed_extensions)) {
            throw new Exception('Invalid image format. Allowed types: JPG, PNG, GIF, WEBP.');
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            throw new Exception('Image size must be 5MB or less.');
        }
        
        try {
            $random = bin2hex(random_bytes(4));
        } catch (Exception $e) {
            $random = uniqid();
        }
        
        $filename = 'image_' . date('YmdHis') . '_' . $random . '.' . $extension;
        
        [$relativeDir, $absoluteDir] = get_product_image_directory($userId, $productId);
        $target_path = $absoluteDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            throw new Exception('Failed to save the uploaded image.');
        }
        
        return 'images/' . trim($relativeDir, '/') . '/' . $filename;
    }
}

if (!function_exists('delete_product_image')) {
    function delete_product_image(?string $path): void {
        if (!$path) {
            return;
        }
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($normalized, 'images/') !== 0) {
            return;
        }
        
        $absolute = project_path($normalized);
        if (is_file($absolute)) {
            @unlink($absolute);
        }
    }
}

if (!function_exists('delete_product_image_directory')) {
    function delete_product_image_directory(?string $path): void {
        if (!$path) {
            return;
        }
        
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($normalized, 'images/') !== 0) {
            return;
        }
        
        $directory = project_path(dirname($normalized));
        if (!is_dir($directory)) {
            return;
        }
        
        $items = glob($directory . '/*');
        if ($items) {
            foreach ($items as $item) {
                if (is_file($item)) {
                    @unlink($item);
                }
            }
        }
        
        @rmdir($directory);
    }
}
