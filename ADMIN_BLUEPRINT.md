# ADMIN_BLUEPRINT.md — Prima Attendances Admin Panel

> Dokumen ini adalah **single source of truth** untuk panel admin web (Livewire + Volt).
> Panel admin **REUSE Repository** yang sama dengan API — TIDAK duplikasi business logic.
> Refer ke `BLUEPRINT.md` untuk kontrak data dan business logic detail.

---

## 🎯 Filosofi Arsitektur

### Reuse Pattern

```
                          ┌─→ API Controller (Mobile App)
Repository / Service ─────┤
                          └─→ Livewire Component (Admin Panel)
```

**Aturan Wajib:**
- ✅ Livewire component **WAJIB** inject Repository/Service via constructor
- ✅ Reuse business logic dari Service yang sama dengan API
- ❌ **JANGAN** call API endpoint dari Livewire (anti-pattern)
- ❌ **JANGAN** duplikasi logic check-in/approval/dll di Livewire

### Auth Strategy

| Layer       | Auth Method                            |
|-------------|----------------------------------------|
| API (Mobile)| Sanctum token (`auth:sanctum`)         |
| Admin Panel | Session-based (`auth` web middleware)  |

Login admin pakai **session** dari Breeze yang sudah ada (`/login` route web).

---

## 🚪 Access Control

### Role Access Matrix

| Halaman / Fitur          | employee | manager | hr  | admin |
|--------------------------|----------|---------|-----|-------|
| Login ke /admin          | ❌       | ✅      | ✅  | ✅    |
| Dashboard                | ❌       | ✅      | ✅  | ✅    |
| Lihat absensi tim        | ❌       | ✅      | ✅  | ✅    |
| Lihat absensi semua      | ❌       | ❌      | ✅  | ✅    |
| Manual entry attendance  | ❌       | ❌      | ✅  | ✅    |
| Approval cuti/izin       | ❌       | ✅ (tim)| ✅  | ✅    |
| CRUD karyawan            | ❌       | ❌      | ✅  | ✅    |
| Master data              | ❌       | ❌      | ✅  | ✅    |
| Reports                  | ❌       | ✅ (tim)| ✅  | ✅    |
| Settings perusahaan      | ❌       | ❌      | ❌  | ✅    |
| User management & roles  | ❌       | ❌      | ❌  | ✅    |
| Audit logs               | ❌       | ❌      | ❌  | ✅    |

### Route Middleware

```php
// routes/web.php
Route::middleware(['auth', 'verified', 'admin.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // ... admin routes
    });
```

### Custom Middleware

| Middleware       | Fungsi                                                         |
|------------------|----------------------------------------------------------------|
| `admin.access`   | Block role `employee`, allow `manager`, `hr`, `admin`          |
| `role:admin`     | Hanya admin                                                    |
| `role:hr,admin`  | HR dan admin                                                   |
| `log.activity`   | Auto log ke `audit_logs` (apply ke route mutating)            |

---

## 🎨 Layout & Navigation

### Layout File

**File:** `resources/views/layouts/admin.blade.php`

**Struktur:**
```
┌────────────────────────────────────────────────┐
│ Top Navbar (search, notif, profile dropdown)   │
├────────────┬───────────────────────────────────┤
│            │ Breadcrumb                        │
│  Sidebar   ├───────────────────────────────────┤
│            │                                   │
│  - Menu 1  │ Page Header (title + actions)    │
│  - Menu 2  │                                   │
│  - Menu 3  │                                   │
│            │ Page Content                      │
│            │                                   │
│            │                                   │
│            │                                   │
└────────────┴───────────────────────────────────┘
```

### Sidebar Menu Structure

```
Dashboard                              [Home]

ABSENSI
├── Daftar Absensi                     [Clock]
├── Manual Entry                       [PlusCircle] (hr, admin)
└── Kalender                           [Calendar]

PENGAJUAN
├── Cuti                               [Plane]
├── Izin                               [FileText]
└── Approval Center        [3 badge]   [CheckCircle] (manager+)

KARYAWAN                               (hr, admin)
├── Daftar Karyawan                    [Users]
└── Departemen                         [Building]

MASTER DATA                            (hr, admin)
├── Lokasi Kantor                      [MapPin]
├── Shift                              [Clock]
├── Jenis Cuti                         [Tag]
└── Hari Libur                         [Calendar]

LAPORAN
├── Absensi                            [BarChart]
├── Cuti                               [PieChart]
├── Keterlambatan                      [AlertCircle]
└── Jam Kerja                          [Clock]

PENGATURAN                             (admin)
├── Perusahaan                         [Settings]
├── User & Role                        [Shield]
└── Audit Log                          [Activity]
```

**Behavior:**
- Active state: highlight menu yang sesuai current route
- Auto-expand parent menu kalau child active
- Collapsible sidebar (icon-only mode)
- Mobile: slide-in drawer
- Permission-aware: menu yang user tidak punya akses → hidden

### Top Navbar

**Komponen:**
- **Logo + nama perusahaan** (kiri)
- **Search global** (Ctrl+K) — search karyawan, absensi
- **Notification bell** dengan badge unread count
- **Profile dropdown**:
  - Profile saya
  - Ganti password
  - Logout

### Breadcrumb

Auto-generate dari route name:
```
Dashboard / Karyawan / Edit Budi Santoso
```

---

## 🧩 Reusable UI Components

Lokasi: `resources/views/components/admin/` dan `resources/views/components/ui/`

### Component List

