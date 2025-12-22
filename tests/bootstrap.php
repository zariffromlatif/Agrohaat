<?php
/**
 * PHPUnit Bootstrap File
 * Sets up test environment
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Include autoloader if using Composer
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// Include config (for database connection in tests)
require_once BASE_PATH . '/config/config.php';

// Set test database credentials (use separate test database)
if (!defined('TEST_DB_NAME')) {
    define('TEST_DB_NAME', 'agrohaat_test_db');
}

