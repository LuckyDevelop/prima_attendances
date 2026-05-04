<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $table = 'devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'device_name',
        'platform',
        'fcm_token',
        'is_active',
        'last_login',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'last_login' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
