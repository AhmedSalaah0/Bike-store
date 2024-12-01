<?php
include __DIR__ . '/../dbConnection.php';

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 
header("Content-Type: application/json"); 

if (isset($_POST['first_name']) || isset($_POST['last_name'])|| isset($_POST['email']) || isset($_POST['password']) || 
isset($_POST['password2']) || isset($_POST['phone_number'])) {
    $first_name = htmlspecialchars(strip_tags($_POST['first_name']));
    $last_name = htmlspecialchars(strip_tags($_POST['last_name']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $password = htmlspecialchars(strip_tags($_POST['password']));
    $password2 = htmlspecialchars(strip_tags($_POST['password2']));
    $phone_number = htmlspecialchars(strip_tags($_POST['phone_number']));
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid Data"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Email Format']);
    exit();
}

if ($password != $password2) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords Do Not Match']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    exit();
}

// if (!preg_match('/^[0-9]{10}$/', $phone_number)) {
//     http_response_code(400);
//     echo json_encode(['error' => 'Invalid Phone Number']);
//     exit();
// }

try {
    $stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR); 
    $stmt->execute();

    $user = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($user) {
        http_response_code(409); 
        echo json_encode(['error' => 'Email Is Already Registered']);
        exit();
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("INSERT INTO customers (first_name, last_name, email, password, phone_number) VALUES (:first_name,:last_name, :email, :password, :phone_number)");
        $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);

        $stmt->execute();

        http_response_code(201); 
        echo json_encode(["message" => "Registration Successful!", "user" => ["name" => $first_name . $last_name]]);
        exit();
    }
} catch (PDOException $EX) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $EX->getMessage()]);
    exit();
}
