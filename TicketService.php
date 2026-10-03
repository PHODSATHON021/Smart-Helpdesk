<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\EventDispatcher;
use App\Core\FileUploader;
use App\Core\HttpException;
use App\Repositories\CommentRepository;
use App\Repositories\StatusLogRepository;
use App\Repositories\TicketRepository;

/** ตาม Sequence 6.1: สร้าง Ticket + ไฟล์แนบ + log + dispatch ticket.created */
final class TicketService
{
    public function __construct(
        private TicketRepository $tickets,
        private CommentRepository $comments,
        private StatusLogRepository $logs,
        private Database $db,
        private EventDispatcher $events,
        private FileUploader $uploader
    ) {}

    public function createTicket(array $data, ?array $file, int $userId): array
    {
        $image = FileUploader::hasFile($file) ? $this->uploader->upload($file) : null;

        try {
            $this->db->beginTransaction();
            $id = $this->tickets->create([
                'user_id'     => $userId,
                'category_id' => (int) $data['category_id'],
                'title'       => trim($data['title']),
                'description' => trim($data['description']),
                'priority'    => $data['priority'],
                'status'      => 'open',
            ]);
            $this->logs->create([
                'ticket_id' => $id, 'changed_by' => $userId,
                'from_status' => null, 'to_status' => 'open', 'note' => 'สร้าง Ticket',
            ]);
            if ($image) {
                $this->comments->create([
                    'ticket_id' => $id, 'user_id' => $userId,
                    'body' => 'แนบรูปประกอบการแจ้งซ่อม', 'image_path' => $image,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->uploader->delete($image);
            throw $e;
        }

        $ticket = $this->tickets->findDetailed($id);
        $this->events->dispatch('ticket.created', ['ticket' => $ticket]);
        return $ticket;
    }

    public function addComment(array $ticket, array $actor, string $body, ?array $file): array
    {
        if (!$this->canComment($ticket, $actor)) {
            throw new HttpException(403, 'คุณไม่มีสิทธิ์แสดงความคิดเห็นใน Ticket นี้');
        }
        $image = FileUploader::hasFile($file) ? $this->uploader->upload($file) : null;
        $id = $this->comments->create([
            'ticket_id' => (int) $ticket['id'], 'user_id' => $actor['id'],
            'body' => trim($body), 'image_path' => $image,
        ]);
        return $this->comments->findDetailed($id);
    }

    // ---------- Authorization (RBAC + Ownership) ----------

    public function canView(array $ticket, array $actor): bool
    {
        return match ($actor['role']) {
            'admin', 'technician' => true,
            default               => (int) $ticket['user_id'] === (int) $actor['id'],
        };
    }

    public function canComment(array $ticket, array $actor): bool
    {
        if ($ticket['status'] === 'closed') {
            return false;
        }
        return match ($actor['role']) {
            'admin'      => true,
            'technician' => (int) $ticket['technician_id'] === (int) $actor['id'],
            default      => (int) $ticket['user_id'] === (int) $actor['id'],
        };
    }
}
