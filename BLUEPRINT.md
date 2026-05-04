# BLUEPRINT.md — Prima Attendances API

> Dokumen ini adalah **single source of truth** untuk semua endpoint API yang akan dibangun.
> Mobile app (Flutter) akan konsumsi endpoint-endpoint di sini.
> Update dokumen ini setiap kali ada perubahan kontrak API.

---

## 📐 Standards & Conventions

### Base URL
```
Local:      http://localhost:8000/api/v1
Production: https://api.prima-attendances.com/v1
```

### Authentication
- Method: **Bearer Token** (Laravel Sanctum)
- Header: `Authorization: Bearer {token}`
- Token didapatkan dari endpoint `/auth/login`

### Common Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token}    (untuk endpoint protected)
```

### HTTP Status Codes
| Code | Usage                                                       |
|------|-------------------------------------------------------------|
| 200  | Success (GET, PUT, DELETE)                                  |
| 201  | Created (POST yang membuat resource baru)                   |
| 400  | Bad Request (request format salah)                          |
| 401  | Unauthorized (token invalid/expired)                        |
| 403  | Forbidden (token valid tapi tidak ada akses)                |
| 404  | Not Found (resource tidak ditemukan)                        |
| 422  | Unprocessable Entity (validation error)                     |
| 429  | Too Many Requests (rate limit)                              |
| 500  | Internal Server Error                                       |

### Response Format

**Success:**
```json
{
  "success": true,
  "message": "Pesan sukses dalam Bahasa Indonesia",
  "data": { ... }
}
```

**Success with Pagination:**
```json
{
  "success": true,
  "message": "Data berhasil diambil",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Data tidak valid",
  "errors": {
    "email": ["Email tidak boleh kosong"],
    "password": ["Password minimal 8 karakter"]
  }
}
```

**General Error (4xx/5xx):**
```json
{
  "success": false,
  "message": "Pesan error dalam Bahasa Indonesia"
}
```

### Date & Time Format
- Date: `YYYY-MM-DD` (e.g., `2026-04-30`)
- DateTime: `YYYY-MM-DD HH:mm:ss` (e.g., `2026-04-30 08:15:30`)
- Time: `HH:mm:ss` (e.g., `08:15:30`)
- Timezone di response: **Asia/Jakarta** (WIB)

### Pagination Parameters
```
?page=1            (default: 1)
?per_page=15       (default: 15, max: 100)
?sort=created_at   (default: created_at)
?order=desc        (default: desc, options: asc/desc)
```

### Rate Limiting
| Endpoint Type        | Limit              |
|----------------------|--------------------|
| Login                | 10 req/min per IP  |
| Authenticated routes | 60 req/min per user|

---

## 🔐 1. Authentication

### 1.1 Login
**Endpoint:** `POST /api/v1/auth/login`
**Auth:** None (Public)

**Request Body:**
```json
{
  "email": "superadmin@gmail.com",
  "password": "prima1682022",
  "device_name": "Samsung Galaxy S23",
  "device_id": "android-uuid-12345",
  "platform": "android",
  "fcm_token": "fcm-token-string"
}
```

**Validation:**
- `email`: required, email
- `password`: required, string, min:8
- `device_name`: required, string, max:150
- `device_id`: required, string
- `platform`: required, in:android,ios
- `fcm_token`: nullable, string

**Response 200:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|abcdef123456...",
    "token_type": "Bearer",
    "expires_at": "2026-05-30 10:00:00",
    "user": {
      "id": 1,
      "employee_id": "EMP001",
      "full_name": "Super Admin",
      "email": "superadmin@gmail.com",
      "phone": null,
      "photo_url": null,
      "role": "admin",
      "role_label": "Administrator",
      "status": "active",
      "department": {
        "id": 1,
        "name": "IT"
      },
      "company": {
        "id": 1,
        "name": "PT Prima Synergy Petroleum Indonesia",
        "timezone": "Asia/Jakarta"
      },
      "office_location": null
    }
  }
}
```

**Error Cases:**
- `422`: Email/password kosong atau format salah
- `401`: Kredensial salah
- `403`: User status `inactive`

**Business Logic:**
1. Validasi input
2. Cari user by email
3. Cek password match (Hash::check)
4. Cek `status = 'active'`, kalau tidak → return 403
5. Generate Sanctum token dengan `device_name`
6. Register/update device di tabel `devices`:
   - Cari `device_id` user, kalau ada → update FCM token & last_login
   - Kalau tidak ada → create new device record
