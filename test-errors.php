<?php
// Test PHP 8.4 Error Display
echo "<h1>PHP 8.4 Error Display Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<hr>";

// Test 1: Trigger a deprecation warning (implicit nullable)
echo "<h2>Test 1: Implicit Nullable Parameter</h2>";
function testNullable($param = null) {
    return "This should trigger a deprecation warning!";
}
echo testNullable();
echo "<hr>";

// Show error_reporting settings
echo "<h2>Current Error Settings:</h2>";
echo "<pre>";
echo "error_reporting: " . error_reporting() . " (E_ALL = " . E_ALL . ")\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "display_startup_errors: " . ini_get('display_startup_errors') . "\n";
echo "</pre>";
