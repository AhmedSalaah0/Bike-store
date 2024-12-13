<?php
header("Access-Control-Allow-Origin: http://localhost:5501");

require_once __DIR__ . '/../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler
{
    private $JWTsecret;
    private $refreshSecret;

    public function __construct()
    {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../../");
        $dotenv->load();
        $this->JWTsecret = $_ENV['JWT_SECRET'];
        $this->refreshSecret = $_ENV['refresh_token_secret'];
    }

    public function generateToken(array $data, int $expiration = 10, string $tokenType = 'access'): string
    {
        $issuedAt = time();
        $payload = [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiration,
            'iss' => $_SERVER['SERVER_NAME'],
            'data' => array_merge($data, ['token_type' => $tokenType])
        ];
        $secret = $tokenType === 'access' ? $this->JWTsecret : $this->refreshSecret;
        return JWT::encode($payload, $secret, 'HS256');
    }

    public function verifyToken(string $token, string $secret): object
    {
        return JWT::decode($token, new Key($secret, 'HS256'));
    }
}