7. Set `is_active = true` untuk device ini, set `false` untuk device lain milik user (single device policy — opsional)
8. Log audit: action `login`, entity `User`
9. Return token + user data

---

### 1.2 Get Current User (Me)
**Endpoint:** `GET /api/v1/auth/me`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Data user berhasil diambil",
  "data": {
    "id": 1,
    "employee_id": "EMP001",
    "full_name": "Super Admin",
    "email": "superadmin@gmail.com",
    "phone": null,
    "photo_url": null,
    "role": "admin",
    "role_label": "Administrator",
    "status": "active",
    "has_face_embedding": false,
    "department": { "id": 1, "name": "IT" },
    "company": { ... },
    "office_location": { ... }
  }
}
```

---

### 1.3 Logout
**Endpoint:** `POST /api/v1/auth/logout`
**Auth:** Required

**Request Body:** (kosong)

**Response 200:**
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

**Business Logic:**
1. Revoke current token: `$request->user()->currentAccessToken()->delete()`
2. Set device `is_active = false` (cari by `device_id` dari token, opsional)
3. Hapus FCM token dari device
4. Log audit: action `logout`

---

### 1.4 Refresh Token (Opsional)
**Endpoint:** `POST /api/v1/auth/refresh`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Token berhasil diperbarui",
  "data": {
    "token": "2|newtoken...",
    "expires_at": "2026-05-30 10:00:00"
  }
}
```

**Business Logic:**
1. Revoke token lama
2. Generate token baru dengan device_name yang sama

---

## ⏰ 2. Attendance

### 2.1 Get Today's Attendance
**Endpoint:** `GET /api/v1/attendance/today`
**Auth:** Required

**Response 200 (sudah check-in):**
```json
{
  "success": true,
  "message": "Data absensi hari ini",
  "data": {
    "id": 123,
    "work_date": "2026-04-30",
    "status": "present",
    "check_in": {
      "time": "08:15:30",
      "datetime": "2026-04-30 08:15:30",
      "latitude": -3.5952,
      "longitude": 98.6722,
      "selfie_url": "https://.../selfies/1/2026-04-30_checkin.jpg",
      "is_late": true,
      "late_minutes": 15,
      "is_mock_location": false,
      "face_verified": true,
      "office_location": {
        "id": 1,
        "name": "Kantor Pusat Medan",
        "address": "Jl. Sudirman No. 123"
      }
    },
    "check_out": null,
    "work_duration_min": 0,
    "shift": {
      "id": 1,
      "name": "Shift Pagi",
      "start_time": "08:00:00",
      "end_time": "17:00:00"
    },
    "can_check_in": false,
    "can_check_out": true
  }
}
```

**Response 200 (belum check-in):**
```json
{
  "success": true,
  "message": "Belum melakukan absensi hari ini",
  "data": {
    "work_date": "2026-04-30",
    "check_in": null,
    "check_out": null,
    "shift": { ... },
    "can_check_in": true,
    "can_check_out": false
  }
}
```

**Business Logic:**
1. Get attendance untuk `user_id` + `work_date = today`
2. Get shift assignment hari ini (kalau ada)
3. Hitung `late_minutes` dari `check_in_time` vs `shift.start_time`
4. Compute `can_check_in`, `can_check_out` flags

---

### 2.2 Check-In
**Endpoint:** `POST /api/v1/attendance/check-in`
**Auth:** Required
**Content-Type:** `multipart/form-data`

**Request Body:**
```
office_location_id: 1
latitude: -3.5952
longitude: 98.6722
is_mock_location: false
face_verified: true
selfie: [file]
notes: "Macet di jalan"  (optional)
```

**Validation:**
- `office_location_id`: required, exists:office_locations,id
- `latitude`: required, numeric, between:-90,90
- `longitude`: required, numeric, between:-180,180
- `is_mock_location`: required, boolean
- `face_verified`: required, boolean
- `selfie`: required, image, mimes:jpg,jpeg,png, max:5120 (5MB)
- `notes`: nullable, string, max:500

**Response 201:**
```json
{
  "success": true,
  "message": "Check-in berhasil",
  "data": {
    "id": 123,
    "work_date": "2026-04-30",
    "check_in": {
      "time": "08:15:30",
      "datetime": "2026-04-30 08:15:30",
      "selfie_url": "https://.../selfies/1/2026-04-30_checkin.jpg",
      "is_late": true,
      "late_minutes": 15,
      "office_location": { ... }
    },
    "status": "present"
  }
}
```

