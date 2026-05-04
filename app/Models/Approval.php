<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalDecision;
use App\Enums\ApprovalRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'request_id',
        'request_type',
        'approver_id',
        'level',
        'decision',
        'comment',
        'decided_at',
    ];

    protected $casts = [
        'request_type' => ApprovalRequestType::class,
        'level'        => 'integer',
        'decision'     => ApprovalDecision::class,
        'decided_at'   => 'datetime',
    ];

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class, 'request_id');
    }

    public function permissionRequest(): BelongsTo
    {
        return $this->belongsTo(PermissionRequest::class, 'request_id');
    }
}
