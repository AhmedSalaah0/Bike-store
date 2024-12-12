<?php
include __DIR__ . '/../database/dbConnection.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $category = $_GET['category'];
        if ($category === "all")
        {
            $stmt = $con->prepare("SELECT * FROM products");
            $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        else
        {
        $stmt = $con->prepare("SELECT * FROM products where category = :category");
        $stmt->bindParam("category", $category, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $products = array_map(function($result) {
            return [
                "product_id" => $result['product_id'],
                'prodict_name'=> $result['product_name'],
                "category" => $result["category"],
                "old_price" => $result["old_price"],
                "new_price"=> $result["new_price"],
                "details" => $result["details"],
                "description"=> $result["description"],
                "image"=> $result["image"],
                "stock" => $result['stock'],
            ];
        }, $result);
        echo json_encode([
            'products'=> $products,
        ]);
        http_response_code(200);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}