**Error Cases:**
- `422`: Validation error
- `422`: Sudah check-in hari ini → message: "Anda sudah melakukan check-in hari ini"
- `422`: Lokasi di luar radius → message: "Lokasi Anda di luar radius kantor (jarak: 250m, max: 100m)"
- `422`: Mock location terdeteksi (kalau policy strict) → message: "Mock location terdeteksi, check-in ditolak"
- `403`: Hari libur (kalau policy strict)

**Business Logic:**
1. Validasi via Form Request
2. Cek apakah sudah ada attendance untuk user + today → kalau ada, return 422
3. Get office location → cek `is_active = true`
4. Validasi geolocation:
   - Hitung jarak (Haversine) antara lat/lng request vs office location
   - Kalau jarak > `radius_meters` → return 422
5. Cek `is_mock_location`:
   - Kalau policy strict → return 422
   - Kalau policy soft → tetap simpan, tapi flag `is_mock_location = true` (untuk audit)
6. Cek hari libur:
   - Cek di tabel `holidays` → kalau ada, return 403 (atau warning saja, tergantung policy)
7. Get shift assignment hari ini → kalau tidak ada, pakai default
8. **DB::beginTransaction()**
9. Upload selfie ke `selfies/{user_id}/{date}_checkin.jpg`
10. Resize selfie max 1024px (Intervention Image)
11. Hitung `late_minutes`: `check_in_time - shift.start_time - company.late_tolerance_min`
12. Determine status: `present`
13. Insert attendance record
14. Log audit: action `check_in`, entity `Attendance`
15. **DB::commit()**
16. Return AttendanceResource

---

### 2.3 Check-Out
**Endpoint:** `POST /api/v1/attendance/check-out`
**Auth:** Required
**Content-Type:** `multipart/form-data`

**Request Body:**
```
latitude: -3.5952
longitude: 98.6722
is_mock_location: false
face_verified: true
selfie: [file]
notes: "Selesai meeting"  (optional)
```

**Validation:** Sama dengan check-in (tanpa `office_location_id`).

**Response 200:**
```json
{
  "success": true,
  "message": "Check-out berhasil",
  "data": {
    "id": 123,
    "work_date": "2026-04-30",
    "check_in": { ... },
    "check_out": {
      "time": "17:30:15",
      "datetime": "2026-04-30 17:30:15",
      "selfie_url": "https://.../selfies/1/2026-04-30_checkout.jpg",
      "is_early_leave": false
    },
    "work_duration_min": 555,
    "work_duration_text": "9 jam 15 menit",
    "status": "present"
  }
}
```

**Error Cases:**
- `422`: Belum check-in → "Anda belum melakukan check-in hari ini"
- `422`: Sudah check-out → "Anda sudah melakukan check-out hari ini"

**Business Logic:**
1. Get attendance untuk user + today
2. Kalau belum ada → return 422 (belum check-in)
3. Kalau `check_out_time !== null` → return 422 (sudah check-out)
4. Validasi lokasi (sama seperti check-in, pakai `office_location_id` dari attendance.check_in)
5. **DB::beginTransaction()**
6. Upload selfie check-out
7. Hitung `work_duration_min` = `check_out_time - check_in_time` dalam menit (kurangi `shift.break_minutes`)
8. Update attendance record
9. Log audit
10. **DB::commit()**

---

### 2.4 Get Attendance History
**Endpoint:** `GET /api/v1/attendance/history`
**Auth:** Required

**Query Parameters:**
```
?month=2026-04           (format: YYYY-MM, default: bulan ini)
?status=present          (optional: present, absent, leave)
?page=1
?per_page=15
```

**Response 200:**
```json
{
  "success": true,
  "message": "Riwayat absensi berhasil diambil",
  "data": [
    {
      "id": 123,
      "work_date": "2026-04-30",
      "status": "present",
      "check_in_time": "08:15:30",
      "check_out_time": "17:30:15",
      "is_late": true,
      "late_minutes": 15,
      "work_duration_min": 555,
      "work_duration_text": "9 jam 15 menit"
    },
    ...
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 15,
    "total": 22
  },
  "summary": {
    "total_present": 20,
    "total_absent": 1,
    "total_leave": 1,
    "total_late": 5,
    "total_work_hours": 178.5
  }
}
```

