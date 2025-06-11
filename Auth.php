<?php
class Auth {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login(string $username, string $password): bool {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) { // Используйте password_hash() для продакшена
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        return false;
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public function logout(): void {
        session_unset();
        session_destroy();
        header('Location: ?action=list');
        exit;
    }
}