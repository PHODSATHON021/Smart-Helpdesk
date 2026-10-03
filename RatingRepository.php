<?php
declare(strict_types=1);

namespace App\Repositories;

final class RatingRepository extends BaseRepository
{
    protected string $table = 'ratings';
    protected array $fillable = ['ticket_id', 'score', 'feedback'];

    public function findByTicket(int $ticketId): ?array
    {
        $row = $this->db->query('SELECT * FROM ratings WHERE ticket_id = ?', [$ticketId])->fetch();
        return $row ?: null;
    }
}