| Component             | Path                                 | Usage                          |
|-----------------------|--------------------------------------|--------------------------------|
| `<x-admin.sidebar>`   | components/admin/sidebar.blade.php   | Sidebar utama                  |
| `<x-admin.navbar>`    | components/admin/navbar.blade.php    | Top navbar                     |
| `<x-admin.breadcrumb>`| components/admin/breadcrumb.blade.php| Breadcrumb                     |
| `<x-admin.page-header>`| components/admin/page-header...     | Title + action buttons         |
| `<x-admin.stat-card>` | components/admin/stat-card.blade.php | Card statistik dashboard       |
| `<x-admin.data-table>`| components/admin/data-table.blade.php| Reusable table                 |
| `<x-admin.empty-state>`| components/admin/empty-state...     | UI saat data kosong            |
| `<x-admin.confirm-modal>`| components/admin/confirm-modal... | Confirmation dialog            |
| `<x-ui.button>`       | components/ui/button.blade.php       | Button primary/secondary/danger|
| `<x-ui.input>`        | components/ui/input.blade.php        | Input field with label & error |
| `<x-ui.select>`       | components/ui/select.blade.php       | Select dropdown                |
| `<x-ui.textarea>`     | components/ui/textarea.blade.php     | Textarea                       |
| `<x-ui.badge>`        | components/ui/badge.blade.php        | Status badge (color variants)  |
| `<x-ui.card>`         | components/ui/card.blade.php         | Generic card container         |
| `<x-ui.modal>`        | components/ui/modal.blade.php        | Generic modal                  |
| `<x-ui.dropdown>`     | components/ui/dropdown.blade.php     | Dropdown menu                  |
| `<x-ui.avatar>`       | components/ui/avatar.blade.php       | User avatar with fallback      |
| `<x-ui.spinner>`      | components/ui/spinner.blade.php      | Loading spinner                |
| `<x-ui.toast>`        | components/ui/toast.blade.php        | Toast notification             |

### Badge Variants (untuk status)

| Status                    | Color (Tailwind)                         |
|---------------------------|------------------------------------------|
| `present`, `approved`     | `bg-green-100 text-green-800`            |
| `pending`                 | `bg-yellow-100 text-yellow-800`          |
| `late`                    | `bg-orange-100 text-orange-800`          |
| `absent`, `rejected`      | `bg-red-100 text-red-800`                |
| `leave`                   | `bg-blue-100 text-blue-800`              |
| `inactive`, `cancelled`   | `bg-gray-100 text-gray-800`              |

### Toast Notification Pattern

Pakai **Livewire dispatch** + **AlpineJS listener**:
```php
// In Livewire component
$this->dispatch('toast', type: 'success', message: 'Data berhasil disimpan');
```

---

## 📄 Halaman Detail (Per Modul)

---

## 1. 🏠 Dashboard

**Route:** `GET /admin/dashboard`
**Component:** `App\Livewire\Admin\Dashboard\Index`
**View:** `resources/views/livewire/admin/dashboard/index.blade.php`
**Access:** All admin roles

### Layout Sections

```
┌─────────────────────────────────────────────────┐
│ Welcome banner: "Halo, {nama}!"                 │
├─────────────────────────────────────────────────┤
│ Stat Cards (4 cards horizontal)                 │
│ [Total Karyawan] [Hadir Hari Ini] [Telat] [Cuti]│
├──────────────────────────┬──────────────────────┤
│                          │ Pending Approvals     │
│ Attendance Chart         │ (saya yang approve)   │
│ (7 hari terakhir)        │ - List 5 terbaru      │
│                          │ - Link "Lihat semua"  │
├──────────────────────────┴──────────────────────┤
│ Recent Activity (audit log feed)                │
│ Quick Actions                                   │
└─────────────────────────────────────────────────┘
```

### Data yang Ditampilkan

**Stat Cards:**
- Total karyawan aktif (filter: `status = active`, `company_id = current company`)
- Hadir hari ini (count `attendances where work_date = today AND check_in IS NOT NULL`)
- Terlambat hari ini (count `attendances where work_date = today AND check_in_time > shift.start_time`)
- Cuti hari ini (count `leave_requests where status = approved AND today between start_date AND end_date`)

**Chart:**
- Library: ApexCharts atau Chart.js
- Type: Line/Bar chart
- X-axis: 7 hari terakhir
- Series: Hadir, Terlambat, Tidak Hadir

**Pending Approvals:**
- List approval dimana `approver_id = current user` AND `decision = pending`
- Limit 5, urut by oldest first

**Recent Activity:**
- Last 10 entries dari `audit_logs`
- Filter berdasarkan `company_id` (kalau multi-tenant)

### Repository Calls

```php
public function __construct(
    protected AttendanceRepositoryInterface $attendanceRepo,
    protected ApprovalRepositoryInterface $approvalRepo,
    protected UserRepositoryInterface $userRepo,
    protected AuditLogRepositoryInterface $auditRepo,
) {}

public function mount(): void
{
    $this->stats = [
        'total_employees' => $this->userRepo->countActive(),
        'present_today'   => $this->attendanceRepo->countPresentToday(),
        'late_today'      => $this->attendanceRepo->countLateToday(),
        'on_leave_today'  => $this->leaveRepo->countOnLeaveToday(),
    ];

    $this->chartData = $this->attendanceRepo->getLast7DaysSummary();
    $this->pendingApprovals = $this->approvalRepo->getPendingByApprover(auth()->id(), 5);
    $this->recentActivity = $this->auditRepo->getRecent(10);
}
```

---

## 2. ⏰ Attendance Module

### 2.1 List Absensi

