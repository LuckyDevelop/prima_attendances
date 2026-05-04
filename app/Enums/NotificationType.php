<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationType: string
{
    case INFO    = 'info';
    case WARNING = 'warning';
    case ERROR   = 'error';

    public function label(): string
    {
        return match ($this) {
            self::INFO    => 'Informasi',
            self::WARNING => 'Peringatan',
            self::ERROR   => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO    => 'blue',
            self::WARNING => 'yellow',
            self::ERROR   => 'red',
        };
    }
}
