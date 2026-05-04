# CLAUDE.md — Prima Attendances

## Project Overview

**Nama Aplikasi**: Prima Attendances
**Perusahaan**: PT Prima Synergy Petroleum Indonesia
**Tujuan**: Sistem manajemen absensi karyawan berbasis mobile (Flutter) dengan backend Laravel REST API. Mendukung absensi GPS, selfie, verifikasi wajah, manajemen cuti, izin, dan shift.

---

## Tech Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Backend     | PHP 8.2+, Laravel 12              |
| Auth API    | Laravel Sanctum 4.0 (token-based) |
| Web UI      | Livewire 3 + Volt                 |
| Database    | MySQL (prod: `prima_attendance`)  |
| Frontend    | TailwindCSS 3, Vite               |
| Testing     | PestPHP 4                         |
| Queue       | Database driver                   |
| File Disk   | `public` disk                     |

### Key Packages
- `laravel/sanctum` ^4.0 — API token auth
- `livewire/livewire` ^3.6 — web admin UI
- `livewire/volt` ^1.7 — Livewire single-file components
- `laravel/breeze` ^2.4 (dev) — Auth scaffolding

---

## Environment

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=prima_attendance
DB_USERNAME=root
DB_PASSWORD=prima1682022
FILESYSTEM_DISK=public
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Default admin credentials (seeder):
- Email: `superadmin@gmail.com`
- Password: `prima1682022`

---

## Database Schema

### `users`
| Column              | Type      | Notes                                      |
|---------------------|-----------|--------------------------------------------|
| id                  | bigint    | PK                                         |
| company_id          | bigint    | FK → companies                             |
| department_id       | bigint    | FK → departments (nullable)                |
| office_location_id  | bigint    | FK → office_locations (nullable)           |
| employee_id         | string    | e.g. `EMP001`                              |
| full_name           | string    | (renamed dari kolom `name`)                |
| email               | string    | unique                                     |
| phone               | string    | nullable                                   |
| photo               | text      | nullable, stored in public disk            |
| face_embedding      | text      | nullable, JSON vector for face recognition |
| role                | enum      | `employee`, `manager`, `hr`, `admin`       |
| status              | enum      | `active`, `inactive`                       |
| password            | string    | hashed (bcrypt)                            |
| email_verified_at   | timestamp | nullable                                   |

### `companies`
| Column             | Type    | Notes              |
|--------------------|---------|--------------------|
| id                 | bigint  | PK                 |
| name               | string  | max 150            |
| logo               | text    | nullable           |
| timezone           | string  | default `UTC`      |
| late_tolerance_min | integer | default 0          |

### `departments`
| Column     | Type   | Notes                                  |
|------------|--------|----------------------------------------|
| id         | bigint | PK                                     |
| company_id | bigint | FK → companies                         |
| name       | string | max 150                                |
| manager_id | bigint | FK → users, nullable, nullOnDelete     |

### `office_locations`
| Column        | Type    | Notes            |
|---------------|---------|------------------|
| id            | bigint  | PK               |
| company_id    | bigint  | FK → companies   |
| name          | string  | max 150          |
| address       | string  | nullable         |
| latitude      | decimal | 10,7 nullable    |
| longitude     | decimal | 10,7 nullable    |
| radius_meters | integer | default 100      |
| is_active     | boolean | default true     |

### `shifts`
| Column        | Type    | Notes          |
|---------------|---------|----------------|
| id            | bigint  | PK             |
| company_id    | bigint  | FK → companies |
| name          | string  | max 150        |
| start_time    | time    |                |
| end_time      | time    |                |
| break_minutes | integer | default 0      |

### `shift_assignments`
| Column    | Type   | Notes       |
|-----------|--------|-------------|
| id        | bigint | PK          |
| user_id   | bigint | FK → users  |
| shift_id  | bigint | FK → shifts |
| work_date | date   |             |

### `attendances`
| Column             | Type      | Notes                             |
|--------------------|-----------|-----------------------------------|
| id                 | bigint    | PK                                |
| user_id            | bigint    | FK → users                        |
| office_location_id | bigint    | FK → office_locations             |
| work_date          | date      |                                   |
| check_in_time      | timestamp | nullable                          |
| check_out_time     | timestamp | nullable                          |
| check_in_lat       | decimal   | 10,7 nullable                     |
| check_out_lat      | decimal   | 10,7 nullable                     |
| check_in_lng       | decimal   | 10,7 nullable                     |
| check_out_lng      | decimal   | 10,7 nullable                     |
| check_in_selfie    | string    | nullable, path to file            |
| check_out_selfie   | string    | nullable, path to file            |
| is_mock_location   | boolean   | default false (anti GPS-spoofing) |
| face_verified      | boolean   | default false                     |
| status             | enum      | `present`, `absent`, `leave`      |
| work_duration_min  | integer   | default 0                         |
| notes              | text      | nullable                          |

