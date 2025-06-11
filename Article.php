<?php
class Article {
    private Database $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
    
    public function getAll(): array {
        $stmt = $this->db->getPdo()->query("SELECT * FROM articles ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById(int $id): ?array {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    public function create(string $title, string $content, string $author): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO articles (title, content, author, created_at) VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$title, $content, $author]);
    }
    
    public function update(int $id, string $title, string $content, string $author): bool {
        $stmt = $this->db->getPdo()->prepare(
            "UPDATE articles SET title = ?, content = ?, author = ? WHERE id = ?"
        );
        return $stmt->execute([$title, $content, $author, $id]);
    }
    
    public function delete(int $id): bool {
        $stmt = $this->db->getPdo()->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }
}