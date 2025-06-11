<?php
class Pagination {
    private int $totalItems;
    private int $perPage;
    private int $currentPage;

    public function __construct(int $totalItems, int $perPage, int $currentPage) {
        $this->totalItems = $totalItems;
        $this->perPage = $perPage;
        $this->currentPage = max(1, $currentPage);
    }

    public function getTotalPages(): int {
        return ceil($this->totalItems / $this->perPage);
    }

    public function getOffset(): int {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function render(string $baseUrl, array $params = []): string {
        $totalPages = $this->getTotalPages();
        if ($totalPages <= 1) {
            return '';
        }

        $html = '<div class="mt-6 flex justify-center space-x-2">';
        $query = http_build_query(array_merge($params, ['page' => $this->currentPage - 1]));
        
        if ($this->currentPage > 1) {
            $html .= "<a href=\"$baseUrl?$query\" class=\"bg-gray-700 text-white py-2 px-4 rounded hover:bg-gray-600 transition\"><i class=\"fas fa-chevron-left\"></i> Назад</a>";
        }

        $html .= "<span class=\"text-gray-300 py-2 px-4\">Страница {$this->currentPage} из $totalPages</span>";

        if ($this->currentPage < $totalPages) {
            $query = http_build_query(array_merge($params, ['page' => $this->currentPage + 1]));
            $html .= "<a href=\"$baseUrl?$query\" class=\"bg-gray-700 text-white py-2 px-4 rounded hover:bg-gray-600 transition\">Вперед <i class=\"fas fa-chevron-right\"></i></a>";
        }

        $html .= '</div>';
        return $html;
    }
}