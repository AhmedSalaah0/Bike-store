<?php
include "../dbConnection.php";

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $con->prepare("SELECT * from customers where email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            echo json_encode(["User Id" => $user['customer_id']]);
            exit;
        
        
        } else {

            http_response_code(404);

            echo json_encode(["error" => "User Not Found"]);
        
        }
    } catch (PDOException $ex) {
        echo $ex->getMessage();
    }
} else {
    echo json_encode(["error" => "Email or password not provided"]);
}
?>
