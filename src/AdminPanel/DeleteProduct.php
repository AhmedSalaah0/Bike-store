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

include __DIR__ . '/../database/dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}
$data = json_decode(file_get_contents('php://input'), true);

$product_id = htmlspecialchars(strip_tags($data['product_id']));

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID not specified']);
    exit();
}

try {
    $stmt = $con->prepare('SELECT product_id FROM products WHERE product_id = :id');
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }
    $stmt = $con->prepare('DELETE FROM products WHERE product_id = :id');
    $stmt->bindParam(':id', $product_id);
    $stmt->execute();
    echo json_encode(['message' => 'Product deleted successfully']);
    exit();
} catch (PDOException $ex) {
    http_response_code(500);
    echo json_encode(['error' => 'database error']);
    exit();
}