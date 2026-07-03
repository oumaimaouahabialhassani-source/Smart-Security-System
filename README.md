# Smart Security System

A Laravel 11 application for facility security management: authentication, a security
dashboard, and (upcoming) modules for employees, visitors, cameras, access logs and reports.

## Features

- **Authentication** — login, registration, logout with CSRF protection, login rate
  limiting (5 attempts per email + IP), session regeneration, and Remember Me.
- **Dashboard** — security overview with stat cards, weekly access chart, camera status,
  and a recent access events table (placeholder data until the domain modules land).
- **Shared layouts** — `layouts/app.blade.php` (sidebar, topbar, footer) for authenticated
  pages, `layouts/guest.blade.php` for auth pages.

## Requirements

- PHP 8.2+ (Laragon ships 8.3 at `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`)
- Composer, Node.js 20+
- MySQL 8 (or use the bundled Docker setup)

## Running with Docker (recommended)

```bash
docker compose up -d --build
```

- App: http://localhost:8000 — MySQL is published on `localhost:3306` (`smart` / `secret`).
- Migrations run automatically on container start (see `docker/entrypoint.sh`).
- Seed the demo user: `docker compose exec app php artisan db:seed`

**Live code:** `app/`, `routes/`, `resources/`, `config/` and `database/` are volume-mounted,
so PHP and Blade changes apply immediately. CSS/JS changes need `npm run build` on the host
(`public/build` is shared with the container). Only changes to `composer.json`, `package.json`
or the Dockerfile itself require `docker compose build app`.

## Running locally (Laragon)

```bash
composer install
npm install && npm run build
php artisan migrate --seed
php artisan serve
```

`.env` points at the Docker MySQL (`127.0.0.1:3306`, `smart` / `secret`) — make sure the
`mysql` container is up, or adjust `DB_*` to your local MySQL.

## Demo login

| Email | Password |
|---|---|
| `test@example.com` | `password` |

## Testing

```bash
php artisan test
```

Tests run against an in-memory SQLite database (see `phpunit.xml`) — they never touch MySQL.

## Security notes

- `APP_KEY` is **not** committed anywhere. Locally it lives in `.env` (git-ignored); the
  Docker entrypoint generates one per container unless `APP_KEY` is provided via environment.
- The MySQL credentials in `docker-compose.yml` are for local development only. Do not
  deploy this compose file: it publishes the database port, enables `APP_DEBUG`, and serves
  via `php artisan serve` (a development server).

## Project structure highlights

```
app/Http/Controllers/Auth/     LoginController, RegisterController
app/Http/Controllers/          DashboardController
resources/views/layouts/       app.blade.php (authenticated), guest.blade.php (auth pages)
resources/views/partials/      sidebar, topbar, footer
resources/views/components/    shield-logo
resources/css/                 login.css (auth pages), dashboard.css (app layout)
```

## Roadmap

- Employees, Visitors, Cameras, Access Logs, Reports, Settings modules
- Replace dashboard placeholder data with real queries (each `DashboardController`
  private method documents the intended query)
- Forgot Password flow
- Role-based authorization
