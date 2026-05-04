<?php

declare(strict_types=1);

namespace App\Enums;

enum ApprovalRequestType: string
{
    case LEAVE      = 'leave';
    case PERMISSION = 'permission';

    public function label(): string
    {
        return match ($this) {
            self::LEAVE      => 'Cuti',
            self::PERMISSION => 'Izin',
        };
    }
}
