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

include __DIR__ . '/../auth/JWTHandler.php';
include __DIR__ . "/../database/dbConnection.php";

$inputData = file_get_contents('php://input');

$userData = json_decode($inputData, true);

$JWT = $userData['token'] ?? '';
$product_id = htmlspecialchars(strip_tags($userData['product_id'] ?? ''));
$quantity = htmlspecialchars(strip_tags($userData['quantity'] ?? ''));
if (!$product_id)
{
    http_response_code(400);
    echo json_encode(['error' => 'Product_id Is Required']);
    exit();
}
if ($quantity <= 0)
{
    http_response_code(400);
    echo json_encode(['error' => 'Quantity should be greater than 0']);
    exit();
}

if (!empty($JWT)) {
    try {
        $handler = new JwtHandler();
        $decoded = $handler->verifyToken($JWT);
        $userData = JWT::decode($JWT, new Key($_ENV['JWT_SECRET'], 'HS256'));
        $user_id = $userData->data->user_id;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error'=> $e->getMessage()]);
            exit();
        }
}
$cartId = -1;
try {
    // selecting cart_id from cart table and fetch it in $cart variable
    $stmt = $con->prepare("SELECT cart_id FROM carts WHERE customer_id = :user_id");
    $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartId = $cart['cart_id'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error: " . $e->getMessage()]);
}

// check if the customer has no cart will create one and reassign the $cartId variable to the lastInsertId()
// which is the newly created cart_id
if (empty($cart)) {
    $currentDate = date("Y-m-d");
    try {
        $stmt = $con->prepare("INSERT INTO cart (customer_id, created_at) VALUES (:user_id, :created_at)");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $currentDate, PDO::PARAM_STR);
        $stmt->execute();
        $cartId = $con->lastInsertId();
        echo json_encode(["message" => "a new cart was created"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "database error: " . $e->getMessage()]);
    }
}

try {
    // query to get the current quantity (stock) of the product
    $stmt = $con->prepare("SELECT stock FROM products WHERE product_id = :product_id");
    $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $stockData = $stmt->fetch(PDO::FETCH_ASSOC);
    $stock = $stockData['stock'];
    // if the quantity is enough just add it to the database
    if ($stock >= $quantity) {
        //check if product already exists in cart
        $stmt = $con->prepare("SELECT quantity FROM cart_items WHERE cart_id = :cart_id AND product_id = :product_id");
        $stmt->bindParam(":cart_id", $cartId, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row && $row['quantity'] > 0)
        {
            $newQuantity = $row['quantity'] + $quantity;
            if ($newQuantity > $stock)
            {
                http_response_code(400);
                echo json_encode(["message" => "quantity exceeds stock"]);
                exit();
            }
            $stmt = $con->prepare("UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cart_id AND product_id = :product_id");
            $stmt->bindParam(":quantity", $newQuantity, PDO::PARAM_INT);
            $stmt->bindParam(":cart_id", $cartId, PDO::PARAM_INT);
            $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["message" => "product quantity updated in cart successfully"]);
        } else {
        


        $stmt = $con->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
        $stmt->bindParam(":cart_id", $cartId, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(["message" => "product added to cart successfully"]);
        }
    } else {
        // check if it's out of stock or there's some of the product left
        switch ($stock) {
            case 0:
                echo json_encode(["message" => "product is out of stock currently"]);
                break;
            default:
                echo json_encode([
                    "message" => "only available quantity of 'product' is $stock",
                    "available_quantity" => $stock
                ]);
                break;
        }
    }
    http_response_code(200);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error: " . $e->getMessage()]);
}