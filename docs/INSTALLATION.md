# Installation Guide

## 1. Prerequisites

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.2 – 8.3 | extensions: pdo_mysql, mbstring, openssl, ctype, fileinfo, tokenizer, xml |
| Composer | 2.x | |
| Node.js | 20+ | for the Vite asset build |
| MySQL | 8.x | a database named `smart_security` (any name works — set it in `.env`) |

> **Laragon users:** the PHP on your PATH may be older. Use the bundled 8.3 binary
> explicitly: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan …`

## 2. Setup

```bash
git clone <repo> smart-security && cd smart-security

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set the database connection:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_security
DB_USERNAME=<user>
DB_PASSWORD=<password>
```

## 3. Database

```bash
php artisan migrate --seed
```

`--seed` creates the admin account plus realistic demo data (staff, cameras, devices,
visits, biometric profiles, doors, access events, alerts). For an **empty production
database**, run `php artisan migrate` only, then create the first administrator:

```bash
php artisan tinker
>>> App\Models\User::create(['first_name' => 'Admin', 'last_name' => 'User',
...     'email' => 'admin@example.com', 'password' => 'ChangeMe!123',
...     'role' => App\Enums\UserRole::Administrator,
...     'status' => App\Enums\UserStatus::Active]);
```

(The password is hashed automatically by the model cast.)

## 4. Run

```bash
php artisan serve        # http://127.0.0.1:8000
```

Optional (enables scheduled automatic backups locally):

```bash
php artisan schedule:work
```

## 5. Log in

Seeded credentials: `admin@smartsecurity.test` / `password`
(change it immediately from Users → Edit if the seeder is used outside a demo).

## 6. Verify

```bash
php artisan test         # 37 tests, in-memory SQLite
```

## Troubleshooting

- **"Connection refused" / 500 on first load** — MySQL isn't running.
- **Styles look broken** — run `npm run build` (assets are served from `public/build`).
- **Emails** — the default `MAIL_MAILER=log` writes mail to `storage/logs/laravel.log`.
  Configure real SMTP from Settings → Email or in `.env`.
