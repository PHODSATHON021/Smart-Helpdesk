<?php
declare(strict_types=1);

namespace App\Observers;

use App\Core\EventDispatcher;
use App\Enums\TicketStatus;
use App\Notifications\NotificationChannelInterface;

/** Observer: ฟัง event จาก Service แล้วส่งแจ้งเตือนตาม Activity Diagram (L1, L2, L3) */
final class TicketObserver
{
    public function __construct(private NotificationChannelInterface $notifier, private string $itTeamId = '') {}

    public function register(EventDispatcher $events): void
    {
        $events->listen('ticket.created', fn(array $p) => $this->handleCreated($p['ticket']));
        $events->listen('ticket.status_changed', fn(array $p) => $this->handleStatusChanged($p['ticket'], $p['from'], $p['to']));
    }

    public function handleCreated(array $t): void
    {
        $this->notifier->send(
            "🆕 Ticket ใหม่ #{$t['id']}\nเรื่อง: {$t['title']}\nหมวด: {$t['category_name']}\nความเร่งด่วน: {$t['priority']}\nผู้แจ้ง: {$t['owner_name']}",
            $this->itTeamId ?: null
        );
    }

    public function handleStatusChanged(array $t, TicketStatus $from, TicketStatus $to): void
    {
        $head = "Ticket #{$t['id']} — {$t['title']}\n";
        match (true) {
            // L2: แจ้งช่างที่ได้รับมอบหมาย
            $to === TicketStatus::Assigned =>
                $this->toUser("📌 คุณได้รับมอบหมายงานใหม่\n{$head}", $t['technician_line_id']),
            // L3: แจ้งผู้ใช้ว่าซ่อมเสร็จ ให้ตรวจสอบ
            $to === TicketStatus::Resolved =>
                $this->toUser("✅ งานซ่อมเสร็จแล้ว กรุณาตรวจสอบและให้คะแนน\n{$head}", $t['owner_line_id']),
            // ถูกตีกลับ (Resolved → InProgress): แจ้งช่าง
            $from === TicketStatus::Resolved && $to === TicketStatus::InProgress =>
                $this->toUser("⚠️ ผู้ใช้ไม่ผ่านการตรวจรับ กรุณาแก้ไขใหม่\n{$head}", $t['technician_line_id']),
            // ช่างรับงาน: แจ้งผู้ใช้
            $to === TicketStatus::InProgress =>
                $this->toUser("🔧 ช่างเริ่มดำเนินการแล้ว\n{$head}", $t['owner_line_id']),
            // ปิดงาน: แจ้งทีม IT
            $to === TicketStatus::Closed =>
                $this->notifier->send("🏁 ปิดงานแล้ว\n{$head}", $this->itTeamId ?: null),
            default => null,
        };
    }

    private function toUser(string $message, ?string $lineUserId): void
    {
        if ($lineUserId) {
            $this->notifier->send($message, $lineUserId);
        }
    }
}
