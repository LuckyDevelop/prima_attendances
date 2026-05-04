<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'office_location_id',
        'work_date',
        'check_in_time',
        'check_out_time',
        'check_in_lat',
        'check_in_lng',
        'check_out_lat',
        'check_out_lng',
        'check_in_selfie',
        'check_out_selfie',
        'is_mock_location',
        'face_verified',
        'status',
        'work_duration_min',
        'notes',
    ];

    protected $casts = [
        'work_date'        => 'date',
        'check_in_time'    => 'datetime',
        'check_out_time'   => 'datetime',
        'check_in_lat'     => 'decimal:7',
        'check_in_lng'     => 'decimal:7',
        'check_out_lat'    => 'decimal:7',
        'check_out_lng'    => 'decimal:7',
        'is_mock_location' => 'boolean',
        'face_verified'    => 'boolean',
        'status'           => AttendanceStatus::class,
        'work_duration_min' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function officeLocation(): BelongsTo
    {
        return $this->belongsTo(OfficeLocation::class);
    }
}