### `leave_types`
| Column              | Type    | Notes          |
|---------------------|---------|----------------|
| id                  | bigint  | PK             |
| company_id          | bigint  | FK → companies |
| name                | string  | max 150        |
| default_quota       | integer | default 0      |
| requires_attachment | boolean | default false  |
| is_paid             | boolean | default false  |

Seeded leave types:
- Cuti Tahunan (12 hari, paid)
- Cuti Sakit (10 hari, paid, requires attachment)
- Cuti Melahirkan (90 hari, paid, requires attachment)
- Cuti Tanpa Bayar (0 hari, unpaid)

### `leave_requests`
| Column        | Type      | Notes                             |
|---------------|-----------|-----------------------------------|
| id            | bigint    | PK                                |
| user_id       | bigint    | FK → users                        |
| leave_type_id | bigint    | FK → leave_types                  |
| start_date    | date      |                                   |
| end_date      | date      |                                   |
| total_days    | integer   | default 0                         |
| reason        | text      | nullable                          |
| attachment    | text      | file path                         |
| status        | enum      | `pending`, `approved`, `rejected` |
| submitted_at  | timestamp | nullable                          |

### `leave_balances`
| Column        | Type    | Notes            |
|---------------|---------|------------------|
| id            | bigint  | PK               |
| user_id       | bigint  | FK → users       |
| leave_type_id | bigint  | FK → leave_types |
| year          | integer |                  |
| total_quota   | integer | default 0        |
| used          | integer | default 0        |
| remaining     | integer | default 0        |

### `permission_requests`
| Column          | Type      | Notes                                              |
|-----------------|-----------|----------------------------------------------------|
| id              | bigint    | PK                                                 |
| user_id         | bigint    | FK → users                                         |
| permission_type | enum      | `late_arrival`, `early_departure`, `out_of_office` |
| request_date    | date      |                                                    |
| start_time      | time      | nullable                                           |
| end_time        | time      | nullable                                           |
| reason          | text      | nullable                                           |
| attachment      | text      | nullable                                           |
| status          | enum      | `pending`, `approved`, `rejected`                  |
| submitted_at    | timestamp | nullable                                           |

### `approvals`
| Column       | Type      | Notes                                    |
|--------------|-----------|------------------------------------------|
| id           | bigint    | PK                                       |
| request_id   | bigint    | ID dari leave_request/permission_request |
| request_type | enum      | `leave`, `permission`                    |
| approver_id  | bigint    | FK → users                               |
| level        | integer   | approval level, default 1                |
| decision     | enum      | `pending`, `approved`, `rejected`        |
| comment      | text      | nullable                                 |
| decided_at   | timestamp | nullable                                 |

### `holidays`
| Column      | Type    | Notes          |
|-------------|---------|----------------|
| id          | bigint  | PK             |
| company_id  | bigint  | FK → companies |
| date        | date    |                |
| name        | string  | max 150        |
| is_national | boolean | default false  |

### `devices`
| Column      | Type      | Notes                  |
|-------------|-----------|------------------------|
| id          | bigint    | PK                     |
| user_id     | bigint    | FK → users             |
| device_id   | text      | nullable               |
| device_name | text      | nullable               |
| platform    | text      | nullable (ios/android) |
| fcm_token   | text      | nullable, for FCM push |
| is_active   | boolean   | default true           |
| last_login  | timestamp | nullable               |

### `notifications`
| Column  | Type    | Notes                      |
|---------|---------|----------------------------|
| id      | bigint  | PK                         |
| user_id | bigint  | tidak ada FK constraint    |
| title   | string  |                            |
| body    | text    |                            |
| type    | enum    | `info`, `warning`, `error` |
| is_read | boolean | default false              |

### `audit_logs`
| Column     | Type   | Notes                 |
|------------|--------|-----------------------|
| id         | bigint | PK                    |
| user_id    | bigint | nullable              |
| action     | text   |                       |
| entity     | text   | nullable, model name  |
| entity_id  | text   | nullable              |
| metadata   | json   | nullable              |
| ip_address | text   | nullable              |

### `personal_access_tokens` (Sanctum)
Standard Sanctum morphs table. Token-based auth untuk API mobile.

---

## Authentication & Roles

**API Auth**: Laravel Sanctum — token-based (`Authorization: Bearer <token>`)
**Web Auth**: Livewire/Volt session-based (Breeze scaffolding)

### Roles (RBAC)
| Role       | Akses                                                      |
|------------|------------------------------------------------------------|
| `employee` | Absensi diri sendiri, pengajuan cuti/izin, lihat histori   |
| `manager`  | Semua employee + approve cuti/izin anggota tim             |
| `hr`       | Manajemen karyawan, laporan, lihat semua data              |
| `admin`    | Full access: master data, setting perusahaan, user mgmt    |

---

## Directory Structure (Existing)

