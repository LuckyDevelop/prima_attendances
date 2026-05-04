<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING  => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }

    /** Returns true if the request can still be cancelled by the requester. */
    public function isCancellable(): bool
    {
        return $this === self::PENDING;
    }
}
