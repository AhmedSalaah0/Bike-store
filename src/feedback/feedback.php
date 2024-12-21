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
$name = htmlspecialchars(strip_tags($data['name']));
$personal_interest = htmlspecialchars(strip_tags($data['personal_interest']));
$comment = htmlspecialchars(strip_tags($data['comment']));
$rating = htmlspecialchars(strip_tags($data['rating']));

try {
    $stmt = $con->prepare("INSERT INTO feedback (customer_id, name, personal_interest, comment, rating) 
                                  VALUES (:customer_id, :name, :personal_interest, :comment, :rating)");

    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(":name", $name, PDO::PARAM_STR);
    $stmt->bindParam(":personal_interest", $personal_interest, PDO::PARAM_STR);
    $stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
    $stmt->bindParam(":rating", $rating, PDO::PARAM_INT);

    $stmt->execute();
    
    http_response_code(200);
    echo json_encode(["status"=> "success","message"=> "thanks, your time is appreciated"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error"=> "database error"]);
}