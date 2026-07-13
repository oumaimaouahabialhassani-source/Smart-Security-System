# AI Security Bot Module

An intelligent security assistant that continuously analyzes every event the Smart Security System records (access events, alerts, visits, biometric verifications), classifies its risk, generates AI alerts with explanations and recommendations, and notifies administrators.

## Access

| Capability | Roles |
|---|---|
| AI dashboard, alerts, history, exports, scans | Administrator, Security Officer (`UserRole::canUseAiBot()`, enforced by the `ai.bot` middleware) |
| AI Chat Assistant | Administrator only (`UserRole::canUseAiAssistant()`) |
| Delete AI alerts | Administrator only (`AiAlertPolicy::delete`) |

## Pages & Routes

All routes live under `/ai-bot` (route names `ai.*`), inside the `auth` + `active` + `ai.bot` middleware stack:

| Route | Name | Purpose |
|---|---|---|
| `GET /ai-bot` | `ai.dashboard` | Stat cards (total / critical / high / medium / low / today / accuracy / live status), live event feed, 7-day charts, latest alerts |
| `GET /ai-bot/feed` | `ai.feed` | JSON live-monitoring feed, polled every 15s; also triggers a monitoring sweep (throttled to one per 30s) |
| `POST /ai-bot/scan` | `ai.scan` | Manual "Run AI Scan" |
| `GET /ai-bot/alerts` | `ai.alerts` | Alert management with filters (risk, status, event type, camera, employee, date range) and inline review/resolve |
| `PATCH /ai-bot/alerts/{aiAlert}` | `ai.alerts.update` | Update status + notes (validated by `UpdateAiAlertRequest`) |
| `POST /ai-bot/alerts/{aiAlert}/resolve` | `ai.alerts.resolve` | Quick resolve |
| `DELETE /ai-bot/alerts/{aiAlert}` | `ai.alerts.destroy` | Delete (admin only) |
| `GET /ai-bot/history` | `ai.history` | Full archive with search + pagination |
| `GET /ai-bot/history/export` | `ai.export` | CSV export (opens in Excel), honors current filters |
| `GET /ai-bot/report` | `ai.report` | Printable daily report — use the browser's Print → Save as PDF (`?date=YYYY-MM-DD` optional) |
| `GET /ai-bot/chat` + `POST /ai-bot/chat` | `ai.chat`, `ai.chat.message` | Admin-only chat assistant (JSON API) |

`ai.feed` and `ai.chat.message` are the module's JSON API endpoints; both require an authenticated session.

## Architecture

- **`App\Models\AiAlert`** — the alert record (`ai_alerts` table). Sequential codes `AIB-00001`; enum casts for risk level, status and recommendation; scopes `open()`, `today()`, `search()`. `source_type`/`source_id` + a unique key make analysis idempotent.
- **`App\Services\AiRiskAnalyzer`** — rule-based scoring engine. Each event type has a baseline score (0–100), then contextual modifiers are added: after-hours (22:00–05:00) +20, repetition +5 per repeat (max +25), blacklisted subject +30, sensitive zone +15, coverage gap +10. Scores map to levels: ≥80 Critical, ≥60 High, ≥35 Medium, else Low. Every triggered factor is kept and stored as the alert's `analysis` explanation. The recommendation (Notify Security Team, Lock the Door, Verify Identity, Review Camera Footage, Contact Administrator, Dispatch Technician, Ignore) is derived from the event type + risk level.
- **`App\Services\AiEventMonitor`** — the monitoring loop. `sweep()` scans everything since the last sweep (persisted in `Setting` under `ai_bot.last_sweep`): access events, system alerts (camera/device offline, failed logins, motion, unknown faces…), visitor check-ins/outs and biometric failures. Routine office-hours granted access is shown in the live feed but not stored as an alert, so the table stays high-signal. High/Critical findings fan out notifications. Sweeps run when the dashboard feed is polled or on manual scan — no queue/cron needed; add a scheduled `sweep()` call if you want fully unattended operation.
- **`App\Services\AiChatAssistant`** — keyword-intent assistant; every answer is computed from live DB queries (never invents data). Swap `answer()` for an LLM call later without touching the UI.
- **`App\Notifications\AiBotAlert`** — database channel (dashboard bell) always; mail when the user opted into email notifications. SMS and WhatsApp are placeholders logged to `laravel.log` — swap in Twilio/Vonage channels in `AiEventMonitor::notify()`.

## Sample Data

`AiAlertFactory` runs events through the real `AiRiskAnalyzer`, so seeded data carries authentic scores and explanations. `DatabaseSeeder` (idempotent) adds ~64 alerts, including fresh critical ones for today:

```
php artisan migrate
php artisan db:seed
```

## Extending

- **Real AI**: replace `AiRiskAnalyzer::analyze()` with a model call (keep the return contract) — everything downstream (alerts, notifications, UI) works unchanged.
- **New event sources**: add a `scanX()` method to `AiEventMonitor` and map onto `AiAlert::EVENT_TYPES`.
- **PDF/Excel libraries**: the project intentionally has no export packages; exports are CSV (Excel-compatible) and print-to-PDF, matching the rest of the system. Install `barryvdh/laravel-dompdf` / `maatwebsite/excel` and swap the export methods if binary formats are required.
