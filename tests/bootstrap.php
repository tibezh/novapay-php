<?php

declare(strict_types=1);

/**
 * Bootstrap file for PHPUnit tests
 */

// Ensure we're using the correct autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Set timezone for consistent test results
date_default_timezone_set('UTC');

// Disable output buffering for tests
if (ob_get_level()) {
  ob_end_clean();
}

// Mock environment variables for testing
$_ENV['NOVAPAY_TEST_MODE'] = 'true';
$_SERVER['HTTP_USER_AGENT'] = 'NovaPay-PHP-Tests';

// Create test directories if they don't exist
$testDirs = [
  __DIR__ . '/temp',
  __DIR__ . '/logs'
];

foreach ($testDirs as $dir) {
  if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
  }
}

echo "🧪 NovaPay PHP Library Tests Bootstrap\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHPUnit: Running tests...\n\n";
