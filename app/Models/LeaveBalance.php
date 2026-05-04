<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;
    protected $table = 'leave_balances';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'year',
        'total_quota',
        'used',
        'remaining',
    ];

    protected $casts = [
        'year'        => 'integer',
        'total_quota' => 'integer',
        'used'        => 'integer',
        'remaining'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
