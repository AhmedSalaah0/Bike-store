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

if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
    $Data = json_decode(file_get_contents('php://input'), true);

    if (!$Data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data format']);
        exit();
    }
    // $product_id = htmlspecialchars(strip_tags($Data['product_id'] ?? ''));
    // $product_name = htmlspecialchars(strip_tags($Data['product_name'] ?? ''));
    // $type = htmlspecialchars(strip_tags($Data['type'] ?? ''));
    // $old_price = htmlspecialchars(strip_tags($Data['old_price'] ?? ''));
    // $new_price = htmlspecialchars(strip_tags($Data['new_price'] ?? ''));
    // $details = htmlspecialchars(strip_tags($Data['details'] ?? ''));
    // $description = htmlspecialchars(strip_tags($Data['description'] ?? ''));
    // $image = $Data['image'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve input data
    $product_id = htmlspecialchars(strip_tags($_POST['product_id'] ?? ''));
    $product_name = htmlspecialchars(strip_tags($_POST['product_name'] ?? ''));
    $category = htmlspecialchars(strip_tags($_POST['category'] ?? ''));
    $old_price = htmlspecialchars(strip_tags($_POST['old_price'] ?? ''));
    $new_price = htmlspecialchars(strip_tags($_POST['new_price'] ?? ''));
    $details = htmlspecialchars(strip_tags($_POST['details'] ?? ''));
    $description = htmlspecialchars(strip_tags($_POST['description'] ?? ''));
    $image = $_FILES['image'] ?? null;

    // Validate required fields
    if (!$product_name || !$product_id || !$category || !$old_price || !$new_price || !$details || !$description) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required']);
        exit();
    }


    if ($image) {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxImageSize = 1024 * 1024 * 5; // 5MB
        $uploadDir = __DIR__ . '/../uploads/';


        if ($image['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'File upload error']);
            exit();
        }


        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $image['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid image format']);
            exit();
        }


        if ($image['size'] > $maxImageSize) {
            http_response_code(400);
            echo json_encode(['error' => 'Image size exceeds the maximum limit']);
            exit();
        }
    }

    try {
        // Check if product exists
        $stmt = $con->prepare('SELECT * FROM products WHERE product_id = :product_id');
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            exit();
        }

        if ($product['image'])
        {
            // Delete existing image
            unlink( $uploadDir . $product['image']);
            // Generate a unique file name and move the file
            $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $targetFileName = uniqid() . '.' . $fileExtension;
            $targetFilePath = "$uploadDir$targetFileName";

            if (!move_uploaded_file($image['tmp_name'], $targetFilePath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save uploaded file']);
                exit();
            }
        }

        // Update product details
        $stmt = $con->prepare(
            'UPDATE products 
            SET product_name = :product_name, 
                category = :category, 
                old_price = :old_price, 
                new_price = :new_price, 
                details = :details, 
                description = :description, 
                image = :image 
            WHERE product_id = :product_id'
        );

        $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':old_price', $old_price, PDO::PARAM_STR);
        $stmt->bindParam(':new_price', $new_price, PDO::PARAM_STR);
        $stmt->bindParam(':details', $details, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':image', $targetFileName, PDO::PARAM_STR);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Success response
        echo json_encode(['message' => 'Product updated successfully']);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'database error']);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit();
}