**Route:** `GET /admin/attendances`
**Component:** `App\Livewire\Admin\Attendance\Index`
**Access:** Manager (tim), HR, Admin

#### Filters

```
┌──────────────────────────────────────────────────────────┐
│ [Tanggal: 2026-04-30] [Departemen: Semua▾] [Status▾]    │
│ [Search nama/employee_id...] [☐ Late only] [☐ Mock loc]  │
│ [Reset] [Export Excel] [Export PDF]                      │
└──────────────────────────────────────────────────────────┘
```

**Filter fields:**
- Date range (default: hari ini)
- Department (multi-select)
- Status: All, Present, Absent, Leave
- Search: nama atau employee_id
- Late only checkbox
- Mock location only checkbox

#### Table Columns

| # | Column           | Sortable | Notes                                  |
|---|------------------|----------|----------------------------------------|
| 1 | Tanggal          | ✅       | YYYY-MM-DD                             |
| 2 | NIK              | ✅       | employee_id                            |
| 3 | Nama             | ✅       | full_name + avatar                     |
| 4 | Departemen       | ✅       |                                        |
| 5 | Check-in         | ✅       | Time + late badge kalau telat          |
| 6 | Check-out        | ✅       | Time atau "-" kalau belum              |
| 7 | Durasi           | ✅       | Work duration formatted                |
| 8 | Status           | -        | Badge (present/late/absent/leave)      |
| 9 | Lokasi           | -        | Office location name                   |
|10 | Flags            | -        | Mock 🚨, Face ✓                       |
|11 | Aksi             | -        | View, Edit (HR only)                   |

#### Actions

- **View**: Buka modal/halaman detail
- **Edit**: Hanya HR/admin, untuk koreksi
- **Bulk export**: Pilih multiple rows → export

#### Permission Logic

```php
// Manager: hanya tim sendiri (department dia + dia sebagai manager)
// HR/Admin: semua karyawan di company

if ($user->role === UserRole::MANAGER) {
    $query->whereHas('user', fn($q) => $q->where('department_id', $user->department_id));
}
```

---

### 2.2 Detail Absensi

**Route:** `GET /admin/attendances/{id}`
**Component:** `App\Livewire\Admin\Attendance\Show`

#### Layout

```
┌─────────────────────────────────────────────────┐
│ Header: Budi Santoso - 30 April 2026 [Edit btn] │
├──────────────────────────────────┬──────────────┤
│ Check-In Section                 │ Check-Out    │
│ ┌──────────┐ Time: 08:15:30      │ Time: 17:30  │
│ │  Selfie  │ Status: Late (15m)  │ Status: OK   │
│ │  Photo   │ Location: HQ Medan  │              │
│ └──────────┘ [View on Map]       │              │
│              Mock: ✗  Face: ✓    │              │
├──────────────────────────────────┴──────────────┤
│ Map Preview (Leaflet)                           │
│ - Marker: check-in location                     │
│ - Marker: check-out location                    │
│ - Circle: office radius                         │
├─────────────────────────────────────────────────┤
│ Notes Section                                   │
├─────────────────────────────────────────────────┤
│ Audit Trail (siapa edit, kapan, perubahan apa)  │
└─────────────────────────────────────────────────┘
```

#### Map Implementation

- Library: **Leaflet.js** (gratis)
- Tile layer: OpenStreetMap
- Markers: check-in (green), check-out (red), office (blue)
- Circle overlay: radius kantor

---

### 2.3 Manual Entry

**Route:** `GET /admin/attendances/manual-entry`
**Component:** `App\Livewire\Admin\Attendance\ManualEntry`
**Access:** HR, Admin only

#### Form

```
┌──────────────────────────────────────┐
│ Karyawan: [Search dropdown]          │
│ Tanggal:  [Date picker]              │
│ Lokasi:   [Office dropdown]          │
│ ─────────────────────────────────────│
│ Check-in time:  [Time input]         │
│ Check-out time: [Time input]         │
│ ─────────────────────────────────────│
│ Status: [present/absent/leave]       │
│ Catatan: [Textarea] (alasan koreksi) │
│                                      │
│ [Cancel] [Simpan]                    │
└──────────────────────────────────────┘
```

#### Business Logic

1. Validate input
2. Cek apakah sudah ada attendance untuk user + date → kalau ada, update; kalau belum, create
3. Wajib ada `notes` (alasan koreksi manual)
4. Log audit dengan `metadata` lengkap (before/after values)

---

## 3. 🏖️ Leave Module

### 3.1 List Cuti

**Route:** `GET /admin/leaves`
**Component:** `App\Livewire\Admin\Leave\Index`

#### Filters

```
[Status▾] [Jenis▾] [Departemen▾] [Periode▾] [Search...]
```

#### Table Columns

| # | Column          | Notes                                |
|---|-----------------|--------------------------------------|
| 1 | Tgl Pengajuan   | submitted_at                         |
| 2 | NIK / Nama      |                                      |
| 3 | Departemen      |                                      |
| 4 | Jenis Cuti      | Cuti Tahunan / Sakit / dll           |
| 5 | Tgl Cuti        | start_date - end_date                |
| 6 | Total Hari      |                                      |
| 7 | Status          | Badge                                |
| 8 | Approval        | Progress: Lvl1✓ / Lvl2◯              |
| 9 | Aksi            | View, Approve, Reject (jika berhak)  |

---

### 3.2 Detail Cuti

**Route:** `GET /admin/leaves/{id}`
**Component:** `App\Livewire\Admin\Leave\Show`

#### Layout

