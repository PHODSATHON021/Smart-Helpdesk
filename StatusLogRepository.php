<?php
declare(strict_types=1);

namespace App\Repositories;

final class StatusLogRepository extends BaseRepository
{
    protected string $table = 'status_logs';
    protected array $fillable = ['ticket_id', 'changed_by', 'from_status', 'to_status', 'note'];

    public function findByTicket(int $ticketId): array
    {
        return $this->db->query(
            'SELECT l.*, u.name AS actor_name
             FROM status_logs l JOIN users u ON u.id = l.changed_by
             WHERE l.ticket_id = ? ORDER BY l.id ASC', [$ticketId])->fetchAll();
    }
}
