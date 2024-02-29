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
                $sanitizedValue = filter_var($value, FILTER_SANITIZE_STRING);
                $stmt->bindValue(":$key", $sanitizedValue);
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
                $sanitizedValue = filter_var($value, FILTER_SANITIZE_STRING);
                $stmt->bindValue(":$key", $sanitizedValue);
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

    public function transaction(callable $callback): bool
    {
        $this->pdo->beginTransaction();
        try {
            $callback($this);
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            ErrorHandler::handleException($e);
            return false;
        }
    }

    public function get(string $table, array $params = []): array
    {
        try {
            $sql = "SELECT * FROM $table";
            $whereClause = '';
            $bindings = [];

            if (!empty($params)) {
                $conditions = [];
                foreach ($params as $key => $value) {
                    $conditions[] = "$key = :$key";
                    $bindings[":$key"] = $value;
                }
                $whereClause = " WHERE " . implode(' AND ', $conditions);
            }

            $stmt = $this->pdo->prepare($sql . $whereClause);
            $stmt->execute($bindings);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            ErrorHandler::handleException($e);
            return [];
        }
    }

}
