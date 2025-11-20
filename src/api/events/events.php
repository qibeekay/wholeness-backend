<?php

namespace App\Api;

if (!file_exists(dirname(__DIR__) . '/../assets/helpers/headers.inc.php')) {
    die('headers.inc.php not found');
}
require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Config\Database;
use App\Controllers\EventController;
use App\Models\Events;
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
$db        = new Database($config['database']);
$controller = new EventController(new Events($db));

// ----- request details -----
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = array_values(array_filter(explode('/', trim($uri, '/'))));

// pop the first segment ("events") so $parts[0] becomes the resource
array_shift($parts);

$id     = isset($_GET['id'])     ? (int)$_GET['id']     : null;
$action = isset($_GET['action']) ? $_GET['action']      : null;

$input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
    ? json_decode(file_get_contents('php://input'), true) ?: []
    : $_POST;

// Also check for action in request body for POST/DELETE requests
if (empty($action) && isset($input['action'])) {
    $action = $input['action'];
}

/* ---------- routing ---------- */
switch (true) {
    /* ---- public ---- */
    case $method === 'GET' && !$id && !$action:
        echo $controller->listPublic();
        break;

    case $method === 'GET' && $id && !$action:
        echo $controller->showPublic($id);
        break;

    /* ---- user bookings ---- */
    case $method === 'POST' && $id && $action === 'book':
        echo $controller->book($id, $input);   // <── pass $input
        break;

    case $method === 'DELETE' && $id && $action === 'cancel':
        echo $controller->cancel($id, $input); // <── pass $input
        break;

    /* ---- admin ---- */
    case $method === 'GET' && !$id && !$action:
        echo $controller->fetchAll();          // admin list
        break;

    case $method === 'GET' && $id && !$action:
        echo $controller->fetchSingle($id);    // admin single + attendees
        break;

    case $method === 'GET' && $id && $action === 'attendees':
        echo $controller->fetchSingle($id);   // already returns attendees
        break;

    case $method === 'POST' && !$id && !$action:
        echo $controller->create($input, $_FILES);
        break;

    case $method === 'PUT' && $id:
    case $method === 'POST' && $id && !$action: // your front-end may still send POST
        echo $controller->update($id, $input, $_FILES);
        break;

    case $method === 'DELETE' && $id && !$action:
        echo $controller->delete($id);
        break;

    default:
        http_response_code(404);
        echo json_encode(['message' => 'Event endpoint not found']);
}
