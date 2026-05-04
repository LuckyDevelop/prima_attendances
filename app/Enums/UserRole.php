<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'employee';
    case MANAGER  = 'manager';
    case HR       = 'hr';
    case ADMIN    = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::EMPLOYEE => 'Karyawan',
            self::MANAGER  => 'Manager',
            self::HR       => 'HRD',
            self::ADMIN    => 'Administrator',
        };
    }

    /** Returns true if the role can approve leave/permission requests. */
    public function canApprove(): bool
    {
        return match ($this) {
            self::MANAGER, self::HR, self::ADMIN => true,
            self::EMPLOYEE                       => false,
        };
    }
}
