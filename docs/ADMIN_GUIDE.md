# Administrator Guide

## Accounts & roles

- The system has exactly two roles: the single **Super Admin** (unrestricted) and
  **Viewer** (read-only). **Users → Add User** creates a Viewer account and emails a
  set-password invitation; the super_admin role can never be assigned from the UI.
- The Super Admin account cannot be edited, deleted or demoted by anyone — including
  itself (role-wise).
- The Users table shows each account's permissions class, last login, who created
  and last updated it, and a link to its audit activity.
- Suspend/deactivate from the edit form — the `active` middleware terminates the
  user's session on their next request.

## Security operations

- **Alerts**: acknowledge-all, assign, annotate, resolve. Notification fan-out goes
  to both roles (Super Admin and Viewer), honoring each user's preferences.
- **AI Security Bot**: sweeps run automatically while anyone watches the AI
  dashboard, or on demand via *Run AI Scan* (admin only). Review each finding —
  marking false positives feeds the accuracy/false-positive metrics on AI Analytics.
  Exports and alert management are Super Admin-only; Viewers monitor.
- **AI Analytics**: watch the security score, the 7-day forecast, and the Insights
  panel — each insight includes a recommended action.
- **Access Control**: lock/unlock doors, lock-all (admin), permissions with
  schedules and validity windows, temporary visitor passes.
- **Audit Logs** (admin-only): every login/logout/failed login, model change and
  role change with actor, IP, browser and timestamp. Filter + CSV export.

## System administration

- **Settings** (admin-only): company branding, appearance (theme, accent, logo),
  mail (SMTP stored encrypted; test-email button), security (session timeout),
  database backups (create/download/restore/delete).
- **Backups**: created under `storage/`; restore replaces current data — download a
  fresh backup first.
- **Hardware**: register cameras/IoT devices with credentials (stored encrypted).
  For real camera streams, deploy an RTSP→HLS gateway and store its URL per camera —
  Live Monitoring picks it up automatically.
- **Production**: `APP_DEBUG=false`, `php artisan optimize`, serve `public/` only,
  HTTPS, and change the seeded Super Admin password immediately.

## Extending the AI

- Real model: replace `AiRiskAnalyzer::analyze()` (keep its return array contract).
- Real SMS/WhatsApp: implement the channels in `AiEventMonitor::notify()` where the
  placeholders are logged.
- New monitored event sources: add a `scanX()` method to `AiEventMonitor` and the
  event type to `AiAlert::EVENT_TYPES` + a baseline in `AiRiskAnalyzer`.
