<?php
include 'dbConnection.php';

$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $con->prepare("SELECT * FROM customers WHERE verification_token = :token");
    $stmt->bindParam(':token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $stmt = $con->prepare("UPDATE customers SET is_verified = 1, verification_token = NULL WHERE verification_token = :token");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        echo "Your email has been verified. You can now log in.";
    } else {
        echo "Invalid or expired verification link.";
    }
} else {
    echo "No token provided.";
}
