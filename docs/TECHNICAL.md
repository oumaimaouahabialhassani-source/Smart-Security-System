# Technical Documentation

## Stack

Laravel 11 · PHP 8.3 · MySQL 8.4 · Vite · bespoke CSS design system
(`resources/css/dashboard.css`, dark/light theme via CSS variables — no CSS framework).
No JS framework: small vanilla-JS modules per page (polling feeds, modals, charts are
server-rendered CSS bars/donuts).

## Architecture

- **Capability-based RBAC on a single enum** — `app/Enums/UserRole.php` is the one
  source of truth. Two roles: `super_admin` (unrestricted, exactly one) and `viewer`
  (read-only across all operational modules). Every permission is a `can*()` method
  on the enum, so reintroducing management roles later means editing this file only.
  `Gate::before` (AppServiceProvider) short-circuits all policies for the Super Admin.
- **Authorization layers**: route middleware (`module:{name}` → `EnsureModuleAccess`,
  `ai.bot` → `EnsureAiBotAccess`, `active` → account status) · policies
  (`UserPolicy`, `CameraPolicy`, `DevicePolicy`, `AiAlertPolicy`) · FormRequest
  `authorize()` · inline `abort_unless()` for non-resource actions. The frontend only
  *hides* controls; every mutation is re-validated server-side.
- **Models own their business logic** (no repository layer): enum casts via `casts()`,
  `scopeSearch()`, sequential human codes assigned in `booted()` (`ALT-`, `AIB-`,
  `VST-`…), helpers like `Alert::raise()` and `AuditLog::record()`.
- **Services** (`app/Services/`):
  - `AiRiskAnalyzer` — rule-based 0–100 risk scoring (baselines per event type +
    modifiers: after-hours, repetition, blacklist, sensitive zones, coverage gaps),
    maps to Low/Medium/High/Critical, picks a recommendation, keeps every factor as a
    human-readable explanation. Swap `analyze()` for an LLM/model call to go "real AI".
  - `AiEventMonitor` — the monitoring sweep: scans access events, system alerts,
    visits and biometric verifications since the last sweep (persisted in `settings`),
    idempotent via the `ai_alerts (source_type, source_id, event_type)` unique key,
    fans out notifications for High/Critical. Triggered by dashboard feed polls
    (throttled to 30s) and the admin "Run AI Scan" button — no cron required.
  - `AiChatAssistant` — keyword-intent assistant; every answer is a live DB query.
  - `AiInsightsService` — natural-language insights (zone trends, unstable cameras,
    peak windows, repeated failures) + least-squares 7-day alert forecast.
- **Auditing** — `app/Observers/Auditable.php` observes every business model
  (create/update/delete), auth events (login/logout/failed/reset) are listened to in
  `AppServiceProvider`, role changes logged explicitly in `UserController`. Each entry
  stores user, IP, browser/OS/device (parsed UA), URL, method, timestamp.
- **Notifications** — Laravel database channel (top-bar bell + `/notifications` page),
  mail opt-in per user; SMS/WhatsApp are logged placeholders in
  `AiEventMonitor::notify()` ready for a Twilio/Vonage channel. Monitoring roles
  (`UserRole::monitoringRoles()`) receive camera-offline/unknown-face/forced-door/
  motion/emergency alerts.
- **Live Monitoring** — `cameras/live` renders a tile per camera; a JS "stream driver"
  swaps the simulated placeholder for a `<video>` element whenever the camera's
  stream URL is playable (HLS/MP4). RTSP integration = deploy a gateway (MediaMTX,
  go2rtc), store its URL per camera. Status/AI-detection overlays poll
  `cameras/live/feed` every 10s (paused when the tab is hidden).

## Performance conventions

- List pages: eager-loaded relations + `paginate()->withQueryString()`.
- Heavy aggregate pages cached: dashboard 30s (`dashboard.aggregates`),
  AI analytics 60s (`ai.analytics`), settings cached forever with invalidation.
- All pollers (bell 30s, AI feed 15s, live wall 10s) pause on `document.hidden`.
- Hot query paths are indexed (see DATABASE.md); `php artisan optimize` verified.

## Testing

`tests/Feature/RoleMatrixTest` asserts the full page × role access matrix (7 roles ×
21 URLs). `SuperAdminProtectionTest` covers privilege-escalation guards.
`RoleAccessTest` / `ExportAccessTest` cover mutations and PII exports.
