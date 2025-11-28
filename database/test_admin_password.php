<?php
/**
 * Test Admin Password
 * Tests common passwords against the default admin hash
 */

$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

$passwords_to_test = [
    'password',
    'admin',
    'admin123',
    'password123',
    'secret',
    '12345678',
    'admin@afromarry',
    'AfroMarry2024',
    'AfroMarry',
];

echo "Testing passwords against the default admin hash...\n\n";
echo "Hash: $hash\n\n";

foreach ($passwords_to_test as $pwd) {
    if (password_verify($pwd, $hash)) {
        echo "✓ MATCH FOUND! Password is: '$pwd'\n";
        echo "\n=== ADMIN LOGIN CREDENTIALS ===\n";
        echo "Email: admin@afromarry.com\n";
        echo "Password: $pwd\n";
        echo "Login URL: http://localhost/AfroMarry/admin/login.php\n";
        exit(0);
    } else {
        echo "✗ '$pwd' - No match\n";
    }
}

echo "\nNone of the common passwords matched.\n";
echo "The password may have been changed, or it's a custom password.\n";
echo "You may need to reset it using: php database/reset_admin_password.php\n";

