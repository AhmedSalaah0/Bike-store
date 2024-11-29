<?php
// dbConnection.php: include your database connection code here

include __DIR__ . '/../dbConnection.php';

// Check if the form data is valid
if (!isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['password2'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['Error' => 'Invalid request payload']);
    exit();
}

$username = htmlspecialchars(strip_tags($_POST['username']));
$email = htmlspecialchars(strip_tags($_POST['email']));
$password = htmlspecialchars(strip_tags($_POST['password']));
$password2 = htmlspecialchars(strip_tags($_POST['password2']));

if ($password !== $password2) {
    echo json_encode(['Error' => 'Passwords do not match']);
    exit();
}

try {
    // Check if email is already registered
    $stmt = $con->prepare("SELECT * FROM customers WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        http_response_code(409); // Conflict
        echo json_encode(['Error' => 'Email is already registered']);
        exit();
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user data
        $stmt = $con->prepare(
            "INSERT INTO customers (username, email, password) 
            VALUES (:username, :email, :password)"
        );
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);

        $stmt->execute();
        http_response_code(201); // Created
        echo json_encode(['Message' => 'Registration successful']);
    }
} catch (PDOException $ex) {
    // Handle error
    http_response_code(500); // Internal Server Error
    echo json_encode(['Error' => 'Database error: ' . $ex->getMessage()]);
}
?>
