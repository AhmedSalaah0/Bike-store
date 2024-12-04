<?php
include "dbConnection.php";

$base_path = '/restApi';

$full_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = str_replace($base_path, '', $full_uri);
$request = strtolower(trim($request, '/'));

$allowed_routes = [
    '' => '/home.php',
    'index' => '/home.php',
    'home' => '/home.php',
    'auth/register' => '/auth/register.php',
    'auth/login' => '/auth/login.php',
    'smtp/sendEmail' => '/smtp/sendEmail.php',
    'auth/verfiy' => '/auth/verfiy.php',
    'smtp/sendemail' => '/smtp/sendEmail.php',
    'auth/verify' => '/auth/verify.php',
    'auth/forgetpassword' => '/auth/forgetpassword.php',
    'auth/verifyOTP' => '/auth/verifyOTP.php',
    'auth/verifyotp' => '/auth/verifyOTP.php',
    'auth/resetNewPassword' => '/auth/resetNewPassword.php',
    'auth/resetnewpassword'=> '/auth/resetNewPassword.php',
];


if (array_key_exists($request, $allowed_routes)) {
    require __DIR__ . $allowed_routes[$request];
} else {
    http_response_code(404);
    echo "404 Error";
}