**Business Logic:**
1. Filter by `user_id`, month, status
2. Order by `work_date desc`
3. Hitung summary statistics

---

### 2.5 Get Office Locations
**Endpoint:** `GET /api/v1/attendance/locations`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Lokasi kantor berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "Kantor Pusat Medan",
      "address": "Jl. Sudirman No. 123, Medan",
      "latitude": -3.5952,
      "longitude": 98.6722,
      "radius_meters": 100
    }
  ]
}
```

**Business Logic:**
- Filter `company_id = user.company_id`
- Filter `is_active = true`
- Untuk employee biasa: bisa juga filter by `office_location_id` user (kalau di-assign ke lokasi spesifik)

---

## 📅 3. Leave Requests (Cuti)

### 3.1 Get Leave Types
**Endpoint:** `GET /api/v1/leave-types`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Jenis cuti berhasil diambil",
  "data": [
    {
      "id": 1,
      "name": "Cuti Tahunan",
      "default_quota": 12,
      "requires_attachment": false,
      "is_paid": true,
      "balance": {
        "year": 2026,
        "total_quota": 12,
        "used": 3,
        "remaining": 9
      }
    },
    {
      "id": 2,
      "name": "Cuti Sakit",
      "default_quota": 10,
      "requires_attachment": true,
      "is_paid": true,
      "balance": { ... }
    }
  ]
}
```

**Business Logic:**
1. Get leave types where `company_id = user.company_id`
2. Untuk setiap leave type, get balance dari `leave_balances` tahun berjalan
3. Kalau belum ada balance → buatkan otomatis dari `default_quota`

---

### 3.2 Get Leave Balances
**Endpoint:** `GET /api/v1/leave-balances`
**Auth:** Required

**Query Parameters:**
```
?year=2026   (default: tahun berjalan)
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "leave_type": { "id": 1, "name": "Cuti Tahunan" },
      "year": 2026,
      "total_quota": 12,
      "used": 3,
      "remaining": 9,
      "pending": 1
    }
  ]
}
```

`pending`: total hari dari leave_request yang masih `pending`.

---

### 3.3 List My Leave Requests
**Endpoint:** `GET /api/v1/leaves`
**Auth:** Required

**Query Parameters:**
```
?status=pending          (optional: pending, approved, rejected)
?year=2026               (optional)
?page=1
?per_page=15
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 45,
      "leave_type": { "id": 1, "name": "Cuti Tahunan" },
      "start_date": "2026-05-10",
      "end_date": "2026-05-12",
      "total_days": 3,
      "reason": "Liburan keluarga",
      "attachment_url": null,
      "status": "pending",
      "submitted_at": "2026-04-30 10:15:00",
      "approvals": [
        {
          "level": 1,
          "approver": { "id": 5, "full_name": "Manager IT" },
          "decision": "pending",
          "comment": null,
          "decided_at": null
        }
      ]
    }
  ],
  "meta": { ... }
}
```

**Business Logic:**
- Filter by `user_id = current user`
- Order by `created_at desc`
- Eager load: `leaveType`, `approvals.approver`

---

### 3.4 Get Leave Detail
**Endpoint:** `GET /api/v1/leaves/{id}`
**Auth:** Required

**Response 200:** (sama format dengan list, tapi single)

**Authorization:** Hanya bisa lihat leave milik sendiri (atau manager/HR untuk subordinate).

---

### 3.5 Submit Leave Request
**Endpoint:** `POST /api/v1/leaves`
**Auth:** Required
**Content-Type:** `multipart/form-data`

**Request Body:**
```
leave_type_id: 1
start_date: 2026-05-10
end_date: 2026-05-12
reason: "Liburan keluarga"
attachment: [file]   (required jika leave_type.requires_attachment = true)
```

**Validation:**
- `leave_type_id`: required, exists:leave_types,id
- `start_date`: required, date, after_or_equal:today
- `end_date`: required, date, after_or_equal:start_date
- `reason`: nullable, string, max:500
- `attachment`: nullable, file, mimes:pdf,jpg,jpeg,png, max:2048

**Response 201:**
```json
{
  "success": true,
  "message": "Pengajuan cuti berhasil dikirim",
  "data": { ... }
}
```

**Error Cases:**
- `422`: Sisa kuota tidak cukup → "Sisa kuota cuti tidak mencukupi (sisa: 2 hari, diajukan: 3 hari)"
- `422`: Konflik dengan leave lain yang masih pending/approved → "Anda sudah memiliki pengajuan cuti di tanggal tersebut"
- `422`: Attachment wajib tapi tidak diupload