```
┌──────────────────────────────────────────────┐
│ Header: Pengajuan Cuti #45                   │
├──────────────────────────────────────────────┤
│ Pemohon Info                                 │
│ - Nama, NIK, Departemen, Jabatan             │
├──────────────────────────────────────────────┤
│ Detail Pengajuan                             │
│ - Jenis cuti, Tanggal, Total hari, Alasan    │
│ - Lampiran (kalau ada) [Download]            │
├──────────────────────────────────────────────┤
│ Riwayat Approval                             │
│ ◉ Level 1 - Manager IT (Approved 2 jam lalu) │
│ ◯ Level 2 - HR Manager (Pending)             │
├──────────────────────────────────────────────┤
│ Action Buttons (jika current user approver)  │
│ [Tolak] [Setujui]                            │
└──────────────────────────────────────────────┘
```

#### Approval Flow

Klik tombol "Setujui" → modal:
```
┌─────────────────────────────┐
│ Setujui Pengajuan?          │
├─────────────────────────────┤
│ Komentar (opsional):        │
│ [Textarea]                  │
│                             │
│ [Batal] [Setujui]           │
└─────────────────────────────┘
```

Klik "Tolak" → modal serupa, tapi komentar **wajib**.

---

### 3.3 Calendar View

**Route:** `GET /admin/leaves/calendar`
**Component:** `App\Livewire\Admin\Leave\Calendar`

#### Layout

Calendar bulanan dengan event = orang yang cuti:
```
April 2026

Mon  Tue  Wed  Thu  Fri  Sat  Sun
                            1    2    3
              ┌──Budi──┐
4    5    6    7    8    9    10
       ┌──Ani──────┐
11   12   13   14   15   16   17
...
```

- Library: **FullCalendar.js** atau custom
- Click event → buka detail leave
- Filter by department

---

### 3.4 Leave Balances

**Route:** `GET /admin/leaves/balances`
**Component:** `App\Livewire\Admin\Leave\Balances`
**Access:** HR, Admin

#### Table

| Karyawan | Cuti Tahunan | Cuti Sakit | Cuti Melahirkan | Cuti Tanpa Bayar | Aksi |
|----------|--------------|------------|-----------------|------------------|------|
| Budi     | 9/12         | 10/10      | 90/90           | 0/0              | Edit |

- Klik **Edit** → modal untuk adjust kuota tahun ini
- Bulk reset kuota di awal tahun

---

## 4. 📝 Permission Module

Mirip dengan Leave, struktur sama:
- `/admin/permissions` — list
- `/admin/permissions/{id}` — detail + approval

Perbedaan: tidak ada balance management.

---

## 5. ✅ Approval Center

**Route:** `GET /admin/approvals`
**Component:** `App\Livewire\Admin\Approval\Index`
**Access:** Manager, HR, Admin

### Layout

```
┌──────────────────────────────────────────────┐
│ Tabs: [Pending (5)] [Approved] [Rejected] [All] │
├──────────────────────────────────────────────┤
│ Filter: [Type▾: All/Leave/Permission]        │
│         [Department▾]                        │
├──────────────────────────────────────────────┤
│ List dengan card-style:                      │
│ ┌──────────────────────────────────────────┐ │
│ │ ☐ Budi Santoso - Cuti Tahunan            │ │
│ │   3 hari (10-12 Mei 2026)                │ │
│ │   Diajukan 2 jam lalu                    │ │
│ │              [Detail] [Tolak] [Setujui]  │ │
│ └──────────────────────────────────────────┘ │
├──────────────────────────────────────────────┤
│ Bulk Actions:                                │
│ [☐ Pilih semua] [Setujui Terpilih] [Tolak]   │
└──────────────────────────────────────────────┘
```

### Bulk Approval

Pilih multiple → approve sekaligus dengan konfirmasi.
Gunakan transaction supaya consistent.

---

## 6. 👥 Employee Management

### 6.1 List Karyawan

**Route:** `GET /admin/employees`
**Component:** `App\Livewire\Admin\Employee\Index`
**Access:** HR, Admin

#### Filters

```
[Search nama/email/NIK] [Departemen▾] [Role▾] [Status▾]
[+ Tambah Karyawan] [Import Excel] [Export]
```

#### Table

| # | NIK | Foto | Nama | Email | Departemen | Role | Status | Aksi |
|---|-----|------|------|-------|------------|------|--------|------|
| 1 | EMP001 | 👤 | Budi | budi@... | IT | Employee | Active | View/Edit |

**Aksi dropdown:**
- View detail
- Edit
- Reset password
- Activate / Deactivate
- Delete (soft delete)

---

### 6.2 Form Karyawan (Create/Edit)

**Route:** `GET /admin/employees/create` & `/admin/employees/{id}/edit`
**Component:** `App\Livewire\Admin\Employee\Form`

#### Form Fields

```
┌─ DATA PRIBADI ──────────────────────────┐
│ Foto Profile: [Upload] (preview)        │
│ Nama Lengkap: [Input]                   │
│ NIK:          [Input] (auto-generate?)  │
│ Email:        [Input]                   │
│ Phone:        [Input]                   │
└─────────────────────────────────────────┘

┌─ DATA KEPEGAWAIAN ──────────────────────┐
│ Departemen:        [Select]             │
│ Role:              [Select]             │
│ Lokasi Kantor:     [Select]             │
│ Manager:           [Search dropdown]    │
│ Status:            [Active/Inactive]    │
└─────────────────────────────────────────┘

┌─ AKUN ──────────────────────────────────┐
│ Password:           [Input] (create only)│
│ Konfirmasi Pass:    [Input]             │
└─────────────────────────────────────────┘

[Batal] [Simpan]
```

