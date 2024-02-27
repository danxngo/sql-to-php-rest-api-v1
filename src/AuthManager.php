<?php

namespace Workstation\PhpApi;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthManager
{
    private string $secretKey;
    private Database $database;

    public function __construct(string $secretKey, Database $database)
    {
        $this->secretKey = $secretKey;
        $this->database = $database;
    }

    public function generateToken(array $payload, int $expiration = 3600): string
    {
        $payload['exp'] = time() + $expiration;
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function verifyToken(string $token): bool
    {
        $decodedToken = $this->decodeToken($token);
        if (!$decodedToken) {
            return false;
        }
        if (isset($decodedToken['exp']) && $decodedToken['exp'] < time()) {
            return false;
        }
        return true;
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
        if(!$this->database->get('user', ['email' => $email])) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user = ['email' => $email, 'password' => $hashedPassword, 'name' => $name];
            $this->database->insert('user', $user);
        }
        return $this->login($email, $password);
    }

    public function forgotPassword(string $email): bool
    {
        return true;
    }
}
