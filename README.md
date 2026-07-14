<<<<<<< HEAD
# Smart Security System

A Laravel 11 back-office for a physical security company: visitor management, biometric
enrollment & verification, door access control, real-time alerts and
cross-module analytics — all behind role-based access control.

## Modules

| Module | Description | Access |
|---|---|---|
| Dashboard | Live security overview built from real access events | All roles |
| Users | Staff accounts, roles, status (active / inactive / suspended) | Administrator |
| Visitors | Registration, check-in/out, printable badge & visitor pass, blacklist | All view · manage: Admin, Security Officer, Receptionist |
| Cameras | CCTV inventory, status, maintenance | All view · manage: Admin, Security Officer |
| IoT Devices | Sensors & controllers, battery / signal monitoring | All view · manage: Admin, Security Officer |
| Biometrics | Face / fingerprint / iris enrollment, identity verification, device panel | All view · enroll/verify: Admin, Security Officer |
| Access Control | Permissions (permanent & temporary), door lock/unlock, lockdown, logs, live feed | All view · manage: Admin, Security Officer |
| Alerts | SOC page: lifecycle, assignment, facility map, insights, notification prefs | All view · manage: Admin, Security Officer |
| Reports & Analytics | Cross-module KPIs, charts, heatmap, CSV exports | Administrator, Manager |
| Settings | 10 groups incl. security policy, SMTP, appearance, backups | Administrator |

## Roles

`Administrator` (full control) · `Security Officer` (operations: visitors, biometrics,
access, alerts, hardware) · `Manager` (viewer + Reports) · `Receptionist` (visitor
registration + temporary access) · `Employee` (viewer).

Public self-registration is intentionally disabled — accounts are created by an
Administrator from the Users module.

## Requirements

- PHP 8.2+ (Laragon ships 8.3 at `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`)
- Composer, Node.js 20+
- MySQL 8

## Quick start (local)

```bash
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate   # then set DB_* credentials
php artisan migrate --seed
php artisan serve
```

Full instructions: [docs/INSTALLATION.md](docs/INSTALLATION.md) ·
Production: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) ·
Usage: [docs/USER-MANUAL.md](docs/USER-MANUAL.md)

## Demo accounts (seeded)

| Email | Password | Role |
|---|---|---|
| `admin@smartsecurity.test` | `password` | Administrator |
| `employee.test@smartsecurity.test` | `password` | Employee (viewer) |

The seeder also creates demo staff, 18 cameras, 22 devices, 40 visits, biometric
profiles, 8 doors, ~320 access events and 73 alerts so every screen has data.

## Testing

```bash
php artisan test
```

feature tests (auth, RBAC, settings, reports, hardware) run
against in-memory SQLite (see `phpunit.xml`) — they never touch MySQL.

## Architecture notes

- **Authorization** — role permissions live on the `App\Enums\UserRole` enum
  (`canManageVisitors()`, `canViewReports()`, …), enforced through Policies,
  FormRequest `authorize()` and controller guards. The `active` middleware logs out
  suspended/inactive users on their next request.
- **Hardware event log** — `App\Models\HardwareEvent` records real device
  interactions (status transitions, commands, pushes, enrollments); see
  `docs/HARDWARE.md`.
- **Settings** — key/value store cached forever (`settings.all`), applied to runtime
  config (app name, timezone, session lifetime, mail) in `AppServiceProvider`.
- **Backups** — `php artisan backup:run` produces a SQL dump in `storage/app/backups`;
  daily/weekly/monthly schedules are toggled from the Settings module.
- **Frontend** — Blade + a single custom design system (`resources/css/dashboard.css`,
  dark/light themes), bundled with Vite. Run `npm run build` after CSS/JS changes.
=======
# Smart-Security-System
>>>>>>> 247efc114c0cfd608fc7a4cc0e187423573f5162