#### Validation

- `full_name`: required, max:150
- `email`: required, email, unique:users
- `employee_id`: required, unique:users
- `role`: required, in: enum values
- `department_id`: required, exists
- `password`: required (create), min:8
- `photo`: nullable, image, max:2048

---

### 6.3 Detail Karyawan

**Route:** `GET /admin/employees/{id}`

#### Tabs

```
[Info Pribadi] [Riwayat Absensi] [Riwayat Cuti] [Devices] [Audit]
```

- **Info**: Read-only profile data
- **Riwayat Absensi**: Mini calendar + summary bulan ini
- **Riwayat Cuti**: List leave requests
- **Devices**: List devices yang pernah login (revoke option)
- **Audit**: Activity log untuk user ini

---

### 6.4 Import Karyawan

**Route:** Modal di `/admin/employees`

#### Flow

1. Download template Excel
2. Upload Excel
3. Preview parsing (validate per row)
4. Kalau ada error → show row mana, error apa
5. Kalau OK semua → confirm import
6. Background job untuk import (kalau >100 rows)

**Excel Columns:**
```
employee_id | full_name | email | phone | department | role | manager_email
```

---

## 7. 🏢 Master Data

### 7.1 Departments

**Route:** `GET /admin/master/departments`
**Component:** `App\Livewire\Admin\Master\Departments`

#### Table

| Nama | Manager | Jumlah Karyawan | Aksi |
|------|---------|-----------------|------|
| IT   | Budi    | 12              | Edit/Delete |

#### Form (Modal)

```
Nama Departemen: [Input]
Manager:         [Search dropdown]
[Batal] [Simpan]
```

---

### 7.2 Office Locations (PENTING — Map Picker!)

**Route:** `GET /admin/master/office-locations`
**Component:** `App\Livewire\Admin\Master\OfficeLocations`

#### Form

```
┌──────────────────────────────────────────┐
│ Nama Lokasi: [Input]                     │
│ Alamat:      [Textarea]                  │
│ ─────────────────────────────────────────│
│ Pilih Lokasi di Map:                     │
│ ┌──────────────────────────────────────┐ │
│ │                                      │ │
│ │     [Map dengan marker draggable]    │ │
│ │     [Circle radius preview]          │ │
│ │                                      │ │
│ └──────────────────────────────────────┘ │
│ Latitude:  [Input] (auto-fill from map)  │
│ Longitude: [Input] (auto-fill from map)  │
│ Radius (m): [Input: 100]                 │
│ ─────────────────────────────────────────│
│ Status: ☐ Active                         │
│ [Batal] [Simpan]                         │
└──────────────────────────────────────────┘
```

#### Map Picker Implementation

- Library: **Leaflet.js**
- Click on map → set marker
- Drag marker → update lat/lng
- Auto-update radius circle saat radius diubah
- Search box (geocoding via Nominatim — free)

---

### 7.3 Shifts

**Route:** `GET /admin/master/shifts`

#### Form

```
Nama Shift:        [Input] (e.g., Shift Pagi)
Jam Mulai:         [Time input]
Jam Selesai:       [Time input]
Istirahat (menit): [Number] (default: 60)

Visualisasi:
[========|====break====|=========]
 08:00   12:00       13:00     17:00
```

---

### 7.4 Leave Types

**Route:** `GET /admin/master/leave-types`

#### Form

```
Nama:                    [Input]
Kuota Default (hari):    [Number]
Wajib Lampiran:          [☐ Checkbox]
Berbayar:                [☐ Checkbox]
```

---

### 7.5 Holidays

**Route:** `GET /admin/master/holidays`

#### Features

- Calendar view: tampilkan hari libur per bulan
- Add manual: tanggal, nama, is_national
- **Import dari API** (kalibrasi.com / dayoff.id) untuk auto-load hari libur Indonesia
- Recurring holidays (e.g., 17 Agustus tiap tahun)

---

## 8. 📅 Shift Assignment

**Route:** `GET /admin/shift-assignments`
**Component:** `App\Livewire\Admin\Shift\Calendar`
**Access:** HR, Admin

### Calendar View

```
                  April 2026
        ┌──────────────────────────────┐
        │ Mon  Tue  Wed  Thu  Fri      │
Budi    │ 🌅   🌅   🌅   🌅   🌅      │
Ani     │ 🌃   🌃   🌅   🌅   🌅      │
Citra   │ 🌅   🌅   🌅   🌅   🌅      │
        └──────────────────────────────┘

🌅 Pagi   🌃 Malam   ⚪ Off
```

### Bulk Assignment

```
Pilih karyawan:    [Multi-select]
Periode:           [From] [To]
Pola:              [Recurring dropdown]
                   - Setiap hari kerja
                   - Senin-Jumat saja
                   - Custom (pick days)
Shift:             [Select]

[Generate Preview] → review → [Confirm]
```

---

## 9. 📊 Reports

### 9.1 Attendance Report

**Route:** `GET /admin/reports/attendance`
**Component:** `App\Livewire\Admin\Report\Attendance`

#### Filters

```
Periode:        [Date range picker]
Departemen:     [Multi-select]
Karyawan:       [Multi-select]
Format Group:   [Per Hari / Per Karyawan / Summary]
```

#### Output

**Per Karyawan format:**

| Karyawan | Hadir | Telat | Absen | Cuti | Total Jam | Avg In | Avg Out |
|----------|-------|-------|-------|------|-----------|--------|---------|
| Budi     | 20    | 5     | 1     | 1    | 178h      | 08:15  | 17:30   |

