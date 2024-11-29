<?php
include __DIR__ . '/../dbConnection.php';

$name = htmlspecialchars(strip_tags($_POST['name']));
$email = htmlspecialchars(strip_tags($_POST['email']));
$password = htmlspecialchars(strip_tags($_POST['password']));
$phone_number = htmlspecialchars(strip_tags($_POST['phone_number']));

try{
    
    $stmt = $con->prepare("SELECT * from customers where email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR); 

    $stmt->execute();

    $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    if ($user)
    {
        http_response_code(404);
        echo json_encode(['Error' => 'Email Is Registered']);
        exit();
    }

    else
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $con->prepare(
            "INSERT INTO customers (name, email, password, phone_number) 
            VALUES (:name, :email, :password, :phone_number)"
        );
        
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);

        $stmt->execute();
        echo "Registration Successful!";
        exit();
    
    }
}catch(PDOException $EX)
{
    echo $EX->getMessage();
}