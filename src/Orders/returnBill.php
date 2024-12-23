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
include __DIR__ . "/../auth/JWTHandler.php";

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

// get cart_id of the customer
try {
    $stmt = $con->prepare("SELECT cart_id FROM carts WHERE customer_id = :customer_id");
    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$data) {
        http_response_code(404);
        echo json_encode(["error" => "No cart found for this customer"]);
        exit();
    }
    $cart_id = $data['cart_id'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'database error: ' . $e->getMessage()]);
    exit();
}

try {
    // get new_price, quantity of each product in the cart
    $stmt = $con->prepare("SELECT p.new_price, c.quantity
                                  FROM cart_items c JOIN products p
                                  ON c.product_id = p.product_id
                                  WHERE cart_id = :cart_id");
    $stmt->bindParam('cart_id', $cart_id, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;
    foreach ($rows as $row)
        $total += $row['new_price'] * $row['quantity'];
    
    http_response_code(200);
    echo json_encode([
        "Subtotal" => $total,
        "Shipping" => 50,
        "Total" => $total + 50
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'database error: ' . $e->getMessage()]);
    exit();
}