#### Export

- **Excel**: Detail dengan sheet per departemen
- **PDF**: Print-friendly dengan header perusahaan
- **Print**: CSS @media print

---

### 9.2 Leave Report

Filter: periode, departemen, jenis cuti, status.
Output: Total hari per jenis cuti per karyawan.

---

### 9.3 Late Arrivals Report

Khusus laporan keterlambatan untuk evaluasi.
Filter: periode, threshold (telat >X menit).

---

### 9.4 Working Hours Report

Total jam kerja per karyawan untuk payroll.
Output: jam normal, jam lembur (kalau implement overtime).

---

## 10. 🔔 Notifications

**Route:** `GET /admin/notifications`
**Component:** `App\Livewire\Admin\Notification\Index`

### Layout

```
┌──────────────────────────────────────────┐
│ [☐ Tandai semua dibaca]  Filter: [Tipe▾] │
├──────────────────────────────────────────┤
│ ● Cuti Disetujui              2 jam lalu │
│   Pengajuan cuti Anda 10-12 Mei...       │
├──────────────────────────────────────────┤
│ ○ Pengajuan Cuti Baru         5 jam lalu │
│   Budi Santoso mengajukan cuti...        │
└──────────────────────────────────────────┘

● = unread (highlighted)
○ = read
```

Click notification → navigate ke detail terkait.

---

## 11. ⚙️ Settings (Admin Only)

### 11.1 Company Settings

**Route:** `GET /admin/settings/company`
**Access:** Admin only

#### Form

```
Nama Perusahaan:        [Input]
Logo:                   [Upload]
Timezone:               [Select]
Toleransi Telat (menit):[Number]
Hari Kerja:             [☑ Sen ☑ Sel ... ☐ Sab ☐ Min]
```

---

### 11.2 User & Role Management

**Route:** `GET /admin/settings/users`
**Access:** Admin only

Khusus untuk manage role dan status (terpisah dari employee management yang fokus ke data kepegawaian).

#### Table

| User | Role Saat Ini | Last Login | Aksi |
|------|---------------|------------|------|
| ...  | Employee      | 5 menit lalu| Change Role / Deactivate |

---

### 11.3 Audit Log Viewer

**Route:** `GET /admin/settings/audit-logs`
**Access:** Admin only

#### Filters

```
[User▾] [Action▾] [Entity▾] [Date range] [Search]
```

#### Table

| Waktu | User | Action | Entity | Entity ID | IP | Detail |
|-------|------|--------|--------|-----------|------|--------|
| 14:30 | Budi | check_in | Attendance | 123 | 192.x | View JSON |

Click "View JSON" → modal dengan metadata lengkap.

---

## 🛠️ Backend Components

### Service Classes

Buat untuk logic kompleks lintas-resource:

```
app/Services/
├── AttendanceReportService.php       ← Generate report data
├── LeaveBalanceService.php           ← Hitung & adjust kuota
├── ApprovalWorkflowService.php       ← Multi-level approval logic
├── AuditLogService.php               ← Helper logging activity
├── NotificationService.php           ← Send DB + FCM notification
├── ExportService.php                 ← Excel & PDF export
├── HolidayImportService.php          ← Import hari libur dari API
└── ShiftAssignmentService.php        ← Bulk assign shift
```

#### Kapan Service vs Repository?

| Kasus                              | Pakai          |
|------------------------------------|----------------|
| Single CRUD operation              | Repository     |
| Cross-resource logic               | Service        |
| External API call                  | Service        |
| Complex calculation                | Service        |
| Data export/import                 | Service        |

### Repository Tambahan untuk Admin

Beberapa repository perlu method tambahan untuk admin panel:

```php
// AttendanceRepository
public function getFiltered(array $filters): LengthAwarePaginator;
public function countPresentToday(int $companyId): int;
public function countLateToday(int $companyId): int;
public function getLast7DaysSummary(int $companyId): array;
public function getMonthlyReport(array $filters): Collection;

// UserRepository
public function getFilteredEmployees(array $filters): LengthAwarePaginator;
public function countActive(int $companyId): int;
public function countByDepartment(int $companyId): array;

// ApprovalRepository
public function getPendingByApprover(int $userId, int $limit = null): Collection;
public function bulkApprove(array $approvalIds, int $approverId, ?string $comment): int;
```

---

## 📦 Package Tambahan

```bash
# Excel & PDF Export
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf

# Frontend (npm)
npm install apexcharts                   # Charts
npm install leaflet                      # Map
npm install flatpickr                    # Date picker
npm install @fullcalendar/core           # Calendar (optional)
npm install sortablejs                   # Drag & drop (optional)
```

### Frontend Setup

```javascript
// resources/js/app.js
import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

window.flatpickr = flatpickr;
```

---

## 🎨 UI/UX Guidelines

### Color Scheme (Tailwind)

| Purpose          | Color                              |
|------------------|------------------------------------|
| Primary          | `blue-600` / `blue-700` (hover)    |
| Success          | `green-600`                        |
| Warning          | `yellow-500` / `orange-500`        |
| Danger           | `red-600`                          |
| Info             | `blue-500`                         |
| Neutral          | `gray-100` to `gray-900`           |
| Background       | `gray-50` (light) / `gray-900` (dark) |

### Typography

- Font: **Inter** atau **Figtree** (sudah default Laravel 12)
- Heading: `font-semibold` to `font-bold`
- Body: `text-sm` (14px) untuk table, `text-base` (16px) untuk form

