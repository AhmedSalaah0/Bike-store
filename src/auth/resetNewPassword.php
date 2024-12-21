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

include __DIR__ . '/../database/dbConnection.php';

$inputData = file_get_contents('php://input');
$userData = json_decode($inputData, true);

$password = htmlspecialchars(strip_tags($userData['password']));
$password2 = htmlspecialchars(strip_tags($userData['password2']));
$email = htmlspecialchars(strip_tags($userData['email']) ?? '');
if ($password != $password2) {
    echo json_encode(['error' => 'Passwords do not match']);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters long']);
    exit();
}

try {
    if (!$email) {
        echo json_encode(['error' => 'No email found']);
        exit();
    }
    $stmt = $con->prepare("SELECT customer_id FROM customers WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $cutomer_id = $user['customer_id'];


    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);


    $stmt = $con->prepare("UPDATE customers SET password = :password WHERE customer_id = :id");
    $stmt->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
    $stmt->bindParam(":id", $cutomer_id, PDO::PARAM_STR);
    $stmt->execute();
    echo json_encode(['success' => 'Password updated successfully']);
    exit();
} catch (PDOException $e) {
    echo json_encode(['error' => 'database error']);
    exit();
}

