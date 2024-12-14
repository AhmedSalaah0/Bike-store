<?php
header("Access-Control-Allow-Origin: http://localhost:5501");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, Category");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
include __DIR__ . '/../database/dbConnection.php';


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $category = $_GET['category'];
        if ($category === "all") {
            $stmt = $con->prepare("SELECT * FROM products");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $con->prepare("SELECT * FROM products WHERE category = :category");
            $stmt->bindParam("category", $category, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $products = array_map(function ($result) {
            return [
                "product_id" => $result['product_id'],
                'product_name' => $result['product_name'],
                "category" => $result["category"],
                "old_price" => $result["old_price"],
                "new_price" => $result["new_price"],
                "details" => $result["details"],
                "description" => $result["description"],
                "image" => 'http://localhost/bike-store/src/uploads/' . $result["image"],
                "stock" => $result['stock'],
            ];
        }, $result);
        echo json_encode([
            'products' => $products,
        ]);
        http_response_code(200);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'database error']);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}