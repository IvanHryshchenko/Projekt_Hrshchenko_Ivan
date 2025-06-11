<?php
class NotificationManager {
    public function setNotification(string $message): void {
        $_SESSION['notification'] = $message;
    }

    public function getNotification(): ?string {
        $message = $_SESSION['notification'] ?? null;
        unset($_SESSION['notification']);
        return $message;
    }
}