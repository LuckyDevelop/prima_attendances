<?php

declare(strict_types=1);

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'present';
    case ABSENT  = 'absent';
    case LEAVE   = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => 'Hadir',
            self::ABSENT  => 'Tidak Hadir',
            self::LEAVE   => 'Cuti',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRESENT => 'green',
            self::ABSENT  => 'red',
            self::LEAVE   => 'blue',
        };
    }
}