```
prima_attendances/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Auth/VerifyEmailController.php
│   ├── Livewire/
│   │   ├── Actions/Logout.php
│   │   └── Forms/LoginForm.php
│   ├── Models/
│   │   ├── Approval.php, Attendance.php, AuditLog.php
│   │   ├── Company.php, Department.php, Device.php
│   │   ├── Holiday.php, LeaveBalance.php, LeaveRequest.php
│   │   ├── LeaveType.php, Notification.php, OfficeLocation.php
│   │   ├── PermissionRequest.php, Shift.php, ShiftAssignment.php
│   │   └── User.php           ← HasApiTokens (Sanctum)
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── VoltServiceProvider.php
├── database/
│   ├── migrations/            ← semua tabel sudah terbuat (16 tabel)
│   └── seeders/
│       ├── CompanySeeder.php    ← PT Prima Synergy Petroleum Indonesia
│       ├── DepartmentSeeder.php ← IT, HR, Finance, Operational, GM, Marketing, Admin
│       ├── LeaveTypeSeeder.php  ← 4 jenis cuti
│       └── UserSeeder.php      ← admin: superadmin@gmail.com / prima1682022
├── routes/
│   ├── api.php                ← hanya /api/user placeholder (belum diisi)
│   ├── auth.php               ← Livewire/Volt web auth
│   └── web.php
└── config/
    ├── auth.php, sanctum.php
    └── ...
```

---

## Current State (April 2026)

- [x] Semua migrasi database selesai (16 tabel)
- [x] Semua Models ada (basic, belum ada relasi/scopes/casts)
- [x] Sanctum installed & `personal_access_tokens` dimigrasikan
- [x] `User` model menggunakan `HasApiTokens`
- [x] Web auth (Livewire/Volt): login, register, forgot/reset password
- [x] Default seeders: Company, Departments, LeaveTypes, admin user
- [ ] API Controllers — **belum ada**
- [ ] API Routes di `routes/api.php` — **belum didefinisikan**
- [ ] Model relationships (belongsTo, hasMany, dll) — **belum ada**
- [ ] Form Requests / Validation — **belum ada**
- [ ] API Resources / Transformers — **belum ada**
- [ ] Business logic: absensi, approval workflow — **belum ada**
- [ ] Middleware role/permission — **belum ada**
- [ ] Repository layer — **belum ada**
- [ ] Enums (UserRole, AttendanceStatus, dll) — **belum ada**

---

## Architecture

### Alur Request
```
Route → Controller → Repository → Model (Eloquent)
```

Setiap layer punya tanggung jawab yang jelas:

| Layer          | Tanggung Jawab                                                  |
|----------------|-----------------------------------------------------------------|
| **Route**      | Mapping URL ke controller method, grouping middleware           |
| **Controller** | Validasi input, try-catch, DB transaction, return response      |
| **Repository** | Query database (Eloquent), tidak ada business logic di sini     |
| **Model**      | Definisi relasi, casts, fillable                                |

---

### Controller — Aturan Penulisan

Controller adalah tempat untuk:
- Validasi request (`$request->validate(...)`)
- Buka dan tutup `DB::beginTransaction()`
- `try { ... } catch (\Exception $e) { ... }`
- Memanggil repository method
- Return JSON response

Controller **tidak boleh** berisi raw query Eloquent langsung — semua query lewat repository.

```php
// app/Http/Controllers/Api/AttendanceController.php

public function checkIn(CheckInRequest $request): JsonResponse
{
    DB::beginTransaction();
    try {
        $attendance = $this->attendanceRepository->checkIn(
            auth()->user(),
            $request->validated()
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Check-in berhasil.',
            'data'    => new AttendanceResource($attendance),
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('Check-in failed', [
            'user_id' => auth()->id(),
            'error'   => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal melakukan check-in.',
        ], 500);
    }
}
```

---

### Repository — Aturan Penulisan

Repository berisi semua query Eloquent. Tidak ada try-catch, tidak ada transaction di sini.
Setiap model punya repository sendiri dengan interface.

```php
// app/Repositories/Contracts/AttendanceRepositoryInterface.php

interface AttendanceRepositoryInterface
{
    public function checkIn(User $user, array $data): Attendance;
    public function checkOut(Attendance $attendance, array $data): Attendance;
    public function getTodayAttendance(User $user): ?Attendance;
    public function getUserHistory(User $user, array $filters): LengthAwarePaginator;
}
```

```php
// app/Repositories/AttendanceRepository.php

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function checkIn(User $user, array $data): Attendance
    {
        return Attendance::create([
            'user_id'            => $user->id,
            'office_location_id' => $data['office_location_id'],
            'work_date'          => now()->toDateString(),
            'check_in_time'      => now(),
            'check_in_lat'       => $data['latitude'],
            'check_in_lng'       => $data['longitude'],
            'check_in_selfie'    => $data['selfie_path'] ?? null,
            'face_verified'      => $data['face_verified'] ?? false,
            'is_mock_location'   => $data['is_mock_location'] ?? false,
            'status'             => 'present',
        ]);
    }

    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::where('user_id', $user->id)
            ->whereDate('work_date', today())
            ->first();
    }
}
```

