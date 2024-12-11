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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include __DIR__ . "/../database/dbConnection.php";
include __DIR__ . '/../auth/JWTHandler.php';

$inputData = file_get_contents('php://input');

$userData = json_decode($inputData, true);
$JWT = $userData['token'];

if (!empty($JWT)) {
    try {
        $handler = new JwtHandler();
        $decoded = $handler->verifyToken($JWT);
        $userData = JWT::decode($JWT, new Key($_ENV['JWT_SECRET'], 'HS256'));
        $customer_id = $userData->data->user_id;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error'=> $e->getMessage()]);
            exit();
        }
}

try {
    $stmt = $con->prepare("Select cart_id from carts where customer_id = :customer_id");
    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $Data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$Data) {
        http_response_code(404);
        echo json_encode(["error" => "No cart found for this customer. $customer_id"]);
        exit();
    }
$cart_id = $Data['cart_id'];

    $stmt = $con->prepare("SELECT c.product_id, p.product_name, 
                                    p.image, p.new_price,c.quantity FROM cart_items c 
                                    join products p on c.product_id = p.product_id
                                    where cart_id = :cart_id");
    $stmt->bindParam('cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // fetching a column using array_column method to return all products and its quantity in cart
    $cartItems = array_map(function($row) {
        return [
            "product_id" => $row['product_id'],
            'prodict_name'=> $row['product_name'],
            "quantity" => $row['quantity'],
            "new_price" => $row['new_price'],
            "image" => $row['image'],
        ];
    }, $rows);
    
    http_response_code(200);
    echo json_encode([
        "cart_items" => $cartItems
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error: " . $e->getMessage()]);
}