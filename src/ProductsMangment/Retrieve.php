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
        $stmt = $con->prepare("SELECT * FROM products where category = :category");
        $stmt->bindParam("category", $category, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($result);
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