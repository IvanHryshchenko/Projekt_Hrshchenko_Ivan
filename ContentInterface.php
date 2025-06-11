<?php
interface ContentInterface {
    public function create(array $data): bool;
    public function update(int $id, array $data): bool;
    public function getAll(array $params = []): array;
}