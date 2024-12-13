<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../database/dbConnection.php';


$inputData = json_decode(file_get_contents('php://input'), true);
$product_id = htmlspecialchars(strip_tags($inputData['product_id']));
if (!$inputData) {
    http_response_code(400);
    echo json_encode(['error' => 'Product_id Is Required']);
    exit();
}

try {
    $stmt = $con->prepare('SELECT * FROM products WHERE product_id = :product_id');
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        // http_response_code(400);
        echo json_encode(['error' => 'Wrong Product_id']);
        exit();
    }
    echo json_encode($result);
    http_response_code(200);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'database error']);
    exit();
}