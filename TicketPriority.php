<?php
declare(strict_types=1);

namespace App\Enums;

enum TicketPriority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'ต่ำ',
            self::Medium => 'ปานกลาง',
            self::High   => 'สูง',
            self::Urgent => 'เร่งด่วน',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low    => 'bg-slate-100 text-slate-700',
            self::Medium => 'bg-sky-100 text-sky-800',
            self::High   => 'bg-orange-100 text-orange-800',
            self::Urgent => 'bg-red-100 text-red-800',
        };
    }
}
