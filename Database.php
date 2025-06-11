<?php
class Database {
    private PDO $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                'mysql:host=localhost;dbname=popculture_db;charset=utf8mb4',
                'username',
                'password',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function getPdo(): PDO {
        return $this->pdo;
    }
}