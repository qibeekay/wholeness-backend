<?php

namespace App\Api;

if (!file_exists(dirname(__DIR__) . '/../assets/helpers/headers.inc.php')) {
    die('headers.inc.php not found');
}
require_once dirname(__DIR__) . '/../assets/helpers/headers.inc.php';

require_once dirname(__DIR__, 3) . '/vendor/autoload.php';


use Dotenv\Dotenv;

use App\Config\Database;
use App\Controllers\BlogController;
use App\Models\Blog;
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
$controller = new BlogController(new Blog($db));

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$excerpt = isset($_GET['excerpt']) ? (string) $_GET['excerpt'] : null;
$input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
    ? json_decode(file_get_contents('php://input'), true) ?: []
    : $_POST;

switch ($method) {
    case 'GET':
        echo isset($excerpt) ? $controller->fetchByExcept($excerpt) : $controller->fetchAll();
        break;
    case 'POST':
        if ($id) {
            // treat as update
            echo $controller->update($id, $input, $_FILES);
        } else {
            // normal create
            echo $controller->blog($input, $_FILES);
        }
        break;
    // case 'PUT':
    // case 'PATCH':
    //     echo $controller->update($id, $input, $_FILES);
    //     break;
    case 'DELETE':
        echo $controller->destroy($id);
        break;
}