### Spacing

- Card padding: `p-6`
- Section gap: `space-y-6`
- Form field gap: `space-y-4`
- Button padding: `px-4 py-2`

### Loading States

- **Skeleton screens** untuk initial load
- **Spinner** untuk action button (saat submit)
- **Progress bar** untuk file upload
- Pakai `wire:loading` directive Livewire

```html
<button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Simpan</span>
    <span wire:loading><x-ui.spinner /> Menyimpan...</span>
</button>
```

### Empty States

Setiap halaman list yang kosong harus punya empty state:
```
[Icon]
Belum ada data absensi
[CTA Button: Tambah Absensi]
```

### Error Handling

- Form errors: tampil di bawah field
- General errors: toast notification
- 500 errors: halaman custom dengan link kembali

---

## 🛣️ Implementation Order

Saran urutan pengerjaan:

### **Phase 1: Foundation (2 hari)** ⭐ WAJIB DULU
1. Layout admin (`layouts/admin.blade.php`)
2. Sidebar component dengan menu
3. Top navbar dengan profile dropdown
4. Breadcrumb
5. Reusable UI components (button, input, badge, modal, table, empty-state)
6. Toast notification setup
7. Auth middleware (`admin.access`, role middleware)
8. Login redirect logic

### **Phase 2: Profile & Dashboard (1 hari)**
9. Halaman profile saya (read-only + edit)
10. Halaman ganti password
11. Dashboard dengan stat cards
12. Dashboard chart
13. Dashboard pending approvals

### **Phase 3: Master Data (2-3 hari)**
14. CRUD Departments
15. CRUD Office Locations + Map Picker (Leaflet)
16. CRUD Shifts
17. CRUD Leave Types
18. CRUD Holidays + import API

### **Phase 4: Employee Management (2 hari)**
19. List karyawan + filter
20. Form create/edit karyawan
21. Detail karyawan dengan tabs
22. Reset password, activate/deactivate
23. (Opsional) Import Excel

### **Phase 5: Attendance Module (2 hari)**
24. List attendance + advanced filter
25. Detail attendance + map view
26. Manual entry & koreksi
27. Audit trail di detail

### **Phase 6: Leave & Permission (2 hari)**
28. List leave + filter
29. Detail leave + approval action
30. Calendar view
31. Leave balance management
32. Permission (mirip leave, lebih simple)

### **Phase 7: Approval Center (1 hari)**
33. Approval list dengan tabs
34. Approve/reject single
35. Bulk approve/reject

### **Phase 8: Shift Assignment (1 hari)**
36. Calendar view shift assignment
37. Bulk assign

### **Phase 9: Reports (2 hari)**
38. Attendance report + filter + export
39. Leave report
40. Late arrivals report
41. Working hours report

### **Phase 10: Settings & Polish (1 hari)**
42. Company settings
43. User & role management
44. Audit log viewer
45. Notification page
46. Final testing & bug fixes

---

## ✅ Definition of Done (DoD)

Setiap halaman dianggap "selesai" kalau memenuhi:

- [ ] Layout konsisten dengan layout admin
- [ ] Authorization works (middleware + policy)
- [ ] Filter & search berfungsi (kalau ada)
- [ ] Pagination berfungsi (kalau ada)
- [ ] Form validation lengkap (client + server)
- [ ] Error handling proper (toast/inline)
- [ ] Loading state ada (wire:loading)
- [ ] Empty state ada (kalau list kosong)
- [ ] Responsive (mobile-friendly)
- [ ] Action terupdate ke audit log (kalau mutating)
- [ ] Notification sent (kalau ada side effect ke user lain)
- [ ] Tested manual: happy path, validation error, permission error
- [ ] Code review sendiri (cek anti-pattern)

---

## 🔍 Testing Strategy

### Manual Testing Checklist (per halaman)

**Functional:**
- [ ] CRUD operations work
- [ ] Filter & sort work
- [ ] Pagination work
- [ ] Export work (kalau ada)

**Authorization:**
- [ ] Login as employee → tidak bisa akses
- [ ] Login as manager → terbatas ke tim
- [ ] Login as HR → akses sesuai matrix
- [ ] Login as admin → full access

**Edge Cases:**
- [ ] Data kosong → empty state
- [ ] Banyak data → pagination
- [ ] Long text → text overflow handling
- [ ] Image upload large file → validation
- [ ] Network error → graceful handling

### Automated Testing (Livewire Tests)

```php
// tests/Feature/Admin/Attendance/IndexTest.php

use App\Models\User;

it('blocks employee from accessing admin panel', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it('shows attendance list for hr', function () {
    $hr = User::factory()->create(['role' => 'hr']);

    Livewire::actingAs($hr)
        ->test(\App\Livewire\Admin\Attendance\Index::class)
        ->assertOk();
});
```

---

## 📝 Component Pattern Examples

### Pattern 1: List dengan Filter

```php
<?php
// resources/views/livewire/admin/attendance/index.blade.php

namespace App\Livewire\Admin\Attendance;

use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public ?int $departmentId = null;

    #[Url]
    public string $date = '';

    public function __construct(
        protected AttendanceRepositoryInterface $attendanceRepo,
    ) {}

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'departmentId']);
        $this->date = now()->toDateString();
    }

    public function render()
    {
        $attendances = $this->attendanceRepo->getFilteredForAdmin([
            'search'        => $this->search,
            'status'        => $this->status,
            'department_id' => $this->departmentId,
            'date'          => $this->date,
            'user'          => auth()->user(),  // for permission scope
        ]);

        return view('livewire.admin.attendance.index', [
            'attendances' => $attendances,
        ])->layout('layouts.admin');
    }
}
```