---

### Directory Structure (Target)

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── Auth/
│   │       │   └── AuthController.php
│   │       ├── AttendanceController.php
│   │       ├── LeaveRequestController.php
│   │       ├── PermissionRequestController.php
│   │       ├── ApprovalController.php
│   │       ├── NotificationController.php
│   │       ├── ShiftController.php
│   │       └── ProfileController.php
│   ├── Requests/
│   │   └── Api/
│   │       ├── Auth/
│   │       │   └── LoginRequest.php
│   │       ├── CheckInRequest.php
│   │       ├── CheckOutRequest.php
│   │       ├── StoreLeaveRequest.php
│   │       └── StorePermissionRequest.php
│   ├── Resources/
│   │   └── Api/
│   │       ├── UserResource.php
│   │       ├── AttendanceResource.php
│   │       ├── LeaveRequestResource.php
│   │       └── ...
│   └── Middleware/
│       ├── CheckRole.php
│       └── EnsureUserIsActive.php
├── Repositories/
│   ├── Contracts/
│   │   ├── AttendanceRepositoryInterface.php
│   │   ├── LeaveRequestRepositoryInterface.php
│   │   ├── PermissionRequestRepositoryInterface.php
│   │   ├── UserRepositoryInterface.php
│   │   └── ...
│   ├── AttendanceRepository.php
│   ├── LeaveRequestRepository.php
│   ├── PermissionRequestRepository.php
│   ├── UserRepository.php
│   └── ...
├── Enums/
│   ├── UserRole.php
│   ├── UserStatus.php
│   ├── AttendanceStatus.php
│   ├── LeaveStatus.php
│   ├── PermissionType.php
│   ├── ApprovalDecision.php
│   ├── ApprovalRequestType.php
│   └── NotificationType.php
├── Helpers/
│   └── GeolocationHelper.php
├── Traits/
│   └── ApiResponse.php
├── Models/
│   └── ... (existing)
└── Providers/
    └── RepositoryServiceProvider.php   ← bind interface → implementation
```

---

### RepositoryServiceProvider

Semua binding interface → class didaftarkan di satu provider:

```php
// app/Providers/RepositoryServiceProvider.php

public function register(): void
{
    $this->app->bind(AttendanceRepositoryInterface::class, AttendanceRepository::class);
    $this->app->bind(LeaveRequestRepositoryInterface::class, LeaveRequestRepository::class);
    $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    // ...
}
```

Daftarkan di `bootstrap/providers.php`:
```php
App\Providers\RepositoryServiceProvider::class,
```

---

### API Response Format

Gunakan format JSON yang konsisten di semua endpoint:

```json
// Success
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": { ... }
}

// Success dengan pagination
{
  "success": true,
  "message": "Data berhasil diambil.",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}

// Error validasi (422)
{
  "success": false,
  "message": "Data tidak valid.",
  "errors": {
    "email": ["Email tidak boleh kosong."]
  }
}

// Error server (500)
{
  "success": false,
  "message": "Terjadi kesalahan pada server."
}
```

---

### Form Request — Aturan Penulisan

Semua validasi diletakkan di Form Request, **bukan** di controller.

```php
// app/Http/Requests/Api/StoreLeaveRequest.php

public function rules(): array
{
    return [
        'leave_type_id' => ['required', 'exists:leave_types,id'],
        'start_date'    => ['required', 'date', 'after_or_equal:today'],
        'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
        'reason'        => ['nullable', 'string', 'max:500'],
        'attachment'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
    ];
}

