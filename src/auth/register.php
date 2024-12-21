<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

include __DIR__ . '/../database/dbConnection.php';


if ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
    $first_name = htmlspecialchars(strip_tags($_POST['first_name'] ?? ''));
    $last_name = htmlspecialchars(strip_tags($_POST['last_name'] ?? ''));
    $email = htmlspecialchars(strip_tags($_POST['email'] ?? ''));
    $password = htmlspecialchars(strip_tags($_POST['password'] ?? ''));
    $password2 = htmlspecialchars(strip_tags($_POST['password2'] ?? ''));
    $phone_number = htmlspecialchars(strip_tags($_POST['phone_number'] ?? ''));
}
    
else {
    $UserData = json_decode(file_get_contents('php://input'), true);

    $first_name = htmlspecialchars(strip_tags($UserData['first_name'] ?? ''));
    $last_name = htmlspecialchars(strip_tags($UserData['last_name'] ?? ''));
    $email = htmlspecialchars(strip_tags($UserData['email'] ?? ''));
    $password = htmlspecialchars(strip_tags($UserData['password'] ?? ''));
    $password2 = htmlspecialchars(strip_tags($UserData['password2'] ?? ''));
    $phone_number = htmlspecialchars(strip_tags($UserData['phone_number'] ?? ''));
}

if (!$first_name || !$last_name || !$email || !$password || !$phone_number) {
    echo json_encode(['error' => 'Invalid Data']);
    http_response_code(400);
    exit();
}

if (!preg_match('/^[0-9]{11}$/', $phone_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid Phone Number']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid Email Format']);
    http_response_code(400);
    exit();
}

if ($password != $password2) {
    echo json_encode(['error' => 'Passwords Do Not Match']);
    http_response_code(400);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    http_response_code(400);
    exit(); 
}



try {
    $stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['error' => 'Email Is Already Registered']);
        http_response_code(409);
        exit();
    } else {
        include __DIR__ .'/generateToken.php';
        $token = $verification_token;
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $con->prepare("INSERT INTO customers (first_name, last_name, email, password, phone_number, verification_token) 
        VALUES (:first_name,:last_name, :email, :password, :phone_number, :verification_token)");
        $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
        $stmt->bindParam(':verification_token', $token, PDO::PARAM_STR);
        
        require __DIR__ . '/../smtp/sendEmail.php';
        sendVerificationEmail($email, $first_name, $last_name, $verification_link);
        $stmt->execute();

        http_response_code(201);
        echo json_encode(["message" => "Registration Successful!", "user" => ["name" => $first_name . " " . $last_name]]);
        exit();
    }
} catch (PDOException $EX) {
    http_response_code(500);
    echo json_encode(['error' => 'database error']);
    exit();
}