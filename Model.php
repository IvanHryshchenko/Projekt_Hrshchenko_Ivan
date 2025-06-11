<?php
abstract class Model {
    protected Database $db;
    protected string $table;

    public function __construct(Database $db, string $table) {
        $this->db = $db;
        $this->table = $table;
    }

    protected function findById(int $id): ?array {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    protected function deleteById(int $id): bool {
        $stmt = $this->db->getPdo()->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}