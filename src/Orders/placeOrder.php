<?php
header('Content-Type: application/json');

include __DIR__ . '/../database/dbConnection.php';
include __DIR__ . '/../auth/JWTHandler.php';


$inputData = file_get_contents('php://input');

$Data = json_decode($inputData, true);

$JWT = $Data['token'];
$order_date = date("Y-m-d");
$address = $Data['address'] ?? '';
$payment_method = $Data['payment_method'] ?? '';
if (!empty($JWT)) {
    try {
        $handler = new JwtHandler();
        $decoded = $handler->verifyToken($JWT, $_ENV['JWT_SECRET']);
        $userData = JWT::decode($JWT, new Key($_ENV['JWT_SECRET'], 'HS256'));
        $customer_id = $userData->data->user_id;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit();
    }
}
if (!$address || !$payment_method) {
    http_response_code(400);
    echo json_encode(['error' => 'Data Is Required']);
    exit();
}

try {
    $stmt = $con->prepare('SELECT i.cart_item_id, i.product_id, i.quantity, p.new_price, p.stock 
    FROM cart_items i 
    JOIN products p ON i.product_id = p.product_id 
    WHERE i.cart_id = (SELECT cart_id FROM carts WHERE customer_id = :customer_id)');
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cartItems = [];
    foreach ($items as $item) {
        if ($item['stock'] < $item['quantity']) {
            http_response_code(400);
            echo json_encode(['error' => 'Not enough stock for item: ' . $item['product_id']]);
            exit();
        } else {
            $stmt = $con->prepare('UPDATE products SET stock = stock - :quantity WHERE product_id = :product_id');
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->execute();
        }
        $total_price = $item['new_price'] * $item['quantity'];
        $cartItems[] = [
            "product_id" => $item['product_id'],
            "quantity" => $item['quantity'],
            "total_price" => $total_price
        ];
    }
    echo json_encode(["Order Items" => $cartItems]);

    // Insert the order into the orders table
    $stmt = $con->prepare('INSERT INTO orders (customer_id, order_date, address, payment_method, total_price) VALUES (:customer_id, :order_date, :address, :payment_method, :total_price)');
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->bindParam(':order_date', $order_date);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':total_price', $total_price);
    $stmt->execute();
    // insert the order items
    foreach ($items as $item) {
    $stmt = $con->prepare('insert into order_items (`customer_id`, `product_id`, `quantity`) 
    values (:customer_id, :product_id, :quantity)');
    $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $item['product_id']);
    $stmt->bindParam(':quantity', $item['quantity']);
    $stmt->execute();
    }

    //remove datafrom cart
    $stmt = $con->prepare('delete from cart_items where cart_id = (select cart_id from carts where customer_id = :customer_id)');
    $stmt->bindParam('customer_id', $customer_id);
    $stmt->execute();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'database error']);
    exit();
}