**Business Logic:**
1. Validasi via Form Request
2. Get leave type → cek `requires_attachment`, kalau iya tapi tidak ada attachment → 422
3. Hitung `total_days`:
   - Default: hari kalender (`end_date - start_date + 1`)
   - Atau: skip weekend (Sabtu-Minggu) — tergantung policy
   - Skip national holidays dari tabel `holidays`
4. Get/create leave_balance untuk tahun berjalan
5. Cek sisa kuota: `balance.remaining >= total_days`, kalau tidak → 422
6. Cek konflik tanggal dengan leave lain (status pending/approved):
   ```sql
   WHERE user_id = ? 
     AND status IN ('pending', 'approved')
     AND ((start_date <= ? AND end_date >= ?) OR ...)
   ```
7. **DB::beginTransaction()**
8. Upload attachment kalau ada
9. Insert leave_request dengan status `pending`, `submitted_at = now()`
10. Determine approval flow:
    - Level 1: Manager dari user.department
    - Level 2: HR (user with role `hr`)
11. Insert approval records (level 1 dan 2) dengan decision `pending`
12. Send notification ke approver level 1 (DB notification + FCM push)
13. Log audit
14. **DB::commit()**

---

### 3.6 Cancel Leave Request
**Endpoint:** `DELETE /api/v1/leaves/{id}`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Pengajuan cuti berhasil dibatalkan"
}
```

**Error Cases:**
- `403`: Bukan milik user
- `422`: Status sudah `approved` atau `rejected` → "Pengajuan cuti yang sudah diproses tidak bisa dibatalkan"

**Business Logic:**
1. Authorize: hanya pemilik yang bisa cancel
2. Cek status === `pending`, kalau bukan → 422
3. Soft delete leave_request (atau update status `cancelled`)
4. Delete approval records terkait
5. Notify approver bahwa pengajuan dibatalkan
6. Log audit

---

## 📝 4. Permission Requests (Izin)

### 4.1 List My Permissions
**Endpoint:** `GET /api/v1/permissions`
**Auth:** Required

**Query Parameters:**
```
?status=pending
?type=late_arrival   (optional)
?month=2026-04
?page=1
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "permission_type": "late_arrival",
      "permission_type_label": "Datang Terlambat",
      "request_date": "2026-05-01",
      "start_time": "08:00:00",
      "end_time": "10:00:00",
      "reason": "Antar anak ke sekolah",
      "attachment_url": null,
      "status": "pending",
      "submitted_at": "2026-04-30 14:00:00",
      "approvals": [ ... ]
    }
  ],
  "meta": { ... }
}
```

---

### 4.2 Submit Permission Request
**Endpoint:** `POST /api/v1/permissions`
**Auth:** Required
**Content-Type:** `multipart/form-data`

**Request Body:**
```
permission_type: late_arrival   (late_arrival | early_departure | out_of_office)
request_date: 2026-05-01
start_time: 08:00              (optional, tergantung type)
end_time: 10:00                (optional)
reason: "Antar anak ke sekolah"
attachment: [file]              (optional)
```

**Validation:**
- `permission_type`: required, in:late_arrival,early_departure,out_of_office
- `request_date`: required, date, after_or_equal:today
- `start_time`: nullable, date_format:H:i
- `end_time`: nullable, date_format:H:i, after:start_time
- `reason`: required, string, max:500
- `attachment`: nullable, file, mimes:pdf,jpg,jpeg,png, max:2048

**Business Logic:** Mirip leave request, tapi tidak cek kuota.

---

### 4.3 Get Permission Detail
**Endpoint:** `GET /api/v1/permissions/{id}`

### 4.4 Cancel Permission
**Endpoint:** `DELETE /api/v1/permissions/{id}`

---

## ✅ 5. Approvals (untuk Manager/HR)

### 5.1 List Pending Approvals
**Endpoint:** `GET /api/v1/approvals/pending`
**Auth:** Required (role: manager, hr, admin)

**Query Parameters:**
```
?type=leave              (optional: leave, permission)
?page=1
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "approval_id": 78,
      "request_type": "leave",
      "request_id": 45,
      "level": 1,
      "requester": {
        "id": 10,
        "employee_id": "EMP010",
        "full_name": "Budi Santoso",
        "department": "IT"
      },
      "details": {
        "leave_type": "Cuti Tahunan",
        "start_date": "2026-05-10",
        "end_date": "2026-05-12",
        "total_days": 3,
        "reason": "Liburan keluarga",
        "attachment_url": null
      },
      "submitted_at": "2026-04-30 10:15:00"
    }
  ],
  "meta": { ... }
}
```

**Business Logic:**
1. Get approvals where:
   - `approver_id = current user`
   - `decision = 'pending'`
2. Eager load request detail (polymorphic: leave_request atau permission_request)
3. Order by `created_at asc` (FIFO)

---

### 5.2 Approve Request
**Endpoint:** `POST /api/v1/approvals/{id}/approve`
**Auth:** Required (role: manager, hr, admin)

**Request Body:**
```json
{
  "comment": "Disetujui, selamat liburan!"
}
```

**Validation:**
- `comment`: nullable, string, max:500

**Response 200:**
```json
{
  "success": true,
  "message": "Pengajuan berhasil disetujui",
  "data": {
    "approval_id": 78,
    "decision": "approved",
    "decided_at": "2026-04-30 14:30:00",
    "next_level": {
      "level": 2,
      "approver": "HR Manager"
    },
    "request_status": "pending"
  }
}
```

**Business Logic:**
1. Authorize: approval.approver_id === current user
2. Cek decision === 'pending', kalau bukan → 422
3. **DB::beginTransaction()**
4. Update approval:
   - `decision = 'approved'`
   - `comment`
   - `decided_at = now()`
5. Cek apakah ada level berikutnya:
   - Kalau ADA level berikutnya → request masih `pending`, notify approver level berikutnya
   - Kalau TIDAK ADA (ini approval terakhir) → update request status:
     - `leave_requests.status = 'approved'`
     - Untuk leave: update `leave_balances.used += total_days`, `remaining -= total_days`
6. Notify requester (DB + FCM)
7. Log audit
8. **DB::commit()**

---

### 5.3 Reject Request
**Endpoint:** `POST /api/v1/approvals/{id}/reject`
**Auth:** Required (role: manager, hr, admin)

**Request Body:**
```json
{
  "comment": "Tidak disetujui karena bertabrakan dengan project deadline"
}
```

**Validation:**
- `comment`: required, string, max:500

**Response 200:**
```json
{
  "success": true,
  "message": "Pengajuan ditolak"
}
```

**Business Logic:**
1. Authorize
2. Update approval: `decision = 'rejected'`, `comment`, `decided_at`
3. Update request status: `leave_requests.status = 'rejected'`
4. Set semua approval level berikutnya jadi `rejected` juga (cascade)
5. Notify requester
6. Log audit

---

### 5.4 List All Approvals (History)
**Endpoint:** `GET /api/v1/approvals`
**Auth:** Required (role: manager, hr, admin)

**Query Parameters:**
```
?status=approved   (pending, approved, rejected)
?type=leave
?date_from=2026-04-01
?date_to=2026-04-30
?page=1
```

---

## 🔔 6. Notifications

### 6.1 List Notifications
**Endpoint:** `GET /api/v1/notifications`
**Auth:** Required

**Query Parameters:**
```
?is_read=false   (optional)
?type=info       (optional)
?page=1
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 100,
      "title": "Cuti Disetujui",
      "body": "Pengajuan cuti tanggal 10-12 Mei 2026 telah disetujui",
      "type": "info",
      "is_read": false,
      "created_at": "2026-04-30 14:30:00"
    }
  ],
  "meta": { ... },
  "unread_count": 3
}
```

---

### 6.2 Mark as Read
**Endpoint:** `POST /api/v1/notifications/{id}/read`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Notifikasi ditandai sebagai dibaca"
}
```

