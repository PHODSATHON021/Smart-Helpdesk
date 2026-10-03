<?php
declare(strict_types=1);

namespace App\Notifications;

/** Strategy Pattern: เพิ่ม Email / Slack ในอนาคตได้โดยทำ interface นี้ */
interface NotificationChannelInterface
{
    /** @param string|null $to ผู้รับ (เช่น LINE userId/groupId) ถ้า null ใช้ค่า default */
    public function send(string $message, ?string $to = null): bool;
}
