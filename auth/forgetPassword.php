<?php
include __DIR__ . '/../dbConnection.php';
include __DIR__ . '/../smtp/sendEmail.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$inputData = file_get_contents('php://input');
$UserData = json_decode($inputData, true);

if (!$UserData) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit();
}

$UserEmail = $UserData['email'];
$stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
$stmt->bindParam(':email', $UserEmail, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit();
} else {
    $OTP = rand(100000, 999999);
    $stmt = $con->prepare('INSERT INTO forget_password (customer_id, OTP) VALUES (:customer_id, :otp)');
    $stmt->bindParam(':otp', $OTP, PDO::PARAM_INT);
    $stmt->bindParam(':customer_id', $user['customer_id'], PDO::PARAM_INT);
    $stmt->execute();
    sendVerificationEmail($UserEmail, $user['first_name'], $user['last_name'], $OTP);
    exit();
}