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

$userData = json_decode($inputData, true);

$customer_id = htmlspecialchars(strip_tags($userData['customer_id'] ?? ''));
$cart_id = htmlspecialchars(strip_tags($userData['cart_id'] ?? ''));

try {
    $stmt = $con->prepare("SELECT product_id, quantity FROM cart_items WHERE cart_id = :cart_id");
    $stmt->bindParam('cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // fetching a column using array_column method to return all products and its quantity in cart
    $products_id = array_column($rows, 'product_id');
    $products_quantity = array_column($rows, 'quantity');
    http_response_code(200);
    echo json_encode(
        [
            "cart_products" => $products_id,
            "products_quantity" => $products_quantity
        ]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error: " . $e->getMessage()]);
}