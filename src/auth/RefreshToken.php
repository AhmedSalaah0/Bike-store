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

require __DIR__ . "/../../vendor/autoload.php";
include __DIR__ . "/JWTHandler.php";
header("Content-Type: application/json");
$refreshToken = $_COOKIE["refresh_token"] ?? '';

if ($refreshToken) {
    try {
        $jwtHandler = new JwtHandler();
        $decoded = $jwtHandler->verifyToken($refreshToken, $_ENV['refresh_token_secret']);
        
        // Ensure the token type is 'refresh'
        if ($decoded->data->token_type !== 'refresh') {
            throw new Exception('Invalid token type');
        }

        $userId = $decoded->data->data->user_id;

        // Generate a new access token
        $accessToken = $jwtHandler->generateToken(
            ['user_id' => $userId],
            3600, // Token expiration time in seconds
            'access'
        );

        echo json_encode([
            'access_token' => $accessToken
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired refresh token']);
    }
} else {
    http_response_code(401);
    echo json_encode(['error' => 'No refresh token provided']);
    exit();
}
