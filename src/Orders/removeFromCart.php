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
include __DIR__ . '/../auth/JWTHandler.php';

$inputData = file_get_contents('php://input');

$cartItemData = json_decode($inputData, true);

$cart_item_id = htmlspecialchars(strip_tags($cartItemData['cart_item_id'] ?? ''));

try {
    // query to delete the item from cart_items table
    $stmt = $con->prepare("DELETE FROM cart_items WHERE cart_item_id = :cart_item_id");
    $stmt->bindParam(":cart_item_id", $cart_item_id, PDO::PARAM_INT);
    $stmt->execute();
    // get the number of rows deleted by the query
    $rowCount = $stmt->rowCount();
    if ($rowCount > 0) {
        http_response_code(200);
        echo json_encode([
            "message" => "Product removed from cart successfully",
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "message" => "product is currently not in your cart to remove",
            "removed_item" => "none"
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "database error"]);
}