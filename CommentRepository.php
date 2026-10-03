<?php
declare(strict_types=1);

namespace App\Repositories;

final class CommentRepository extends BaseRepository
{
    protected string $table = 'comments';
    protected array $fillable = ['ticket_id', 'user_id', 'body', 'image_path'];

    public function findByTicket(int $ticketId): array
    {
        return $this->db->query(
            'SELECT c.*, u.name AS author_name, u.role AS author_role
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.ticket_id = ? ORDER BY c.id ASC', [$ticketId])->fetchAll();
    }

    public function findDetailed(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT c.*, u.name AS author_name, u.role AS author_role
             FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ?', [$id])->fetch();
        return $row ?: null;
    }

    /** หา ticket_id จากชื่อไฟล์ภาพ (ใช้ตรวจสิทธิ์ก่อนแสดงรูป) */
    public function ticketIdByImage(string $file): ?int
    {
        $id = $this->db->query('SELECT ticket_id FROM comments WHERE image_path = ? LIMIT 1', [$file])->fetchColumn();
        return $id === false ? null : (int) $id;
    }
}
