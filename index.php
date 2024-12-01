<?php
include "dbConnection.php";

$base_path = '/restApi'; 
// Parse and clean request URL
$request = str_replace($base_path, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$request = strtolower(trim($request, '/')); 

$allowed_routes = [
    '' => '/home.php',
    'index' => '/home.php',
    'home' => '/home.php',
    'auth/register' => '/auth/register.php',
    'auth/login' => '/auth/login.php',
];


if (array_key_exists($request, $allowed_routes)) {
    require __DIR__ . $allowed_routes[$request];
} else {
    http_response_code(404);
    echo "404 Error";
}
