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


include __DIR__ . '/../database/dbConnection.php';
include __DIR__ . '/../smtp/sendEmail.php';
include __DIR__ . '/JWTHandler.php';

$userData = json_decode(file_get_contents('php://input'), true);

$first_name = $userData['first_name'] ?? '';
$last_name = $userData['last_name'] ??'';
$email = $userData['email'] ?? '';
$current_password = $userData['current_password'] ??'';
$password = $userData['password'] ??'';
$confirm_password = $userData['confirm_password'] ??'';
$JWT = $userData['token'] ?? '';

if (!empty($JWT)) {
    try {
        $jwtHandler = new JwtHandler();
        $decoded = $jwtHandler->verifyToken($JWT, $_ENV['JWT_SECRET']);
        if ($decoded->data->token_type !== 'access') {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit();
        }
        $user_id = $decoded->data->user_id;
        $token_email = $decoded->data->email;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
}


if (!$first_name || !$last_name || !$email || !$current_password || !$password || !$confirm_password || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit();
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match']);
    exit();
}

try {
    $stmt = $con->prepare("SELECT * FROM customers WHERE customer_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user)
    {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit();
    }

    if (!password_verify($current_password, $user['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid password']);
        exit();
    }
    $verify = 1;
    $tkn = null;
    if ($user['email'] != $token_email)
    {
        require __DIR__ . '/generateToken.php';
        sendVerificationEmail($email, $user['first_name'], $user['last_name'], 'Verification_mail',$verification_link);
        $verify = 0;
        $tkn = $verification_token;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $con->prepare("UPDATE customers SET first_name = :first_name, last_name = :last_name, email = :email, password = :password,
    verification_token = :verification_token, is_verified = :verify WHERE customer_id = :user_id");
    $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
    $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':verification_token', $tkn, PDO::PARAM_STR);
    $stmt->bindParam(':verify', $verify, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $payload = [
        'user_id'=> $user_id,
        'first_name'=> $first_name,
        'last_name'=> $last_name,
        'email'=> $email,
        'token_type'=> 'access',
    ];
    $token = $jwtHandler->generateToken($payload);

    echo json_encode([
        'message' => 'Profile updated successfully',
        'token'=> $token
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error' . $e->getMessage()]);
    exit();
}