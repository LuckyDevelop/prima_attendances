<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermissionRequest extends Model
{
    use HasFactory;

    protected $table = 'permission_requests';

    protected $fillable = [
        'user_id',
        'permission_type',
        'request_date',
        'start_time',
        'end_time',
        'reason',
        'attachment',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'permission_type' => PermissionType::class,
        'request_date'    => 'date',
        'status'          => LeaveStatus::class,
        'submitted_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'request_id')
            ->where('request_type', 'permission')
            ->orderBy('level');
    }
}
