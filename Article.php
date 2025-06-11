<?php
class Article {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll(int $page = 1, int $perPage = 6, string $search = '', int $categoryId = 0): array {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT a.*, c.name AS category_name 
                FROM articles a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (a.title LIKE ? OR a.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categoryId > 0) {
            $sql .= " AND a.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->getPdo()->prepare($sql);
        // Явное указание типов для LIMIT и OFFSET
        $paramCount = count($params) - 2; // Индексы последних двух параметров
        $stmt->bindValue($paramCount + 1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($paramCount + 2, $offset, PDO::PARAM_INT);

        // Отладка: выводим SQL и параметры
        error_log("SQL: $sql");
        error_log("Params: " . print_r($params, true));

        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getTotal(string $search = '', int $categoryId = 0): int {
        $sql = "SELECT COUNT(*) FROM articles WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($categoryId > 0) {
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
            $data['category_id']
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->getPdo()->prepare(
            "UPDATE articles SET title = ?, description = ?, author = ?, category_id = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['author'],
            $data['category_id'],
            $id
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->getPdo()->prepare("DELETE FROM articles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getCategories(): array {
        $stmt = $this->db->getPdo()->query("SELECT * FROM categories ORDER BY name");
        return $stmt->fetchAll();
    }

    public function getComments(int $articleId): array {
        $stmt = $this->db->getPdo()->prepare("SELECT * FROM comments WHERE article_id = ? ORDER BY created_at DESC");
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    public function addComment(int $articleId, string $author, string $content): bool {
        $stmt = $this->db->getPdo()->prepare("INSERT INTO comments (article_id, author, content, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$articleId, $author, $content]);
    }

    public function renderPagination(int $page, int $perPage, string $search, int $categoryId): string {
        $total = $this->getTotal($search, $categoryId);
        $totalPages = ceil($total / $perPage);

        if ($totalPages <= 1) return '';

        $html = '<div class="flex justify-center mt-4 space-x-2">';
        if ($page > 1) {
            $params = http_build_query(['action' => 'list', 'page' => $page - 1, 'search' => $search, 'category' => $categoryId]);
            $html .= "<a href='?$params' class='bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700'><i class='fas fa-chevron-left'></i></a>";
        }
        $html .= "<span class='px-3 py-1'>$page из $totalPages</span>";
        if ($page < $totalPages) {
            $params = http_build_query(['action' => 'list', 'page' => $page + 1, 'search' => $search, 'category' => $categoryId]);
            $html .= "<a href='?$params' class='bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700'><i class='fas fa-chevron-right'></i></a>";
        }
        $html .= '</div>';
        return $html;
    }
}