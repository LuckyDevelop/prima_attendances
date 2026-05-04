<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Approval\ApprovalController;
use App\Http\Controllers\Api\Attendance\AttendanceController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Device\DeviceController;
use App\Http\Controllers\Api\Holiday\HolidayController;
use App\Http\Controllers\Api\Leave\LeaveRequestController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Permission\PermissionRequestController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Shift\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1');

        Route::middleware(['auth:sanctum', 'active.user'])->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'active.user'])->group(function () {

        /*
        | Attendance
        */
        Route::prefix('attendance')->group(function () {
            Route::get('today', [AttendanceController::class, 'today']);
            Route::post('check-in', [AttendanceController::class, 'checkIn']);
            Route::post('check-out', [AttendanceController::class, 'checkOut']);
            Route::get('history', [AttendanceController::class, 'history']);
            Route::get('locations', [AttendanceController::class, 'locations']);
        });

        /*
        | Leave
        */
        Route::get('leave-types', [LeaveRequestController::class, 'leaveTypes']);
        Route::get('leave-balances', [LeaveRequestController::class, 'leaveBalances']);
        Route::apiResource('leaves', LeaveRequestController::class)->only(['index', 'store', 'show', 'destroy']);

        /*
        | Permission
        */
        Route::apiResource('permissions', PermissionRequestController::class)->only(['index', 'store', 'show', 'destroy']);

        /*
        | Approvals (manager / hr / admin only)
        */
        Route::middleware('role:manager,hr,admin')->group(function () {
            Route::get('approvals/pending', [ApprovalController::class, 'pending']);
            Route::post('leaves/{id}/approve', [LeaveRequestController::class, 'approve']);
            Route::post('leaves/{id}/reject', [LeaveRequestController::class, 'reject']);
            Route::post('permissions/{id}/approve', [PermissionRequestController::class, 'approve']);
            Route::post('permissions/{id}/reject', [PermissionRequestController::class, 'reject']);
        });

        /*
        | Notifications
        */
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('read-all', [NotificationController::class, 'readAll']);
            Route::post('{id}/read', [NotificationController::class, 'read']);
        });

        /*
        | Profile
        */
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::put('password', [ProfileController::class, 'changePassword']);
            Route::post('avatar', [ProfileController::class, 'uploadAvatar']);
            Route::post('face-embedding', [ProfileController::class, 'faceEmbedding']);
        });

        /*
        | Devices
        */
        Route::prefix('devices')->group(function () {
            Route::get('/', [DeviceController::class, 'index']);
            Route::post('register', [DeviceController::class, 'register']);
            Route::put('fcm-token', [DeviceController::class, 'updateFcmToken']);
            Route::delete('{id}', [DeviceController::class, 'destroy']);
        });

        /*
        | Shifts (read-only)
        */
        Route::prefix('shifts')->group(function () {
            Route::get('today', [ShiftController::class, 'today']);
            Route::get('schedule', [ShiftController::class, 'schedule']);
        });

        /*
        | Holidays
        */
        Route::get('holidays', [HolidayController::class, 'index']);
    });

});
