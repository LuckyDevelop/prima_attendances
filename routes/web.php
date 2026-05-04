<?php

use App\Livewire\Admin\Approval\Index as ApprovalIndex;
use App\Livewire\Admin\Attendance\Calendar as AttendanceCalendar;
use App\Livewire\Admin\Attendance\Index as AttendanceIndex;
use App\Livewire\Admin\Attendance\ManualEntry as AttendanceManualEntry;
use App\Livewire\Admin\Attendance\Show as AttendanceShow;
use App\Livewire\Admin\Dashboard\Index as DashboardIndex;
use App\Livewire\Admin\Employee\Form as EmployeeForm;
use App\Livewire\Admin\Employee\Index as EmployeeIndex;
use App\Livewire\Admin\Employee\Show as EmployeeShow;
use App\Livewire\Admin\Leave\Balances as LeaveBalances;
use App\Livewire\Admin\Leave\Calendar as LeaveCalendar;
use App\Livewire\Admin\Leave\Index as LeaveIndex;
use App\Livewire\Admin\Leave\Show as LeaveShow;
use App\Livewire\Admin\Master\Departments as MasterDepartments;
use App\Livewire\Admin\Master\Holidays as MasterHolidays;
use App\Livewire\Admin\Master\LeaveTypes as MasterLeaveTypes;
use App\Livewire\Admin\Master\OfficeLocations as MasterOfficeLocations;
use App\Livewire\Admin\Master\Shifts as MasterShifts;
use App\Livewire\Admin\Permission\Index as PermissionIndex;
use App\Livewire\Admin\Permission\Show as PermissionShow;
use App\Livewire\Admin\Profile\ChangePassword;
use App\Livewire\Admin\Profile\Show as ProfileShow;
use App\Livewire\Admin\Notification\Index as NotificationIndex;
use App\Livewire\Admin\Settings\AuditLogs as SettingsAuditLogs;
use App\Livewire\Admin\Settings\Company as SettingsCompany;
use App\Livewire\Admin\Settings\Users as SettingsUsers;
use App\Livewire\Admin\Report\Attendance as ReportAttendance;
use App\Livewire\Admin\Report\LateArrivals as ReportLateArrivals;
use App\Livewire\Admin\Report\Leave as ReportLeave;
use App\Livewire\Admin\Report\WorkingHours as ReportWorkingHours;
use App\Livewire\Admin\Shift\Calendar as ShiftCalendar;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';

// ─── Admin Panel ───────────────────────────────────────────────────────────────

Route::middleware(['auth', 'verified', 'active.user', 'admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::redirect('/', '/admin/dashboard');

        Route::get('dashboard', DashboardIndex::class)->name('dashboard');

        // Profile
        Route::get('profile', ProfileShow::class)->name('profile');
        Route::get('profile/password', ChangePassword::class)->name('profile.password');

        // Attendance — order matters: static segments before dynamic
        Route::get('attendances/manual-entry', AttendanceManualEntry::class)->name('attendances.manual-entry');
        Route::get('attendances/calendar', AttendanceCalendar::class)->name('attendances.calendar');
        Route::get('attendances', AttendanceIndex::class)->name('attendances.index');
        Route::get('attendances/{id}', AttendanceShow::class)->name('attendances.show');

        // Leave — static segments before dynamic
        Route::get('leaves/calendar', LeaveCalendar::class)->name('leaves.calendar');
        Route::get('leaves/balances', LeaveBalances::class)->name('leaves.balances');
        Route::get('leaves', LeaveIndex::class)->name('leaves.index');
        Route::get('leaves/{id}', LeaveShow::class)->name('leaves.show');

        // Permission
        Route::get('permissions', PermissionIndex::class)->name('permissions.index');
        Route::get('permissions/{id}', PermissionShow::class)->name('permissions.show');

        // Approval
        Route::get('approvals', ApprovalIndex::class)->name('approvals.index');

        // Employees
        Route::get('employees', EmployeeIndex::class)->name('employees.index');
        Route::get('employees/create', EmployeeForm::class)->name('employees.create');
        Route::get('employees/{userId}', EmployeeShow::class)->name('employees.show');
        Route::get('employees/{userId}/edit', EmployeeForm::class)->name('employees.edit');

        // Master data
        Route::prefix('master')->name('master.')->group(function () {
            Route::get('departments', MasterDepartments::class)->name('departments');
            Route::get('office-locations', MasterOfficeLocations::class)->name('office-locations');
            Route::get('shifts', MasterShifts::class)->name('shifts');
            Route::get('leave-types', MasterLeaveTypes::class)->name('leave-types');
            Route::get('holidays', MasterHolidays::class)->name('holidays');
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('attendance', ReportAttendance::class)->name('attendance');
            Route::get('leaves', ReportLeave::class)->name('leaves');
            Route::get('late-arrivals', ReportLateArrivals::class)->name('late-arrivals');
            Route::get('working-hours', ReportWorkingHours::class)->name('working-hours');
        });

        // Settings (admin only)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('company', SettingsCompany::class)->name('company');
            Route::get('users', SettingsUsers::class)->name('users');
            Route::get('audit-logs', SettingsAuditLogs::class)->name('audit-logs');
        });

        // Notifications
        Route::get('notifications', NotificationIndex::class)->name('notifications');

        // Shift Assignment
        Route::get('shift-assignments', ShiftCalendar::class)->name('shift-assignments');
    });

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/login');
})->name('logout');