public function messages(): array
{
    return [
        'leave_type_id.required'    => 'Jenis cuti wajib dipilih.',
        'start_date.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
    ];
}
```

---

### File Uploads

- Disk: `public` (configured in `.env`)
- Selfie check-in: `selfies/{user_id}/{date}_checkin.jpg`
- Selfie check-out: `selfies/{user_id}/{date}_checkout.jpg`
- Lampiran cuti/izin: `attachments/{user_id}/{type}/{filename}`

---

### Timezone

- Timezone perusahaan: `Asia/Jakarta`
- DB menyimpan waktu dalam UTC
- Gunakan `now()->setTimezone($company->timezone)` saat display

---

## Coding Conventions

### PHP Style
- PSR-12 standard
- Strict types: `declare(strict_types=1);` di setiap file PHP
- Constructor property promotion (PHP 8+)
- Nullable type hints: `?string` bukan `string|null`
- Return type wajib di semua method
- Visibility wajib (`public`/`protected`/`private`)

### Naming Conventions
| Element            | Convention            | Example                       |
|--------------------|-----------------------|-------------------------------|
| Classes            | `PascalCase`          | `AttendanceController`        |
| Methods            | `camelCase`           | `getUserHistory()`            |
| Variables          | `camelCase`           | `$attendanceData`             |
| Database columns   | `snake_case`          | `check_in_time`               |
| Routes             | `kebab-case`          | `/check-in`, `/leave-requests`|
| Constants          | `UPPER_SNAKE_CASE`    | `DEFAULT_PAGE_SIZE`           |
| Files (Models)     | Singular PascalCase   | `LeaveRequest.php`            |
| Files (Migrations) | Snake case + plural   | `create_attendances_table`    |

### Comments & Documentation
- DocBlock untuk semua public method
- Comment dalam **Bahasa Inggris**
- User-facing message dalam **Bahasa Indonesia**
- Hindari comment yang obvious (yang menjelaskan WHAT), tulis WHY
- Type hints lebih utama daripada PHPDoc — kecuali butuh detail tambahan (misal generic type collection)

### Imports
- Group imports: framework → 3rd party → app
- Alphabetical order dalam group
- No wildcard imports (no `use App\Models\*`)

---

## Error Handling

### Exception Types
| Exception                   | HTTP Code | Handling                                |
|-----------------------------|-----------|-----------------------------------------|
| `ValidationException`       | 422       | Auto-handled by Form Request            |
| `AuthenticationException`   | 401       | Auto-handled by Sanctum                 |
| `AuthorizationException`    | 403       | Auto-handled by Policy/middleware       |
| `ModelNotFoundException`    | 404       | Auto-handled atau via `findOrFail`      |
| Custom domain exceptions    | varies    | Di `App\Exceptions\` (e.g., `AttendanceAlreadyExistsException`) |
| Generic `\Exception`        | 500       | Wajib log + return generic message      |

### Try-Catch Rules

**WAJIB pakai try-catch untuk:**
- File upload operations
- DB transactions (multi-table operation)
- External API calls
- Operasi yang melibatkan filesystem + database
- Operasi yang affect multiple resources

**TIDAK perlu try-catch untuk:**
- Simple read query (Laravel auto-handle)
- Validasi (sudah di Form Request)
- Single insert/update tanpa side-effect

### Logging Standards
- Pakai `Log::error('message', ['context' => $value])` dengan context
- Log channel `daily` untuk error tracking
- **JANGAN expose `$e->getMessage()` ke user** di production environment
- Di production, pakai generic message: "Terjadi kesalahan, silakan coba lagi"
- Di local/dev, boleh include error detail untuk debugging

```php
catch (\Exception $e) {
    DB::rollBack();

    Log::error('Failed to process check-in', [
        'user_id' => auth()->id(),
        'error'   => $e->getMessage(),
        'trace'   => $e->getTraceAsString(),
    ]);

    return response()->json([
        'success' => false,
        'message' => 'Gagal melakukan check-in. Silakan coba lagi.',
    ], 500);
}
```

---

## Database & Query Rules

### Eloquent Best Practices
- **WAJIB pakai `$fillable`** — HINDARI `$guarded = []` (security: mass-assignment vulnerability)
- Gunakan `$casts` untuk: dates, JSON, enums, booleans
- Eager loading WAJIB untuk relationship yang akan dipakai (hindari N+1 query)
- Pakai `select()` untuk query large table (ambil kolom yang perlu saja)
- Gunakan `whereHas()` dengan hati-hati (bisa lambat di table besar) — pertimbangkan join manual

### Query Conventions
- Gunakan query builder/Eloquent, JANGAN raw SQL kecuali perlu (misal aggregate kompleks)
- Index untuk kolom yang sering di-query (foreign keys, status, dates)
- Soft delete untuk data penting (attendance, leave_request) — pakai `SoftDeletes` trait
- Audit log untuk perubahan data critical (gunakan tabel `audit_logs`)

### Migration Rules
- Selalu sertakan `down()` method
- Foreign key dengan `onDelete` constraint yang jelas (`cascade`, `nullOnDelete`, `restrict`)
- Tambah index untuk: foreign keys, kolom search, kolom sort
- Comment kolom yang tidak obvious dengan `->comment('...')`
- Migration baru, jangan modify migration lama (kecuali masih dev awal)

### Multi-Tenancy Awareness
**PENTING**: Setiap query yang berhubungan dengan data perusahaan WAJIB filter by `company_id`.
User saat ini: hanya 1 perusahaan, tapi struktur sudah disiapkan untuk multi-tenant.

---

## Enums

Lokasi: `app/Enums/`

Pakai PHP 8.1+ Backed Enum untuk semua status/role/type.

```php
// app/Enums/UserRole.php
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
}
```

### Enum yang Wajib Dibuat
| Enum                  | Values                                                  |
|-----------------------|---------------------------------------------------------|
| `UserRole`            | `employee`, `manager`, `hr`, `admin`                    |
| `UserStatus`          | `active`, `inactive`                                    |
| `AttendanceStatus`    | `present`, `absent`, `leave`                            |
| `LeaveStatus`         | `pending`, `approved`, `rejected`                       |
| `PermissionType`      | `late_arrival`, `early_departure`, `out_of_office`      |
| `ApprovalDecision`    | `pending`, `approved`, `rejected`                       |
| `ApprovalRequestType` | `leave`, `permission`                                   |
| `NotificationType`    | `info`, `warning`, `error`                              |

### Aturan Penggunaan
- **JANGAN hardcode string status** di mana pun
- Cast di model: `'role' => UserRole::class`
- Validation: `Rule::enum(UserRole::class)`
- Comparison: `$user->role === UserRole::ADMIN`

---

## Authorization

### Approach
Custom middleware (tidak pakai Spatie Permission untuk simpler setup).

### Middleware
| Middleware      | Fungsi                                       |
|-----------------|----------------------------------------------|
| `auth:sanctum`  | Token validation                             |
| `role:admin,hr` | Multiple role allowed (whitelist)            |
| `active.user`   | Pastikan `status = 'active'`                 |

### Policy
Pakai Laravel Policy untuk model-level authorization:
- `LeaveRequestPolicy::view(User $user, LeaveRequest $leave)` — bisa lihat?
- `LeaveRequestPolicy::approve(User $user, LeaveRequest $leave)` — bisa approve?
- `AttendancePolicy::update(User $user, Attendance $attendance)` — bisa edit?

### Aturan Akses Data
| Role       | Lihat Data                                  | Modify Data                  |
|------------|---------------------------------------------|------------------------------|
| `employee` | Hanya milik sendiri                         | Hanya milik sendiri          |
| `manager`  | Tim (department dia + dia sebagai manager)  | Approve cuti tim             |
| `hr`       | Semua karyawan                              | Master data karyawan         |
| `admin`    | Semua data                                  | Semua data + setting         |

---

## Repository Method Naming Convention

### Read Methods
| Method                          | Return Type          | Example                              |
|---------------------------------|----------------------|--------------------------------------|
| `find($id)`                     | `?Model`             | `find(5)`                            |
| `findOrFail($id)`               | `Model` (throw 404)  | `findOrFail(5)`                      |
| `findBy{Field}($value)`         | `?Model`             | `findByEmail('x@y.com')`             |
| `getAll()`                      | `Collection`         | `getAll()`                           |
| `getBy{Filter}()`               | `Collection`         | `getByUser(User $user)`              |
| `paginate{X}($filters)`         | `LengthAwarePaginator` | `paginateUserHistory($filters)`    |

### Write Methods
| Method                          | Return Type          | Example                              |
|---------------------------------|----------------------|--------------------------------------|
| `create(array $data)`           | `Model`              | `create(['name' => 'Budi'])`         |
| `update($model, array $data)`   | `Model`              | `update($user, ['name' => 'Budi'])`  |
| `delete($model)`                | `bool`               | `delete($user)`                      |

### Aggregate Methods
| Method                          | Return Type          | Example                              |
|---------------------------------|----------------------|--------------------------------------|
| `count{X}()`                    | `int`                | `countActiveUsers()`                 |
| `exists{X}()`                   | `bool`               | `existsForUserOnDate(1, '2026-04-30')` |
| `sum{X}()`, `average{X}()`      | `int`/`float`        | `sumWorkDuration($userId, $month)`   |

---

## Testing

Framework: **PestPHP 4**

### File Naming
- Test files: `{Feature}Test.php` (e.g., `AttendanceCheckInTest.php`)
- Test methods: deskriptif dalam English

```php
it('returns 422 when location is outside office radius', function () {
    // arrange
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    // act
    $response = $this->withToken($token)
        ->postJson('/api/v1/attendance/check-in', [
            'latitude'  => 0,
            'longitude' => 0,
        ]);

    // assert
    $response->assertStatus(422);
});
```

### Coverage Target
Setiap API endpoint WAJIB punya test untuk:
- ✅ Happy path (success case)
- ✅ Validation error (invalid input)
- ✅ Authorization error (wrong role)
- ✅ Business logic error (e.g., already clocked in)

### Test Structure
```
tests/
├── Feature/
│   ├── Api/
│   │   ├── Auth/
│   │   │   └── LoginTest.php
│   │   ├── Attendance/
│   │   │   ├── CheckInTest.php
│   │   │   └── CheckOutTest.php
│   │   └── Leave/
│   │       └── StoreLeaveRequestTest.php
│   └── Web/
└── Unit/
    ├── Repositories/
    │   └── AttendanceRepositoryTest.php
    └── Helpers/
        └── GeolocationHelperTest.php
