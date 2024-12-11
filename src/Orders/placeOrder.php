<?php
header('Content-Type: application/json');

include __DIR__ . '/../database/dbConnection.php';
include __DIR__ . '/../auth/JWTHandler.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
    $stmt = $con->prepare('SELECT i.cart_item_id, i.product_id, i.quantity, p.new_price, p.stock 
    FROM cart_items i 
    JOIN products p ON i.product_id = p.product_id 
    WHERE i.cart_id = (SELECT cart_id FROM carts WHERE customer_id = :customer_id)');
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cartItems = [];
    foreach ($items as $item) {
        if ($item['stock'] < $item['quantity']) {
            http_response_code(400);
            echo json_encode(['error'=> 'Not enough stock for item: '. $item['product_id']]);
            exit();
        } else {
            $stmt = $con->prepare('UPDATE products SET stock = stock - :quantity WHERE product_id = :product_id');
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->execute();
        }
        $total = $item['new_price'] * $item['quantity'];
        $cartItems[] = [
            "product_id" => $item['product_id'],
            "quantity" => $item['quantity'],
            "total price" => $total 
        ];
    }

    echo json_encode(["Order Items" => $cartItems]);
    $stmt = $con-> prepare("insert into O");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=> $e->getMessage()]);
    exit();
}
