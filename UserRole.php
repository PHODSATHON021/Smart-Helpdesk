<?php
declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case User       = 'user';
    case Technician = 'technician';
    case Admin      = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User       => 'ผู้ใช้งาน',
            self::Technician => 'ช่างเทคนิค',
            self::Admin      => 'ผู้ดูแลระบบ',
        };
    }
}