```

### Database Setup
- Pakai `RefreshDatabase` trait
- Factory untuk semua model
- Seed minimum data yang dibutuhkan saja
- Setiap test harus independent (no shared state)

---

## Performance Guidelines

### Query Optimization
- Use `chunk()` for processing large datasets (>1000 rows)
- Use `cursor()` for read-only iteration
- Cache static/rarely-changed data (departments, leave types) dengan `Cache::remember`

```php
$departments = Cache::remember('departments.all', 3600, function () {
    return Department::all();
});
```

### File Upload
- Resize image before save (max 1024px untuk selfie) — pakai Intervention Image
- Compress to JPEG quality 80
- Use queue for heavy processing (face recognition, image processing)

### API Response
- Pagination default: 15 per page, max: 100
- Lazy loading untuk relationship besar
- Field filtering: `?fields=id,name,email` (opsional)

---

## Security

### API Security
- Rate limiting: 60 req/min untuk authenticated, 10 req/min untuk login endpoint
- Token expiration: 30 hari (configurable di `config/sanctum.php`)
- CORS: hanya allow origin yang terdaftar (production)
- HTTPS only di production environment

### Data Security
- Hash password dengan bcrypt (default Laravel)
- **JANGAN log password, token, atau data sensitif** (PII)
- Mask data di response (e.g., phone number: `081***5678`)
- File upload: validate MIME type (jangan pakai extension saja)
- Sanitize filename sebelum simpan

### Anti-Fraud (Khusus Aplikasi Absensi)
- **Detect mock location** → flag attendance dengan `is_mock_location = true`
- Face recognition untuk verifikasi check-in (`face_verified` flag)
- Device binding: 1 user 1 device aktif (lihat tabel `devices`)
- Audit log untuk action critical: check-in/out, approve/reject

### Security Checklist sebelum Deploy
- [ ] `APP_DEBUG=false` di production
- [ ] `APP_ENV=production`
- [ ] Database password kuat
- [ ] CORS configured properly
- [ ] HTTPS enforced
- [ ] Rate limiting aktif
- [ ] File upload validation strict
- [ ] No `dd()` / `dump()` / `var_dump()` di code

---

## Git Workflow

### Branch Naming
- `feature/auth-login-api` — fitur baru
- `fix/attendance-duplicate-checkin` — bug fix
- `refactor/repository-pattern` — refactor tanpa ubah behavior
- `docs/update-claude-md` — dokumentasi
- `chore/update-dependencies` — maintenance

### Commit Message (Conventional Commits)
- `feat: add clock-in API endpoint`
- `fix: prevent duplicate attendance on same day`
- `refactor: extract geolocation logic to helper`
- `test: add validation tests for leave request`
- `docs: update CLAUDE.md with API standards`
- `chore: bump laravel to 12.5`

### PR Rules
- Minimal 1 reviewer
- Semua test harus pass (`php artisan test`)
- No commented-out code
- Update CLAUDE.md jika ada konvensi baru

---

## Commands

```bash
# Dev (all-in-one: server + queue + log + vite)
composer dev

