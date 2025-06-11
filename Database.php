<?php
class Database {
    private PDO $pdo;

    public function __construct(string $host, string $dbName, string $user, string $pass) {
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$dbName;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("Ошибка подключения: " . $e->getMessage());
        }
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }
}