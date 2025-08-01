<?php
// cors.php - Should be included at the very top of your entry point

// Allowed origins
$allowedOrigins = [
    "*", // Wildcard for all origins (not recommended with credentials)
    "http://localhost:*", // All localhost ports
    "https://*" // All HTTPS domains
];

// Get the request origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Set CORS headers if origin is allowed
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');  // Cache preflight for 24h
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    http_response_code(200);
    exit;
}