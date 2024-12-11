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

include __DIR__ . "/../database/dbConnection.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . "/JWTHandler.php";


if ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
    $email = htmlspecialchars(strip_tags($_POST['email'] ?? ''));
    $password = htmlspecialchars(strip_tags($_POST['password'] ?? ''));
} else {
    $userData = json_decode(file_get_contents('php://input'), true);
    if (!$userData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data format']);
        exit();
    }
    $email = htmlspecialchars(strip_tags($userData['email'] ?? ''));
    $password = htmlspecialchars(strip_tags($userData['password'] ?? ''));
    $JWT = $userData['token'] ?? '';
}

if (!empty($JWT)) {
    try {
        $jwtHandler = new JwtHandler();
        $decoded = $jwtHandler->verifyToken($JWT);
        http_response_code(200);
            echo json_encode([
            'message' => 'Token is valid',
            'user' => $decoded->data // Return user data from the token
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
}



if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit();
}


try {
    $stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified'] == 1) {
            $jwtHandler = new JwtHandler();

            $userData =[
                'user_id' => $user['customer_id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name']
            ];

            $token = $jwtHandler->generateToken($userData);
            $refreshTokenPayload = [
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60),
                'data' => ['user_id' => $user['customer_id']]
            ];
            $refreshToken = JWT::encode($refreshTokenPayload, $_ENV['JWT_SECRET'], 'HS256');
            

            http_response_code(200);
            echo json_encode([
                'message' => 'Login successful',
                'access_token' => $token, // Access token
                'refresh_token' => $refreshToken // Refresh token
            ]);
            setcookie(
                'refresh_token',   // Cookie name
                $refreshToken,     // Cookie value
                [
                    'expires' => time() + (7 * 24 * 60 * 60), // Expiration time
                    'path' => '/',                           
                    'domain' => 'localhost',                  // Match domain
                    'secure' => false,                       
                    'httponly' => true,                       
                    'samesite' => 'Lax',                      // Allow cross-origin
                ]
            );
            http_response_code(200);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Verify your email']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => $ex->getMessage()]);
}

