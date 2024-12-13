<?php
header("Access-Control-Allow-Origin: http://localhost:5501");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

setcookie('refresh_token', '', [
    'expires' => time() - 3600 * 7,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

unset($_COOKIE['refresh_token']);

echo json_encode(['message' => 'Logged out successfully']);