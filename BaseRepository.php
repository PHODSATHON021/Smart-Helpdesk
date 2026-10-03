<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** CRUD พื้นฐาน — ใช้ $fillable เป็น whitelist ชื่อ column เพื่อกัน SQL Injection ผ่านชื่อ key */
abstract class BaseRepository implements RepositoryInterface
{
    protected string $table;
    /** @var string[] */
    protected array $fillable = [];

    public function __construct(protected Database $db) {}

    public function find(int $id): ?array
    {
        $row = $this->db->query("SELECT * FROM {$this->table} WHERE id = ?", [$id])->fetch();
        return $row ?: null;
    }

    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->table} ORDER BY id DESC")->fetchAll();
    }

    public function create(array $data): int
    {
        $data = $this->onlyFillable($data);
        $cols = implode(',', array_keys($data));
        $marks = implode(',', array_fill(0, count($data), '?'));
        $this->db->query("INSERT INTO {$this->table} ($cols) VALUES ($marks)", array_values($data));
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->onlyFillable($data);
        if ($data === []) {
            return false;
        }
        $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
        return $this->db->query("UPDATE {$this->table} SET $set WHERE id = ?", [...array_values($data), $id])->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount() > 0;
    }

    private function onlyFillable(array $data): array
    {
        return array_intersect_key($data, array_flip($this->fillable));
    }
}
