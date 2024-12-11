<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
require_once __DIR__ . '/../../vendor/autoload.php';

class JwtHandler
{
    private $secretKey;

    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->load();

        $this->secretKey = $_ENV['JWT_SECRET'];
    }

    public function generateToken(array $data, int $expiration = 3600): string
    {
        $issuedAt = time();
        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiration,
            'iss' => $_SERVER['SERVER_NAME'],
            'data' => $data
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }


    public function verifyToken(string $token): object
    {
        return JWT::decode($token, new Key($this->secretKey, 'HS256'));
    }
}