---

### 6.3 Mark All as Read
**Endpoint:** `POST /api/v1/notifications/read-all`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "message": "Semua notifikasi ditandai sebagai dibaca",
  "data": { "marked_count": 5 }
}
```

---

### 6.4 Get Unread Count
**Endpoint:** `GET /api/v1/notifications/unread-count`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "data": { "unread_count": 3 }
}
```

---

## 👤 7. Profile

### 7.1 Get Profile
**Endpoint:** `GET /api/v1/profile`
**Auth:** Required

**Response 200:** (sama dengan `/auth/me`)

---

### 7.2 Update Profile
**Endpoint:** `PUT /api/v1/profile`
**Auth:** Required

**Request Body:**
```json
{
  "full_name": "Budi Santoso",
  "phone": "081234567890"
}
```

**Validation:**
- `full_name`: required, string, max:150
- `phone`: nullable, string, max:20

**Response 200:**
```json
{
  "success": true,
  "message": "Profil berhasil diperbarui",
  "data": { ... user data ... }
}
```

---

### 7.3 Change Password
**Endpoint:** `PUT /api/v1/profile/password`
**Auth:** Required

**Request Body:**
```json
{
  "current_password": "oldpass123",
  "new_password": "newpass456",
  "new_password_confirmation": "newpass456"
}
```

