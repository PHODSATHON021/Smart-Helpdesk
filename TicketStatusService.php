<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\EventDispatcher;
use App\Core\FileUploader;
use App\Core\HttpException;
use App\Enums\TicketStatus;
use App\Repositories\CommentRepository;
use App\Repositories\RatingRepository;
use App\Repositories\StatusLogRepository;
use App\Repositories\TicketRepository;
use App\Repositories\UserRepository;

/**
 * หัวใจของระบบ: State Machine (Diagram 3) + Sequence 6.2
 *  1) validateTransition → 2) checkPermission → 3) ตรวจเงื่อนไขบังคับ → 4) Transaction → 5) dispatch event
 */
final class TicketStatusService
{
    public function __construct(
        private TicketRepository $tickets,
        private CommentRepository $comments,
        private StatusLogRepository $logs,
        private RatingRepository $ratings,
        private UserRepository $users,
        private Database $db,
        private EventDispatcher $events,
        private FileUploader $uploader
    ) {}

    public function transition(array $ticket, TicketStatus $to, array $actor, array $payload = [], ?array $file = null): array
    {
        $from = TicketStatus::from($ticket['status']);

        $this->validateTransition($from, $to);
        $this->checkPermission($actor, $ticket, $from, $to);
        $extra = $this->checkRequirements($from, $to, $payload, $file);

        $image = FileUploader::hasFile($file) ? $this->uploader->upload($file) : null;
        if ($to === TicketStatus::Resolved && !$image) {
            throw new HttpException(422, 'ต้องแนบรูปภาพผลการซ่อม', ['image' => ['ต้องแนบรูปภาพผลการซ่อม']]);
        }

        try {
            $this->db->beginTransaction();

            $this->tickets->updateStatus((int) $ticket['id'], $to->value, $extra['ticket']);

            $commentBody = $extra['comment'];
            if ($commentBody !== '' || $image) {
                $this->comments->create([
                    'ticket_id'  => (int) $ticket['id'],
                    'user_id'    => $actor['id'],
                    'body'       => $commentBody !== '' ? $commentBody : 'แนบรูปผลการดำเนินการ',
                    'image_path' => $image,
                ]);
            }
            if ($to === TicketStatus::Closed) {
                $this->ratings->create([
                    'ticket_id' => (int) $ticket['id'],
                    'score'     => (int) $payload['score'],
                    'feedback'  => trim((string) ($payload['feedback'] ?? '')),
                ]);
            }
            $this->logs->create([
                'ticket_id'   => (int) $ticket['id'],
                'changed_by'  => $actor['id'],
                'from_status' => $from->value,
                'to_status'   => $to->value,
                'note'        => $extra['note'],
            ]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            $this->uploader->delete($image);
            throw $e;
        }

        $fresh = $this->tickets->findDetailed((int) $ticket['id']);
        $this->events->dispatch('ticket.status_changed', [
            'ticket' => $fresh, 'from' => $from, 'to' => $to, 'actor' => $actor,
        ]);
        return $fresh;
    }

    private function validateTransition(TicketStatus $from, TicketStatus $to): void
    {
        if (!$from->canTransitionTo($to)) {
            throw new HttpException(422, "ไม่สามารถเปลี่ยนสถานะจาก '{$from->label()}' เป็น '{$to->label()}' ได้");
        }
    }

    /** Transition Rules Matrix (ตารางใน Diagram 3) */
    private function checkPermission(array $actor, array $ticket, TicketStatus $from, TicketStatus $to): void
    {
        $isAdmin = $actor['role'] === 'admin';
        $isAssignedTech = $actor['role'] === 'technician' && (int) $ticket['technician_id'] === (int) $actor['id'];
        $isOwner = (int) $ticket['user_id'] === (int) $actor['id'];

        $allowed = match (true) {
            $from === TicketStatus::Open       && $to === TicketStatus::Assigned   => $isAdmin,
            $from === TicketStatus::Assigned   && $to === TicketStatus::InProgress => $isAssignedTech,
            $from === TicketStatus::InProgress && $to === TicketStatus::Resolved   => $isAssignedTech,
            $from === TicketStatus::Resolved   && $to === TicketStatus::Closed     => $isOwner,
            $from === TicketStatus::Resolved   && $to === TicketStatus::InProgress => $isOwner,
            default                                                               => false,
        };
        if (!$allowed) {
            throw new HttpException(403, 'คุณไม่มีสิทธิ์เปลี่ยนสถานะนี้');
        }
    }

    /** เงื่อนไขบังคับของแต่ละ transition (คอลัมน์ Required Action) */
    private function checkRequirements(TicketStatus $from, TicketStatus $to, array $p, ?array $file): array
    {
        $comment = trim((string) ($p['comment'] ?? ''));
        $ticketData = [];
        $note = null;
        $fail = fn(string $field, string $msg) => throw new HttpException(422, $msg, [$field => [$msg]]);

        if ($to === TicketStatus::Assigned) {
            $techId = (int) ($p['technician_id'] ?? 0);
            $tech = $techId ? $this->users->find($techId) : null;
            if (!$tech || $tech['role'] !== 'technician') {
                $fail('technician_id', 'กรุณาเลือกช่างเทคนิคที่ถูกต้อง');
            }
            $ticketData['technician_id'] = $techId;
            $note = 'มอบหมายให้ ' . $tech['name'];
        } elseif ($from === TicketStatus::Assigned && $to === TicketStatus::InProgress) {
            $comment !== '' || $fail('comment', 'ต้องเพิ่ม comment ก่อนเริ่มดำเนินการ');
            $note = 'ช่างรับงาน';
        } elseif ($to === TicketStatus::Resolved) {
            $note = 'แก้ไขเสร็จสิ้น';
        } elseif ($to === TicketStatus::Closed) {
            $score = $p['score'] ?? null;
            if (filter_var($score, FILTER_VALIDATE_INT) === false || (int) $score < 1 || (int) $score > 5) {
                $fail('score', 'กรุณาให้คะแนน 1-5');
            }
            $note = "ยืนยันปิดงาน (คะแนน {$score}/5)";
        } elseif ($from === TicketStatus::Resolved && $to === TicketStatus::InProgress) {
            mb_strlen($comment) >= 5 || $fail('comment', 'กรุณาระบุเหตุผลที่ไม่ผ่าน (อย่างน้อย 5 ตัวอักษร)');
            $comment = 'ไม่ผ่านการตรวจสอบ: ' . $comment;
            $note = $comment;
        }
        return ['ticket' => $ticketData, 'comment' => $comment, 'note' => $note];
    }
}