# Individual
php artisan serve
npm run dev
php artisan queue:listen --tries=1

# Database
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed

# Testing
php artisan test
php artisan test --filter=AttendanceCheckInTest
./vendor/bin/pest --coverage

# Cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Generate
php artisan make:controller Api/AttendanceController
php artisan make:request Api/CheckInRequest
php artisan make:resource Api/AttendanceResource
php artisan make:enum UserRole       # custom command (kalau dibuat)
```

---

## Notes Penting

### Soal `$guarded = []`
**SECURITY ISSUE**: Semua model existing masih pakai `$guarded = []` (mass-assignment vulnerability).
WAJIB diganti dengan `$fillable = [...]` yang explicit saat implementasi setiap fitur.

### Konvensi Field Name
- Gunakan `full_name` bukan `name` di model `User`
- Gunakan `work_date` bukan `date` di `Attendance`
- Konsistensi: `check_in_*` untuk masuk, `check_out_*` untuk pulang

### Multi-Tenancy
Setiap query data perusahaan WAJIB filter by `company_id`. Saat ini single-tenant, tapi struktur sudah disiapkan untuk multi-tenant di masa depan.

### Face Recognition
- `face_embedding` menyimpan vector embedding wajah (JSON) untuk verifikasi wajah saat check-in
- Verifikasi dilakukan di mobile app (Flutter) → kirim hasil ke API via flag `face_verified`

### Anti GPS-Spoofing
- `is_mock_location` di `attendances` adalah flag anti-fraud untuk deteksi GPS spoofing dari aplikasi mobile
- Flutter app harus deteksi mock location via plugin (e.g., `flutter_jailbreak_detection`)

### Multi-Level Approval
- `approvals` mendukung multi-level approval (`level` column)
- Default flow: Manager (level 1) → HR (level 2)
- Status final di `leave_requests.status` di-update setelah semua level approved

### Push Notification
- `devices` menyimpan FCM token untuk push notification ke aplikasi Flutter
- Token bisa multiple per user (multi-device)
- Hapus FCM token saat logout

### Soal Notifications Table
- `notifications` table tidak memiliki FK constraint pada `user_id` (by design di migration)
- Tetap query manual: `Notification::where('user_id', $userId)`

---

## Important for Claude Code

### Workflow Rules
1. **SELALU tunjukkan plan dulu** sebelum membuat/mengubah multiple file
2. **TANYA dulu** kalau ada ambiguitas atau keputusan teknis penting
3. **JANGAN install package** tanpa konfirmasi user
4. **JANGAN buat file besar** (>200 baris) tanpa minta approval bertahap
5. **CONFIRM struktur folder** kalau berbeda dari yang ada di CLAUDE.md

### Saat Membuat Fitur Baru
Ikuti urutan ini:
1. Migration (jika perlu kolom baru)
2. Model — update relationships, casts, fillable
3. Enum (jika ada status/type baru)
4. Repository Interface
5. Repository Implementation
6. Bind di RepositoryServiceProvider
7. Form Request
8. Resource
9. Controller
10. Route
11. Test (Pest) — minimum 4 test case
12. Update CLAUDE.md jika ada konvensi/keputusan baru

### Saat Modifikasi File
1. **BACA file lengkap dulu** — jangan asal edit berdasarkan asumsi
2. Pahami pattern yang sudah ada
3. Ikuti style yang sudah ada (jangan introduce style baru tanpa diskusi)
4. Cek dependency lain yang mungkin terpengaruh
5. Run test setelah modifikasi

### Default Behavior Preferences
- **Bahasa**: Indonesian untuk komunikasi & user message, English untuk code/comment
- **Verbose**: Tampilkan reasoning singkat sebelum action
- **Approval-first**: Lebih baik konfirmasi dulu daripada redo
- **Incremental**: Pecah task besar jadi step-by-step

### Forbidden Actions
- ❌ JANGAN modify migration yang sudah ada (buat migration baru jika perlu ubah schema)
- ❌ JANGAN ubah struktur folder tanpa konfirmasi
- ❌ JANGAN auto-format file yang tidak terkait dengan task
- ❌ JANGAN delete data dummy seeder yang sudah ada
- ❌ JANGAN pakai `$guarded = []` di model baru
- ❌ JANGAN hardcode string status (pakai Enum)
- ❌ JANGAN pakai raw SQL kecuali sangat perlu
- ❌ JANGAN expose `$e->getMessage()` ke user di production
- ❌ JANGAN commit `.env`, `vendor/`, `node_modules/`
- ❌ JANGAN pakai `dd()`, `dump()`, `var_dump()` di code yang akan di-commit

### When in Doubt
Tanyakan ke user dengan format:
```
Saya ada beberapa opsi untuk [task]:
1. Opsi A: [pros & cons]
2. Opsi B: [pros & cons]

