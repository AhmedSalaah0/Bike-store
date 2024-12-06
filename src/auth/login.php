<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/../database/dbConnection.php";

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
            http_response_code(200);
            echo json_encode(["User Id" => $user['customer_id']]);
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

