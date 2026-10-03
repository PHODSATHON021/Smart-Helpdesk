<?php
declare(strict_types=1);

namespace App\Enums;

/** State Machine: Open → Assigned → InProgress → Resolved → Closed (Resolved ย้อนกลับ InProgress ได้) */
enum TicketStatus: string
{
    case Open       = 'open';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open       => 'เปิดใหม่',
            self::Assigned   => 'มอบหมายแล้ว',
            self::InProgress => 'กำลังดำเนินการ',
            self::Resolved   => 'แก้ไขเสร็จ รอตรวจสอบ',
            self::Closed     => 'ปิดงาน',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open       => 'bg-blue-100 text-blue-800',
            self::Assigned   => 'bg-indigo-100 text-indigo-800',
            self::InProgress => 'bg-amber-100 text-amber-800',
            self::Resolved   => 'bg-emerald-100 text-emerald-800',
            self::Closed     => 'bg-slate-200 text-slate-700',
        };
    }

    /** @return self[] */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open       => [self::Assigned],
            self::Assigned   => [self::InProgress],
            self::InProgress => [self::Resolved],
            self::Resolved   => [self::Closed, self::InProgress],
            self::Closed     => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
