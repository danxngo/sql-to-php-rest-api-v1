<?php

namespace Workstation\PhpApi;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthManager
{
    private const TOKEN_EXPIRATION = 3600; // Token expiration time in seconds
    private const TOKEN_ALGORITHM = 'HS256'; // Token signing algorithm

    private string $secretKey;
    private Database $database;

    public function __construct(string $secretKey, Database $database)
    {
        $this->secretKey = $secretKey;
        $this->database = $database;
    }

    public function generateToken(array $payload): string
    {
        $payload['exp'] = time() + self::TOKEN_EXPIRATION;
        return JWT::encode($payload, $this->secretKey, self::TOKEN_ALGORITHM);
    }

    public function verifyToken(string $token): bool
    {
        try {
            $decodedToken =  JWT::decode($token, new Key($this->secretKey, self::TOKEN_ALGORITHM));
            return isset($decodedToken->exp) && $decodedToken->exp >= time();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function login(string $email, string $password): ?string
    {
        $user = $this->database->get('user', ['email' => $email]);
        if ($user && password_verify($password, $user[0]['password'])) {
            $tokenPayload = ['user_id' => $user[0]['id']];
            return $this->generateToken($tokenPayload);
        }
        return null;
    }

    public function signup(string $email, string $password, string $name): ?string
    {
        if (!$this->database->get('user', ['email' => $email])) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user = ['email' => $email, 'password' => $hashedPassword, 'name' => $name];
            $this->database->insert('user', $user);
        }
        return $this->login($email, $password);
    }

    public function forgotPassword(string $email): bool
    {
        // Implementation for password recovery logic
        return true;
    }
}
