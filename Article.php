<?php
require_once 'ContentInterface.php';

class Article implements ContentInterface {
    private Database $db;
    protected string $table = 'articles';

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function create(array $data): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO {$this->table} (title, description, author, created_at) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$data['title'], $data['description'], $data['author']]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->getPdo()->prepare(
            "UPDATE {$this->table} SET title = ?, description = ?, author = ? WHERE id = ?"
        );
        return $stmt->execute([$data['title'], $data['description'], $data['author'], $id]);
    }

    public function getAll(array $params = []): array {
        $stmt = $this->db->getPdo()->query("SELECT * FROM {$this->table} ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool {
        $stmt = $this->db->getPdo()->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getLikes(int $articleId): int {
        $stmt = $this->db->getPdo()->prepare("SELECT COUNT(*) FROM likes WHERE article_id = ?");
        $stmt->execute([$articleId]);
        return (int)$stmt->fetchColumn();
    }

    public function hasLiked(int $articleId, int $userId): bool {
        $stmt = $this->db->getPdo()->prepare("SELECT COUNT(*) FROM likes WHERE article_id = ? AND user_id = ?");
        $stmt->execute([$articleId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    public function like(int $articleId, int $userId): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT IGNORE INTO likes (article_id, user_id, created_at) VALUES (?, ?, NOW())"
        );
        return $stmt->execute([$articleId, $userId]);
    }

    public function unlike(int $articleId, int $userId): bool {
        $stmt = $this->db->getPdo()->prepare(
            "DELETE FROM likes WHERE article_id = ? AND user_id = ?"
        );
        return $stmt->execute([$articleId, $userId]);
    }

    public function getComments(int $articleId): array {
        $stmt = $this->db->getPdo()->prepare(
            "SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.article_id = ? ORDER BY c.created_at ASC"
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment(int $articleId, int $userId, string $content): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO comments (article_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$articleId, $userId, $content]);
    }
}