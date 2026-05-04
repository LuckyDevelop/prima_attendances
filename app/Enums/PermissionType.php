<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionType: string
{
    case LATE_ARRIVAL     = 'late_arrival';
    case EARLY_DEPARTURE  = 'early_departure';
    case OUT_OF_OFFICE    = 'out_of_office';

    public function label(): string
    {
        return match ($this) {
            self::LATE_ARRIVAL    => 'Datang Terlambat',
            self::EARLY_DEPARTURE => 'Pulang Awal',
            self::OUT_OF_OFFICE   => 'Di Luar Kantor',
        };
    }
}
