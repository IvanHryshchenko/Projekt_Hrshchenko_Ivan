<?php
class Auth {
    private Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    public function login(string $username, string $password): bool {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Проверяем пароль в открытом виде
        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
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
    }
}