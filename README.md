# CIPHER Backend

> Attendance Management System with Face Recognition & Digital Signatures

Laravel 12 REST API backend for CIPHER — a modern employee attendance system that uses facial recognition for check-in/check-out and digital signatures for report approval.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Variables](#environment-variables)
- [Database](#database)
- [API Reference](#api-reference)
- [Authentication](#authentication)
- [Face Recognition](#face-recognition)
- [Role & Permissions](#role--permissions)
- [Business Logic](#business-logic)
- [Project Structure](#project-structure)
- [Changelog](#changelog)

---

## Features

- **Face Recognition Check-in/Check-out** — Euclidean distance matching against multiple stored face samples per employee (threshold 0.4)
- **Shift Management** — Assign shifts with start/end times; auto-detect late arrivals and overtime
- **Attendance Analytics** — Dashboard stats, late-today list, anomaly detection (±2 hr from shift)
- **Monthly Reports** — Generate HTML attendance reports, upload to Cloudflare R2, approve with digital signature
- **Company Settings** — Company name, address, stamp image stored and served from R2
- **Role-based Access** — Three roles: `admin`, `hr`, `employee`
- **Sanctum Bearer Token Auth** — Stateless tokens, no CSRF complexity

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Auth | Laravel Sanctum (Bearer tokens) |
| Database | MySQL 8+ |
| File Storage | Cloudflare R2 (S3-compatible) |
| Face Matching | Euclidean distance on face-api.js 128-dim embeddings |

---

## Requirements

- PHP >= 8.2
- Composer
- MySQL 8+
- Cloudflare R2 bucket (or any S3-compatible storage)

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/chandravictorious/cipher-backend.git
cd cipher-backend

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your .env (see Environment Variables section)

# 6. Run migrations and seed the database
php artisan migrate --seed

# 7. Start the development server
php artisan serve
```

---

## Environment Variables

Copy `.env.example` to `.env` and fill in the required values:

```env
# Application
APP_NAME=CIPHER
APP_ENV=local
APP_URL=http://localhost:8000

# Database (MySQL)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cipher
DB_USERNAME=root
DB_PASSWORD=your_password

# Cloudflare R2 Storage
R2_ACCESS_KEY_ID=your_r2_access_key
R2_SECRET_ACCESS_KEY=your_r2_secret_key
R2_DEFAULT_REGION=auto
R2_BUCKET=cipher-files
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_URL=https://pub-<hash>.r2.dev
```

---

## Database

### Migrations

```bash
php artisan migrate
```

| Table | Description |
|---|---|
| `departments` | Company departments |
| `users` | Employees, HR, and Admins |
| `shifts` | Work shift definitions (start/end time) |
| `employee_shifts` | User-to-shift assignments with effective date |
| `face_descriptors` | Stored face embeddings (multi-sample JSON) |
| `attendances` | Check-in/check-out records with status |
| `reports` | Monthly attendance reports with approval state |
| `company_settings` | Company name, address, stamp |
| `personal_access_tokens` | Sanctum API tokens |

### Seeder

```bash
php artisan db:seed
```

| Email | Password | Role |
|---|---|---|
| admin@cipher.com | password | admin |
| hr@cipher.com | password | hr |

---

## API Reference

Base URL: `http://localhost:8000/api`

All authenticated endpoints require:
```
Authorization: Bearer <token>
```

### Auth

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/register` | No | Register a new user |
| POST | `/login` | No | Login — receive Bearer token |
| POST | `/logout` | Yes | Revoke current token |

**Login Response:**
```json
{
  "user": { "id": 1, "name": "Admin CIPHER", "role": "admin" },
  "token": "1|abc123..."
}
```

---

### Departments *(Admin only)*

| Method | Endpoint | Description |
|---|---|---|
| GET | `/departments` | List all |
| POST | `/departments` | Create |
| PUT | `/departments/{id}` | Update |
| DELETE | `/departments/{id}` | Delete |

---

### Shifts *(Admin only)*

| Method | Endpoint | Description |
|---|---|---|
| GET | `/shifts` | List all |
| POST | `/shifts` | Create |
| PUT | `/shifts/{id}` | Update |
| DELETE | `/shifts/{id}` | Delete |

```json
{ "name": "Morning", "start_time": "08:00", "end_time": "17:00" }
```

---

### Employees

| Method | Endpoint | Description |
|---|---|---|
| GET | `/employees` | List employees |
| POST | `/employees/assign-shift` | Assign shift |
| POST | `/employees/upload-signature` | Upload signature PNG |

---

### Face Recognition

| Method | Endpoint | Description |
|---|---|---|
| POST | `/face/register` | Register face sample(s) |
| POST | `/face/check-in` | Check in via face |
| POST | `/face/check-out` | Check out via face |
| GET | `/face/descriptor/{userId}` | Get stored descriptor |

**Register** — `user_id` is optional (defaults to auth user):
```json
{ "descriptor": [-0.12, 0.34, ...] }
```

**Check-in / Check-out:**
```json
{ "descriptor": [-0.12, 0.34, ...] }
```

---

### Attendance

| Method | Endpoint | Description |
|---|---|---|
| GET | `/attendance/today` | Today's records for current user |
| GET | `/attendance/dashboard` | Aggregate stats for today |
| GET | `/attendance/history/{userId}` | Full history (`?date=YYYY-MM-DD`) |
| GET | `/attendance/late-today` | Late employees today |
| GET | `/attendance/anomalies` | Anomalous check-ins |

**Today Response:**
```json
{
  "data": [
    {
      "id": 1,
      "check_in": "2026-05-14T08:05:00.000000Z",
      "check_out": "2026-05-14T17:10:00.000000Z",
      "status": "on_time",
      "overtime_minutes": 10
    }
  ],
  "date": "2026-05-14"
}
```

---

### Reports *(Admin & HR)*

| Method | Endpoint | Description |
|---|---|---|
| GET | `/reports/pending` | List pending reports |
| POST | `/reports/generate/{employeeId}/{month}/{year}` | Generate report |
| POST | `/reports/{id}/approve` | Approve report |
| GET | `/reports/download/{id}` | Download file |

---

### Company Settings *(Admin only)*

| Method | Endpoint | Description |
|---|---|---|
| GET | `/settings` | Get settings |
| PUT | `/settings` | Update name/address |
| POST | `/settings/upload-stamp` | Upload stamp image |

---

## Authentication

CIPHER uses **Sanctum Bearer token** authentication — no CSRF handshake, no cookies required.

1. `POST /api/login` → receive `token`
2. Store token on the frontend
3. Send `Authorization: Bearer <token>` on every request

---

## Face Recognition

### Algorithm

1. **Registration** — Stores up to 10 face-api.js 128-dim embeddings per user (oldest dropped when limit reached)
2. **Matching** — Computes Euclidean distance against every stored sample for every user; the minimum distance wins
3. **Threshold** — Match accepted only if best distance ≤ **0.4** (stricter than face-api.js default of 0.6)

### Recommended Registration Flow (Frontend)

```javascript
const samples = [];
for (let i = 0; i < 5; i++) {
  const detection = await faceapi
    .detectSingleFace(video)
    .withFaceLandmarks()
    .withFaceDescriptor();
  if (detection) samples.push(Array.from(detection.descriptor));
}

await fetch('/api/face/register', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ descriptor: samples }),
});
```

---

## Role & Permissions

| Endpoint Group | admin | hr | employee |
|---|:---:|:---:|:---:|
| Auth | ✅ | ✅ | ✅ |
| Departments | ✅ | ❌ | ❌ |
| Shifts | ✅ | ❌ | ❌ |
| Employees | ✅ | ✅ | ✅ |
| Face Recognition | ✅ | ✅ | ✅ |
| Attendance | ✅ | ✅ | ✅ |
| Reports | ✅ | ✅ | ❌ |
| Company Settings | ✅ | ❌ | ❌ |

---

## Business Logic

**Late Detection** — Check-in after `shift.start_time` → status `late`

**Overtime** — `check_out > shift.end_time` → `overtime_minutes` = difference

**Anomaly** — `|check_in − shift.start_time| > 2 hours` → flagged as anomaly

**Active Shift** — Most recent `employee_shift` where `effective_date <= today`

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/API/      — 8 controllers
│   ├── Middleware/           — RoleMiddleware, AdminMiddleware, HRMiddleware
│   ├── Requests/             — FormRequest validation classes
│   └── Resources/            — API Resource transformers
├── Models/                   — 8 Eloquent models
└── Services/
    ├── AttendanceService.php — late/overtime/anomaly logic
    ├── FaceService.php       — multi-sample euclidean matching
    └── ReportService.php     — report generation + approval
database/
├── migrations/               — 9 migration files
└── seeders/
routes/
└── api.php                   — 30 API endpoints
```

---

## Changelog

### v0.0.0 — Initial Release

- Full CRUD: departments, shifts, employees
- Bearer token auth via Laravel Sanctum
- Face recognition with multi-sample storage (max 10 per user)
- Strict match threshold: 0.4 Euclidean distance
- Float32Array auto-normalization from face-api.js
- Late detection, overtime, anomaly detection
- Monthly report generation → Cloudflare R2
- Report approval with digital signature path
- Role-based middleware: `admin`, `hr`, `employee`
- Cloudflare R2 filesystem disk
- `GET /api/attendance/today` for current user
- `?date=` filter on history endpoint
- Database seeder: admin + HR accounts, 2 shifts, company settings
