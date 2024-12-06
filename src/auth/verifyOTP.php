<?php

include __DIR__ . '/../database/dbConnection.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $OTPData = json_decode(file_get_contents('php://input'), true);

    $OTP = $OTPData['OTP'] ?? '';
$email = $OTPData['email'] ?? '';

if ($OTP && $email) {
    $stmt = $con->prepare("SELECT customer_id FROM customers WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $customer_id = $user['customer_id'];
        $stmt = $con->prepare("SELECT OTP FROM forget_password where customer_id = :id ORDER BY transaction_id DESC LIMIT 1;");
        $stmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();
        $otpData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($otpData && strval($otpData['OTP']) === strval($OTP)) {
            http_response_code(200);
            echo json_encode(["user_id" => $customer_id]);
            $stmt = $con->prepare("Delete from forget_password WHERE customer_id = :id");
            $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);
            $stmt->execute();        
            exit();
        }
    }
    http_response_code(400);
    echo json_encode(["error" => "Invalid or expired OTP. ". $otpData['OTP']]);
} else {
    echo json_encode(["error" => "No token provided."]);
}
exit();

}