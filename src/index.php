<?php
// Include database connection (adjust path if necessary)
include "database/dbConnection.php";

// Define the base path (relative to the domain root)
$base_path = '/bike-store';

// Parse the full URI and remove the base path
$full_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = str_replace($base_path, '', $full_uri);
$request = strtolower(trim($request, '/')); // Normalize the request path

// Map of allowed routes
$allowed_routes = [
    '' => 'index.php', // Homepage
    'index' => 'index.php',
    'home' => 'index.php',
    'auth/register' => 'auth/register.php',
    'auth/login' => 'auth/login.php',
    'smtp/sendEmail' => 'smtp/sendEmail.php',
    'auth/verify' => 'auth/verify.php',
    'auth/forgetpassword' => 'auth/forgetpassword.php',
    'auth/verifyotp' => 'auth/verifyOTP.php',
    'auth/resetnewpassword' => 'auth/resetNewPassword.php',
];

if (array_key_exists($request, $allowed_routes)) {
    $file_to_include = __DIR__ . '/' . $allowed_routes[$request];
    
    if (file_exists($file_to_include)) {
        require $file_to_include;
    } else {
        http_response_code(500);
        echo "500 Internal Server Error: Route file not found.";
    }
} else {
    // Return a 404 response for invalid routes
    http_response_code(404);
    echo "404 Not Found: The requested URL was not found on this server.";
}
