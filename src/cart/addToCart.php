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
$product_id = htmlspecialchars(strip_tags($userData['product_id'] ?? ''));
$product_name = htmlspecialchars(strip_tags($userData['product_name'] ?? ''));
$quantity = htmlspecialchars(strip_tags($userData['quantity'] ?? ''));

$cartId = -1;
try {
    // selecting cart_id from cart table and fetch it in $cart variable
    $stmt = $con->prepare("SELECT cart_id FROM cart WHERE customer_id = :customer_id");
    $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart = $stmt->fetch(PDO::FETCH_ASSOC);
    $cartId = $cart['cart_id'];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error" . $e->getMessage()]);
}

// check if the customer has no cart will create one and reassign the $cartId variable to the lastInsertId()
// which is the newly created cart_id
if (empty($cart)) {
    $currentDate = date("Y-m-d");
    try {
        $stmt = $con->prepare("INSERT INTO cart (customer_id, created_at) VALUES (:customer_id, :created_at)");
        $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(':created_at', $currentDate, PDO::PARAM_STR);
        $stmt->execute();
        $cartId = $con->lastInsertId();
        echo json_encode(["message" => "a new cart was created"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "database error" . $e->getMessage()]);
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
        $stmt = $con->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
        $stmt->bindParam(":cart_id", $cartId, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(["message" => "$product_name added to cart successfully"]);
    } else {
        // check if it's out of stock or there's some of the product left
        if ($stock == 0)
            echo json_encode(["message" => "$product_name is out of stock currently"]);
        else {
            echo json_encode([
                "message" => "only available quantity of '$product_name' is $stock",
                "available_quantity" => $stock
            ]);
        }
    }
    http_response_code(200);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error" . $e->getMessage()]);
}