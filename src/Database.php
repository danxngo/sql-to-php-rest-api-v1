<?php

namespace Workstation\PhpApi;

use PDO;
use PDOException;

class Database
{
    private $pdo;

    public function __construct(string $dsn, string $username, string $password, array $options = [])
    {
        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function getAll(string $table): array
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM $table");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return [];
        }
    }

    public function getById(string $table, int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return null;
        }
    }

    public function insert(string $table, array $data): bool
    {
        try {
            $keys = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO $table ($keys) VALUES ($values)";
            $stmt = $this->pdo->prepare($sql);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return false; 
        }
    }

    public function update(string $table, int $id, array $data): bool
    {
        try {
            $set = '';
            foreach ($data as $key => $value) {
                $set .= "$key = :$key, ";
            }
            $set = rtrim($set, ', ');
            $sql = "UPDATE $table SET $set WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return false;
        }
    }

    public function delete(string $table, int $id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM $table WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return false;
        }
    }

    public function get(string $table, array $params = []): array
    {
        try {
            $sql = "SELECT * FROM $table";
            if (!empty($params)) {
                $conditions = [];
                foreach ($params as $key => $value) {
                    $conditions[] = "$key = :$key";
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return [];
        }
    }
}


/*
// Example usage:
$dsn = 'mysql:host=localhost;dbname=mydatabase;charset=utf8mb4';
$username = 'username';
$password = 'password';
$options = [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];

$database = new Database($dsn, $username, $password, $options);
$users = $database->getAll("users");
$user = $database->getById("users", 1);
*/