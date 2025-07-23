<?php

namespace App\Api;

if (!file_exists(dirname(__DIR__) . '/../assets/helpers/headers.inc.php')) {
    die('headers.inc.php not found');
}
require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';


use Dotenv\Dotenv;

use App\Config\Database;
use App\Controllers\AuthController;
use App\Helpers\ErrorHandler;




// Register error and exception handlers
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);

// Load .env file
$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

// Load configuration
$config = require dirname(__DIR__, 2) . '/assets/config/config.php';

// Initialize database and controller
$db = new Database($config['database']);
$authController = new AuthController($db, $config);

// Handle Google OAuth callback
echo $authController->handleGoogleCallback();