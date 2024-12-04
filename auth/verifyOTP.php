<?php

include __DIR__ . '/../dbConnection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");


$OTPData = json_decode(file_get_contents('php://input'), true);

$OTP = $OTPData['OTP'] ?? '';

if ($OTP) {
    $stmt = $con->prepare("SELECT * FROM forget_password WHERE OTP = :otp");
    $stmt->bindParam(':otp', $OTP, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(["user_id" => $user['customer_id']]);
        exit();

    } else {
        echo "Invalid or expired OTP.";
        exit();
    }
} else {
    echo "No token provided.";
    exit();
}
