<?php
header("Access-Control-Allow-Origin: http://localhost:5501");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/../database/dbConnection.php";
include __DIR__ . '/../auth/JWTHandler.php';

$inputData = file_get_contents('php://input');

$userData = json_decode($inputData, true);
$JWT = $userData['token'] ?? '';

if (!empty($JWT)) {
    try {
        $handler = new JwtHandler();
        $decoded = $handler->verifyToken($JWT, $_ENV['JWT_SECRET']);
        $customer_id = $decoded->data->user_id;
        if ($decoded->data->is_admin == true) {
            http_response_code(404);
            echo json_encode(['error' => 'User Is Administrator']);
            exit();
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}

try {
    $stmt = $con->prepare("SELECT cart_id FROM carts WHERE customer_id = :customer_id");
    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $Data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$Data) {
        http_response_code(404);
        echo json_encode(["error" => "No cart found for this customer. $customer_id"]);
        exit();
    }
    $cart_id = $Data['cart_id'];

    $stmt = $con->prepare("SELECT c.cart_item_id, c.product_id, p.product_name, 
                                    p.image, p.new_price,c.quantity FROM cart_items c 
                                    JOIN products p on c.product_id = p.product_id
                                    where cart_id = :cart_id");
    $stmt->bindParam('cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // fetching a column using array_column method to return all products and its quantity in cart
    $cartItems = array_map(function ($row) {
        return [
            "cart_item_id" => $row['cart_item_id'],
            "product_id" => $row['product_id'],
            'product_name' => $row['product_name'],
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
    // http_response_code(500);
    echo json_encode(["error" => "database error"]);
}