**Validation:**
- `current_password`: required, current_password
- `new_password`: required, string, min:8, confirmed
- `new_password_confirmation`: required

**Response 200:**
```json
{
  "success": true,
  "message": "Password berhasil diperbarui"
}
```

**Business Logic:**
1. Verifikasi current password
2. Hash & update password
3. (Opsional) Revoke semua token kecuali current → force re-login di device lain
4. Log audit

---

### 7.4 Upload Avatar
**Endpoint:** `POST /api/v1/profile/avatar`
**Auth:** Required
**Content-Type:** `multipart/form-data`

**Request Body:**
```
photo: [file]
```

**Validation:**
- `photo`: required, image, mimes:jpg,jpeg,png, max:2048

**Response 200:**
```json
{
  "success": true,
  "message": "Foto profil berhasil diperbarui",
  "data": {
    "photo_url": "https://.../photos/1/avatar.jpg"
  }
}
```

**Business Logic:**
1. Delete old photo file
2. Upload new photo, resize max 512x512
3. Update `users.photo`

---

### 7.5 Submit Face Embedding
**Endpoint:** `POST /api/v1/profile/face-embedding`
**Auth:** Required

**Request Body:**
```json
{
  "embedding": "[0.123, -0.456, 0.789, ...]"
}
```

**Validation:**
- `embedding`: required, json, max:50000

**Response 200:**
```json
{
  "success": true,
  "message": "Data wajah berhasil disimpan"
}
```

**Business Logic:**
- Validate JSON format
- Update `users.face_embedding`
- Encrypt sebelum simpan (optional, untuk security)

---

## 📱 8. Device Management

### 8.1 Register Device
**Endpoint:** `POST /api/v1/devices/register`
**Auth:** Required

**Request Body:**
```json
{
  "device_id": "android-uuid-12345",
  "device_name": "Samsung Galaxy S23",
  "platform": "android",
  "fcm_token": "fcm-token-string"
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Device berhasil didaftarkan",
  "data": { ... }
}
```

**Business Logic:**
- Upsert device by `device_id` + `user_id`
- Update FCM token

---

### 8.2 Update FCM Token
**Endpoint:** `PUT /api/v1/devices/fcm-token`
**Auth:** Required

**Request Body:**
```json
{
  "device_id": "android-uuid-12345",
  "fcm_token": "new-fcm-token"
}
```

---

### 8.3 List My Devices
**Endpoint:** `GET /api/v1/devices`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "device_name": "Samsung Galaxy S23",
      "platform": "android",
      "is_active": true,
      "last_login": "2026-04-30 08:00:00",
      "is_current": true
    }
  ]
}
```

---

### 8.4 Logout Device
**Endpoint:** `DELETE /api/v1/devices/{id}`
**Auth:** Required

**Business Logic:**
- Revoke token milik device tersebut
- Set `is_active = false`

---

## 📅 9. Shifts (Read-only)

### 9.1 Get My Shift Today
**Endpoint:** `GET /api/v1/shifts/today`
**Auth:** Required

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Shift Pagi",
    "start_time": "08:00:00",
    "end_time": "17:00:00",
    "break_minutes": 60
  }
}
```

---

### 9.2 Get My Shift Schedule
**Endpoint:** `GET /api/v1/shifts/schedule`
**Auth:** Required

**Query Parameters:**
```
?month=2026-04
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "work_date": "2026-04-30",
      "shift": { ... }
    },
    ...
  ]
}
```

---

## 🏖️ 10. Holidays

### 10.1 List Holidays
**Endpoint:** `GET /api/v1/holidays`
**Auth:** Required

**Query Parameters:**
```
?year=2026
?month=05   (optional)
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2026-05-01",
      "name": "Hari Buruh Internasional",
      "is_national": true
    }
  ]
}
```

