<?php
declare(strict_types=1);

namespace App\Repositories;

final class CategoryRepository extends BaseRepository
{
    protected string $table = 'categories';
    protected array $fillable = ['name', 'description'];

    public function all(): array
    {
        return $this->db->query('SELECT c.*, (SELECT COUNT(*) FROM tickets t WHERE t.category_id = c.id) AS ticket_count
                                 FROM categories c ORDER BY c.name')->fetchAll();
    }
}