### Pattern 2: Form Modal (Create/Edit)

```php
<?php
namespace App\Livewire\Admin\Master;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DepartmentForm extends Component
{
    public ?Department $department = null;

    #[Validate('required|string|max:150')]
    public string $name = '';

    #[Validate('nullable|exists:users,id')]
    public ?int $managerId = null;

    public bool $showModal = false;

    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepo,
    ) {}

    public function openCreate(): void
    {
        $this->reset();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->department = Department::findOrFail($id);
        $this->name = $this->department->name;
        $this->managerId = $this->department->manager_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        try {
            DB::beginTransaction();

            if ($this->department) {
                $this->departmentRepo->update($this->department, [
                    'name'       => $this->name,
                    'manager_id' => $this->managerId,
                ]);
                $message = 'Departemen berhasil diperbarui';
            } else {
                $this->departmentRepo->create([
                    'company_id' => auth()->user()->company_id,
                    'name'       => $this->name,
                    'manager_id' => $this->managerId,
                ]);
                $message = 'Departemen berhasil ditambahkan';
            }

            DB::commit();

            $this->showModal = false;
            $this->dispatch('toast', type: 'success', message: $message);
            $this->dispatch('department-updated');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Department save failed', ['error' => $e->getMessage()]);
            $this->dispatch('toast', type: 'error', message: 'Gagal menyimpan data');
        }
    }
}
```

### Pattern 3: Approval Action

```php
public function approve(int $approvalId): void
{
    $approval = $this->approvalRepo->findOrFail($approvalId);

    // Authorization
    if ($approval->approver_id !== auth()->id()) {
        $this->dispatch('toast', type: 'error', message: 'Tidak berhak');
        return;
    }

    try {
        // Pakai SERVICE, bukan repository (logic kompleks)
        $this->approvalService->approve(
            $approval,
            auth()->user(),
            $this->approvalComment
        );

        $this->dispatch('toast', type: 'success', message: 'Berhasil disetujui');
        $this->dispatch('approval-updated');
    } catch (\Exception $e) {
        Log::error('Approval failed', ['error' => $e->getMessage()]);
        $this->dispatch('toast', type: 'error', message: 'Gagal memproses');
    }
}
```

---

## 🚨 Common Pitfalls (Anti-Patterns)

### ❌ JANGAN: Call API dari Livewire
```php
// SALAH
public function getData() {
    return Http::get('/api/v1/attendances')->json();
}
```

### ✅ BENAR: Inject Repository
```php
public function __construct(
    protected AttendanceRepositoryInterface $repo
) {}

public function getData() {
    return $this->repo->getAll();
}
```

---

### ❌ JANGAN: Logic kompleks di Component
```php
// SALAH — logic approval workflow di component
public function approve($id) {
    $approval = Approval::find($id);
    $approval->update([...]);
    
    // check level berikutnya
    $next = Approval::where(...)->first();
    if ($next) {
        // notify next approver
        Notification::create([...]);
    } else {
        // update leave status
        $leave = LeaveRequest::find(...);
        $leave->update(['status' => 'approved']);
        // update balance
        $balance = LeaveBalance::firstOrCreate(...);
        $balance->update([...]);
    }
}
```

### ✅ BENAR: Service Class
```php
public function approve($id) {
    $approval = $this->approvalRepo->findOrFail($id);
    $this->approvalService->approve($approval, auth()->user(), $this->comment);
}
```

---

### ❌ JANGAN: Magic strings untuk role
```php
if ($user->role === 'admin') { ... }
```

### ✅ BENAR: Pakai Enum
```php
if ($user->role === UserRole::ADMIN) { ... }
```

---

### ❌ JANGAN: N+1 query
```php
foreach ($attendances as $a) {
    echo $a->user->full_name;  // N+1!
}
```

### ✅ BENAR: Eager loading
```php
$attendances = Attendance::with('user')->get();
```

---

## 🎓 Tips & Tricks

### 1. Pakai `wire:navigate` untuk SPA-like navigation

```html
<a href="{{ route('admin.attendances.index') }}" wire:navigate>
    Daftar Absensi
</a>
```

### 2. URL state preservation

```php
#[Url(as: 'q')]
public string $search = '';

#[Url(as: 'page')]
public int $page = 1;
```

URL akan jadi: `/admin/attendances?q=budi&page=2` — bisa di-share dan di-bookmark.

### 3. Polling untuk real-time

```html
<div wire:poll.30s="refreshNotifications">
    {{ $unreadCount }} notifikasi
</div>
```

### 4. Lazy loading untuk performance

```html
<livewire:admin.dashboard.attendance-chart lazy />
```

### 5. Computed properties

```php
use Livewire\Attributes\Computed;

#[Computed]
public function totalEmployees(): int
{
    return $this->userRepo->countActive();
}
```

---

## 📚 Update CLAUDE.md

Setelah ADMIN_BLUEPRINT.md ada, update CLAUDE.md:

```markdown
## Admin Panel

Detail lengkap admin panel ada di `ADMIN_BLUEPRINT.md`.

Key principles:
1. Reuse Repository/Service yang sama dengan API
2. Session-based auth (Breeze)
3. Volt Class API untuk component
4. Selalu inject Repository via constructor (DI)
5. Service class untuk cross-resource logic
```

---

## 📋 Version History

| Version | Date       | Notes                              |
|---------|------------|------------------------------------|
| 1.0     | 2026-04-30 | Initial admin blueprint            |
