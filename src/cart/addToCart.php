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
$quantity = htmlspecialchars(strip_tags($userData['quantity'] ?? ''));

// selecting all data from cart table and fetch it in $cart variable
$stmt = $con->prepare("SELECT * FROM cart WHERE customer_id = :customer_id");
$stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
$stmt->execute();

$cart = $stmt->fetch(PDO::FETCH_ASSOC);
$cartId = $cart['cart_id'];

// check if the customer has no cart will create one and reassign the $cartId variable to the lastInsertId() hence the newly created cart_id
if (empty($cart)) {
    $currentDate = date("Y-m-d");
    $stmt = $con->prepare("INSERT INTO cart (customer_id, created_at) VALUES (:customer_id, :created_at)");
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':created_at', $currentDate, PDO::PARAM_STR);
    $stmt->execute();

    $cartId = $con->lastInsertId();
    echo json_encode(["message" => "a new cart was created"]);
}

// if the available quantity is enough return http_response_code(200) else return the available quantity with an message to clearfy
$stmt = $con->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cart_id, :product_id, :quantity)");
$stmt->bindParam(":cart_id", $cartId, PDO::PARAM_INT);
$stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
$stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
$stmt->execute();