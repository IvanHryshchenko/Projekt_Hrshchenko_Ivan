```php
<?php
class Article extends Model implements ContentInterface {
    public function __construct(Database $db) {
        parent::__construct($db, 'articles');
    }

    public function deleteById(int $id): bool {
        return parent::deleteById($id);
    }

    public function getAll(array $params = []): array {
        $page = $params['page'] ?? 1;
        $perPage = $params['perPage'] ?? 6;
        $search = $params['search'] ?? '';
        $categoryId = $params['categoryId'] ?? 0;

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, c.name AS category_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE 1=1";
        $queryParams = [];

        if ($search) {
            $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $queryParams[] = "%$search%";
            $queryParams[] = "%$search%";
        }

        if ($categoryId) {
            $sql .= " AND a.category_id = ?";
            $queryParams[] = $categoryId;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $queryParams[] = $perPage;
        $queryParams[] = $offset;

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($queryParams);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotal(string $search = '', int $categoryId = 0): int {
        $sql = "SELECT COUNT(*) FROM articles WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO articles (title, description, author, category_id, created_at) 
             VALUES (?, ?, ?, ?, NOW())"
        );
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['author'],
            $data['category_id'] ?: null
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->getPdo()->prepare(
            "UPDATE articles 
             SET title = ?, description = ?, author = ?, category_id = ? 
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['author'],
            $data['category_id'] ?: null,
            $id
        ]);
    }

    public function getCategories(): array {
        $stmt = $this->db->getPdo()->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment(int $articleId, string $author, string $content): bool {
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO comments (article_id, author, content, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        return $stmt->execute([$articleId, $author, $content]);
    }

    public function getComments(int $articleId): array {
        $stmt = $this->db->getPdo()->prepare(
            "SELECT * FROM comments WHERE article_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
