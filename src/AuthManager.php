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
            if ($decodedToken && isset($decodedToken->user_id)) {
                $databaseUser = $this->database->get('user', ['id' => $decodedToken->user_id]);
                if ($databaseUser && $databaseUser[0]['token'] === $token) {
                    return isset($decodedToken->exp) && $decodedToken->exp >= time();
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function login(string $email, string $password): ?string
    {
        $user = $this->database->get('user', ['email' => $email]);
        if ($user && password_verify($password, $user[0]['password'])) {
            $tokenPayload = ['user_id' => $user[0]['id']];
            $token = $this->generateToken($tokenPayload);
            $this->database->update('user', $user[0]['id'], ['token'=> $token]);
            return $token;
        }
        return null;
    }

    public function signup(string $email, string $password, string $name): ?string
    {

        if (!$this->database->get('user', ['email' => $email])) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user = ['email' => $email, 'password' => $hashedPassword, 'name' => $name, 'token' => ''];
            $this->database->insert('user', $user);
        }
        return $this->login($email, $password);
    }

    public function logout(string $token): bool
    {
        $decodedToken =  JWT::decode($token, new Key($this->secretKey, self::TOKEN_ALGORITHM));
        if ($decodedToken && isset($decodedToken->user_id)) {
            $user_id = $decodedToken->user_id;
            return $this->database->update('user', $user_id, ['token'=> '']);
        }
        return false;
    }

    public function forgotPassword(string $email): bool
    {
        // Implementation for password recovery logic
        return true;
    }
}
