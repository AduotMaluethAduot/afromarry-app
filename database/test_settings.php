<?php
require_once '../config/settings.php';

// Test getting all settings
$settings = getAllSettings();

echo "Current settings:\n";
foreach ($settings as $key => $value) {
    echo "$key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

// Test setting a value
echo "\nSetting test value...\n";
setSetting('test_key', 'test_value', 'string');

// Test getting the value back
$value = getSetting('test_key');
echo "Retrieved test value: $value\n";

// Test updating the value
echo "\nUpdating test value...\n";
setSetting('test_key', 'updated_value', 'string');

// Test getting the updated value
$value = getSetting('test_key');
echo "Retrieved updated value: $value\n";

echo "\nTest completed successfully!\n";
?>