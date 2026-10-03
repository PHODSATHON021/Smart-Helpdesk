<?php
declare(strict_types=1);

namespace App\Repositories;

final class TicketRepository extends BaseRepository
{
    protected string $table = 'tickets';
    protected array $fillable = ['user_id', 'category_id', 'technician_id', 'title', 'description', 'status', 'priority', 'resolved_at', 'closed_at'];

    private const SELECT_DETAILED = 'SELECT t.*, c.name AS category_name,
            o.name AS owner_name, o.line_user_id AS owner_line_id,
            tc.name AS technician_name, tc.line_user_id AS technician_line_id
        FROM tickets t
        JOIN categories c ON c.id = t.category_id
        JOIN users o ON o.id = t.user_id
        LEFT JOIN users tc ON tc.id = t.technician_id';

    public function findDetailed(int $id): ?array
    {
        $row = $this->db->query(self::SELECT_DETAILED . ' WHERE t.id = ?', [$id])->fetch();
        return $row ?: null;
    }

    public function findByUser(int $userId): array
    {
        return $this->search('', ['user_id' => $userId]);
    }

    public function findByTechnician(int $techId): array
    {
        return $this->search('', ['technician_id' => $techId]);
    }

    /** @param array{status?:string,priority?:string,category_id?:int,user_id?:int,technician_id?:int} $filters */
    public function search(string $keyword = '', array $filters = []): array
    {
        $where = [];
        $params = [];
        if ($keyword !== '') {
            $where[] = '(t.title LIKE ? OR t.description LIKE ? OR t.id = ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = ctype_digit($keyword) ? (int) $keyword : 0;
        }
        foreach (['status', 'priority', 'category_id', 'user_id', 'technician_id'] as $col) {
            if (!empty($filters[$col])) {
                $where[] = "t.$col = ?";
                $params[] = $filters[$col];
            }
        }
        $sql = self::SELECT_DETAILED . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY t.id DESC LIMIT 200';
        return $this->db->query($sql, $params)->fetchAll();
    }

    /** อัปเดตสถานะ พร้อมประทับเวลา resolved_at / closed_at ตาม state */
    public function updateStatus(int $id, string $status, array $extra = []): void
    {
        $data = ['status' => $status] + $extra;
        if ($status === 'resolved') {
            $data['resolved_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'closed') {
            $data['closed_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'in_progress') {
            $data['resolved_at'] = null;   // ถูกตีกลับ: ล้างเวลาที่เคยแก้เสร็จ
        }
        $this->update($id, $data);
    }

    // ---------- Aggregate queries สำหรับ Dashboard ----------

    public function countByStatus(): array
    {
        return $this->db->query('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status')->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /** เวลาเฉลี่ยในการแก้ไข (ชั่วโมง) จากสร้าง → แก้เสร็จ */
    public function avgResolutionTime(): float
    {
        $v = $this->db->query('SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) / 60
                               FROM tickets WHERE resolved_at IS NOT NULL')->fetchColumn();
        return round((float) $v, 1);
    }

    public function topTechnicians(int $limit = 5): array
    {
        $limit = max(1, $limit);
        return $this->db->query(
            "SELECT u.name, COUNT(t.id) AS total, ROUND(AVG(r.score), 2) AS avg_score
             FROM users u
             JOIN tickets t ON t.technician_id = u.id AND t.status IN ('resolved','closed')
             LEFT JOIN ratings r ON r.ticket_id = t.id
             GROUP BY u.id, u.name ORDER BY total DESC LIMIT {$limit}")->fetchAll();
    }

    public function monthlyRatings(int $months = 6): array
    {
        $months = max(1, $months);
        return $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, ROUND(AVG(score), 2) AS avg_score, COUNT(*) AS total
             FROM ratings GROUP BY month ORDER BY month DESC LIMIT {$months}")->fetchAll();
    }

    public function averageRating(): float
    {
        return round((float) $this->db->query('SELECT AVG(score) FROM ratings')->fetchColumn(), 2);
    }
}
