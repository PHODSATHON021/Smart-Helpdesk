<?php
declare(strict_types=1);

namespace App\Repositories;

interface RepositoryInterface
{
    public function find(int $id): ?array;
    public function all(): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
