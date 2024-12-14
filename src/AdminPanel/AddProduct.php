<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . '/../database/dbConnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve input data
    $product_name = htmlspecialchars(strip_tags($_POST['product_name'] ?? ''));
    $catagory = htmlspecialchars(strip_tags($_POST['category'] ?? ''));
    $old_price = htmlspecialchars(strip_tags($_POST['old_price'] ?? ''));
    $new_price = htmlspecialchars(strip_tags($_POST['new_price'] ?? ''));
    $details = htmlspecialchars(strip_tags($_POST['details'] ?? ''));
    $description = htmlspecialchars(strip_tags($_POST['description'] ?? ''));
    $image = $_FILES['image'] ?? null;

    // Validate required fields
    if (!$product_name || !$catagory || !$old_price || !$new_price || !$details || !$description) {
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

        $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $targetFileName = uniqid() . '.' . $fileExtension;
        $targetFilePath = "$uploadDir$targetFileName";

        if (!move_uploaded_file($image['tmp_name'], $targetFilePath)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file']);
            exit();
        }
    }
    else{
        http_response_code(400);
        echo json_encode(['error'=> '!!! Image Is Needed']);
        exit();
    }
        try {
            $stmt = $con->prepare("INSERT INTO `products` (`product_name`, `category`,
            `old_price`, `new_price`, `details`, `description`, `image`)
            values (:product_name, :catagory, :old_price,
            :new_price, :details, :description, :image)");
            $stmt->bindParam(':product_name', $product_name, PDO::PARAM_STR);
            $stmt->bindParam(':catagory', $catagory, PDO::PARAM_STR);
            $stmt->bindParam(':old_price', $old_price, PDO::PARAM_INT);
            $stmt->bindParam(':new_price', $new_price, PDO::PARAM_INT);
            $stmt->bindParam(':details', $details , PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam('image', $targetFileName, PDO::PARAM_STR);
            $stmt->execute();

            http_response_code(201);
            echo json_encode(['message' => 'Product created successfully']);
            exit();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
            exit();
        }
}

