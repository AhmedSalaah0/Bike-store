<?php
include __DIR__ . "/../dbConnection.php";

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type, Authorization"); 
header("Content-Type: application/json"); 

if ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') 
{
    $email = htmlspecialchars(strip_tags($_POST['email'] ?? ''));
    $password = htmlspecialchars(strip_tags($_POST['password'] ?? ''));
}

else {
$userData = json_decode(file_get_contents('php://input'), true);

if (!$userData)
{
    throw new Exception("Data Error");
}

$email = htmlspecialchars(strip_tags($userData['email'] ?? ''));
$password = htmlspecialchars(strip_tags($userData['password'] ?? ''));

}
if (!$email || !$password)
{
    
    throw new Exception('All field are required');
}

    try {
        $stmt = $con->prepare("SELECT * from customers where email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode(["User Id" => $user['customer_id']]);
            exit;        
        } 
        
        else {
            http_response_code(404);
            echo json_encode(["error" => "User Not Found"]);
        
        }
    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }

