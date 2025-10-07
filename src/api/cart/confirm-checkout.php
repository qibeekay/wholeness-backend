<?php

namespace App\Api;

// ob_start();

// if (headers_sent($file, $line)) {
//     die("Headers already sent in $file on line $line");
// }

// require_once dirname(__DIR__) . '/../assets/helpers/cors.php';
require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';

if (!file_exists(dirname(__DIR__) . '/../assets/helpers/headers.inc.php')) {
    die('headers.inc.php not found');
}

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Controllers\CheckoutController;
use App\Models\Store;
use App\Helpers\ErrorHandler;
use App\Helpers\EmailService;



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
$store = new Store($db);
$mailer = new EmailService(
    $config['services']['apiUrl'],
    $config['services']['bearerToken']
);
$ctrl = new CheckoutController($db, $store, $mailer);

/* ----- route POST /api/store/cart/checkout ----- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}


echo $ctrl->confirmIntent();
