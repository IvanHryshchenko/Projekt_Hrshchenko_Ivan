<?php
class Notification {
    public function set(string $message): void {
        $_SESSION['notification'] = $message;
    }

    public function get(): ?string {
        $message = $_SESSION['notification'] ?? null;
        unset($_SESSION['notification']);
        return $message;
    }
}
?>