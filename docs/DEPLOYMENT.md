# Deployment Guide (Production)

## 1. Environment (`.env`)

```dotenv
APP_ENV=production
APP_DEBUG=false                 # NEVER true in production
APP_URL=https://your-domain.tld
APP_KEY=                        # generate on the server: php artisan key:generate

SESSION_SECURE_COOKIE=true      # requires HTTPS
SESSION_ENCRYPT=true            # optional hardening

LOG_CHANNEL=stack
LOG_LEVEL=warning               # 'debug' is for development only

DB_*                            # dedicated MySQL user, strong password,
                                # least privilege (no GRANT/SUPER)

MAIL_MAILER=smtp                # or configure from Settings → Email after deploy
MAIL_HOST=... MAIL_PORT=587 MAIL_USERNAME=... MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=security@your-domain.tld
```

Never commit `.env`. Never reuse the development `APP_KEY`.

## 2. Build & optimize

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force
php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

After every deploy that changes config/routes/views, re-run the four `*:cache` commands
(`php artisan optimize` runs them all).

## 3. Web server

Point the document root at `public/` (never the project root). Standard Laravel
nginx/Apache config applies. Enforce HTTPS (redirect 80 → 443); the app sends
`http_only` + `same_site=lax` cookies and expects `SESSION_SECURE_COOKIE=true`.

Recommended headers at the web-server level:
`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Strict-Transport-Security: max-age=31536000`.

## 4. Scheduler & queues

Cron entry (required for automatic backups configured in Settings → Backups):

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The app runs synchronously by default (`QUEUE_CONNECTION=database` is configured but no
jobs are queued yet) — no worker required. If you later queue mail, run
`php artisan queue:work` under supervisor.

## 5. Permissions & storage

- `storage/` and `bootstrap/cache/` writable by the PHP user.
- Database backups are written to `storage/app/backups` — include this path (and the
  database itself) in your server-level backup rotation; app-level dumps are a
  convenience, not a disaster-recovery plan.

## 6. First run on production

1. `php artisan migrate --force` (no seeder).
2. Create the first Administrator (see INSTALLATION.md §3).
3. Log in → Settings: set company name, timezone, session timeout, password policy,
   SMTP (use "Send test email"), backup schedule.
4. Create staff accounts from the Users module (self-registration is disabled by design).

## 7. Manual configuration checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, fresh `APP_KEY`
- [ ] HTTPS certificate + `SESSION_SECURE_COOKIE=true`
- [ ] Real SMTP credentials (default `log` mailer only writes to the log file)
- [ ] Cron entry for `schedule:run`
- [ ] `php artisan optimize` after each deploy
- [ ] Server-level database backups + offsite copy
- [ ] Change/remove any seeded demo accounts
- [ ] Hardware integrations (RTSP camera streams, real biometric SDK, badge QR
      scanners) are stubbed with realistic simulations — connect real devices when available