Saya rekomendasikan Opsi A karena [reason].
Apakah setuju?
```

---

## API Plan

> Bagian ini akan diupdate setelah `PROJECT_BLUEPRINT.md` tersedia.
> Paste konten blueprint API ke file `PROJECT_BLUEPRINT.md`, lalu jalankan prompt update.

### Planned Endpoints (High Level)

```
POST   /api/v1/auth/login
POST   /api/v1/auth/logout              [auth]
GET    /api/v1/auth/me                  [auth]

GET    /api/v1/attendance/today         [auth]
POST   /api/v1/attendance/check-in      [auth]
POST   /api/v1/attendance/check-out     [auth]
GET    /api/v1/attendance/history       [auth]

GET    /api/v1/leaves                   [auth]
POST   /api/v1/leaves                   [auth]
GET    /api/v1/leaves/{id}              [auth]
DELETE /api/v1/leaves/{id}              [auth]
GET    /api/v1/leave-types              [auth]
GET    /api/v1/leave-balances           [auth]

POST   /api/v1/permissions              [auth]
GET    /api/v1/permissions              [auth]

GET    /api/v1/approvals/pending        [auth, role:manager,hr]
POST   /api/v1/approvals/{id}/approve   [auth, role:manager,hr]
POST   /api/v1/approvals/{id}/reject    [auth, role:manager,hr]

GET    /api/v1/notifications            [auth]
POST   /api/v1/notifications/{id}/read  [auth]

GET    /api/v1/profile                  [auth]
PUT    /api/v1/profile                  [auth]
PUT    /api/v1/profile/password         [auth]
POST   /api/v1/profile/face-embedding   [auth]

POST   /api/v1/devices/register         [auth]
DELETE /api/v1/devices/{id}             [auth]
```

---

## Changelog

| Date       | Author | Change                                          |
|------------|--------|-------------------------------------------------|
| 2026-04-30 | Init   | Initial CLAUDE.md created                       |
| 2026-04-30 | Update | Added coding conventions, error handling, etc. |

## Documentation Files

- `BLUEPRINT.md` — Detail kontrak API (untuk mobile app)
- `ADMIN_BLUEPRINT.md` — Detail panel admin web (Livewire)

Always refer to these blueprints when implementing features.
Update them when contract changes.
