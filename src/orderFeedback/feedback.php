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

$inputData = file_get_contents('php://input');

$data = json_decode($inputData, true);

$customer_id = htmlspecialchars(strip_tags($data['customer_id']));
$product_id = htmlspecialchars(strip_tags($data['product_id']));
$quality_rate = htmlspecialchars(strip_tags($data['quality_rate']));
$fair_price = htmlspecialchars(strip_tags($data['fair_price']));
$smooth_purchase = htmlspecialchars(strip_tags($data['smooth_purchase']));
$purchase_problems = htmlspecialchars(strip_tags($data['purchase_problems']));
$smooth_delivery = htmlspecialchars(strip_tags($data['smooth_delivery']));
$delivery_problems = htmlspecialchars(strip_tags($data['delivery_problems']));
$recommend_rate = htmlspecialchars(strip_tags($data['recommend_rate']));
$message = htmlspecialchars(strip_tags($data['message']));

try {
    $stmt = $con->prepare("INSERT INTO order_feedback (customer_id, product_id, quality_rate, fair_price, smooth_purchase, purchase_problems, smooth_delivery, delivery_problems, recommend_rate, message) 
                              VALUES (:customer_id, :product_id, :quality_rate, :fair_price, :smooth_purchase, :purchase_problems, :smooth_delivery, :delivery_problems, :recommend_rate, :message)");

    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
    $stmt->bindParam(":quality_rate", $quality_rate, PDO::PARAM_INT);
    $stmt->bindParam(":fair_price", $fair_price, PDO::PARAM_STR);
    $stmt->bindParam(":smooth_purchase", $smooth_purchase, PDO::PARAM_STR);
    $stmt->bindParam(":purchase_problems", $purchase_problems, PDO::PARAM_STR);
    $stmt->bindParam(":smooth_delivery", $smooth_delivery, PDO::PARAM_STR);
    $stmt->bindParam(":delivery_problems", $delivery_problems, PDO::PARAM_STR);
    $stmt->bindParam(":recommend_rate", $recommend_rate, PDO::PARAM_INT);
    $stmt->bindParam(":message", $message, PDO::PARAM_STR);

    $stmt->execute();
    
    http_response_code(200);
    echo json_encode(["status"=> "success","message"=> "thanks, your time is appreciated"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error"=> "database error: " . $e->getMessage()]);
}