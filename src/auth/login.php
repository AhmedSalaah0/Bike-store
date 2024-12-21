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
        $decoded = $jwtHandler->verifyToken($JWT, $_ENV['JWT_SECRET']);
        if ($decoded->data->token_type !== 'access') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
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
    $stmt = $con->prepare("SELECT * FROM admins WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $is_admin = false;
    if ($user) {
        $is_admin = true;
        $userData = [
            'user_id' => $user['Admin_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'is_admin' => $is_admin,
        ];
    } else {
        $stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $userData = [
                'user_id' => $user['customer_id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'is_admin' => $is_admin,
            ];
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        if ($is_admin || $user['is_verified'] == 1) {

            $jwtHandler = new JwtHandler();

            $token = $jwtHandler->generateToken($userData);

            $refreshTokenPayload = [
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60),
                'data' => [ $is_admin ? ['Admin_id' =>  $user['Admin_id']] : ['user_id' => $user['customer_id']]
                        ]            
                    ];
            $refreshToken = $jwtHandler->generateToken($refreshTokenPayload, 3600 * 24 * 7, 'refresh');

        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Verify your email']);
        }
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
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'database error']);
}

