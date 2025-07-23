<?php

namespace App\Api;

use Dotenv\Dotenv;
use App\Assets\Config\Database;
use App\Controllers\AuthController;
use App\Helpers\ErrorHandler;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';


// Register error and exception handlers
set_error_handler([ErrorHandler::class, 'handleError']);
set_exception_handler([ErrorHandler::class, 'handleException']);

// Load .env file
$dotenv = Dotenv::createImmutable(dirname(__DIR__, 2) . '/src');
$dotenv->load();

// Load configuration
$config = require dirname(__DIR__, 2) . '/src/assets/config/config.php';

// Initialize database and controller
$db = new Database($config['database']);
$authController = new AuthController($db, $config);

// Handle Google OAuth callback
echo $authController->redirectToGoogle();