---

## 🛠️ Implementation Order

Saran urutan pengerjaan untuk efisiensi maksimal:

### Phase 1: Foundation (Hari 1)
1. **Enums** — buat semua enum yang dibutuhkan
2. **Model relationships** — tambahkan relationship + casts + fillable di semua model
3. **ApiResponse trait** — `app/Traits/ApiResponse.php`
4. **GeolocationHelper** — `app/Helpers/GeolocationHelper.php`
5. **Middleware**:
   - `EnsureUserIsActive`
   - `CheckRole`

### Phase 2: Auth (Hari 1-2)
6. UserRepository (Interface + Implementation)
7. RepositoryServiceProvider + register
8. LoginRequest
9. UserResource
10. AuthController (login, me, logout)
11. Routes + middleware
12. **Tests**: LoginTest, MeTest, LogoutTest

### Phase 3: Attendance (Hari 2-4) — Core Feature
13. AttendanceRepository
14. CheckInRequest, CheckOutRequest
15. AttendanceResource
16. OfficeLocationResource
17. AttendanceController
18. Routes
19. **Tests**: CheckInTest (5+ scenarios), CheckOutTest, HistoryTest

### Phase 4: Leave (Hari 4-6)
20. LeaveRequestRepository, LeaveBalanceRepository, LeaveTypeRepository
21. StoreLeaveRequest
22. LeaveRequestResource, LeaveTypeResource, LeaveBalanceResource
23. LeaveRequestController
24. Approval workflow logic
25. **Tests**

### Phase 5: Permission (Hari 6-7)
Mirip dengan Leave, tapi lebih simple.

### Phase 6: Approval (Hari 7-8)
26. ApprovalRepository
27. ApprovalController (pending, approve, reject)
28. Notification on approve/reject
29. **Tests**

### Phase 7: Supporting Features (Hari 8-9)
- Notifications endpoints
- Profile endpoints
- Device management
- Shifts (read-only)
- Holidays

### Phase 8: Testing & Polish (Hari 9-10)
- Integration test full workflow (login → check-in → leave → approve)
- Postman collection / API documentation (Scribe)
- Performance test
- Security review

---

## 📦 Required Packages

```bash
# Image processing (resize selfie, avatar)
composer require intervention/image-laravel
php artisan vendor:publish --provider="Intervention\Image\Laravel\ServiceProvider"

# API documentation (optional, sangat recommended)
composer require knuckleswtf/scribe --dev
php artisan scribe:install

# Push notification FCM (saat implement notifikasi)
composer require kreait/laravel-firebase
```

---

## 🔍 Testing Checklist (Per Endpoint)

Setiap endpoint minimal harus punya test untuk:
- [ ] **Happy path** — input valid, response sesuai expected
- [ ] **Validation error** — required field kosong, format salah
- [ ] **Authentication error** — tanpa token (401)
- [ ] **Authorization error** — role tidak punya akses (403)
- [ ] **Business logic error** — duplicate, conflict, etc.
- [ ] **Edge cases** — boundary values, empty results

---

## 📝 Postman Collection Structure

Saat sudah jadi, buatkan Postman collection dengan struktur:

```
Prima Attendances API/
├── Auth/
│   ├── Login
│   ├── Me
│   └── Logout
├── Attendance/
│   ├── Today
│   ├── Check In
│   ├── Check Out
│   ├── History
│   └── Locations
├── Leave/
│   ├── Get Leave Types
│   ├── Submit Leave
│   ├── My Leaves
│   └── Cancel Leave
├── Permission/
├── Approval/
│   ├── Pending
│   ├── Approve
│   └── Reject
├── Notification/
├── Profile/
└── Device/
```

Environment variables:
- `base_url`: http://localhost:8000/api/v1
- `token`: (auto-set dari login response)

---

## 🚀 Next Steps

Setelah BLUEPRINT.md ini ada di project, lanjutkan dengan:

1. Update `CLAUDE.md` — tambahkan reference ke BLUEPRINT.md di section "API Plan"
2. Mulai Phase 1 (Foundation)
3. Test setiap endpoint sebelum lanjut ke endpoint berikutnya
4. Update BLUEPRINT.md kalau ada perubahan kontrak (versi semantic: v1.0 → v1.1)

---

## 📋 Version History

| Version | Date       | Notes                          |
|---------|------------|--------------------------------|
| 1.0     | 2026-04-30 | Initial blueprint              |
