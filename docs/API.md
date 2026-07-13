# API Documentation

The application is session-based (no token API). All endpoints require an
authenticated, active session and the CSRF token on mutations. JSON endpoints power
the live features and are the natural seam for a future mobile/REST API (move them
under `routes/api.php` with Sanctum tokens — controllers already return JSON).

## JSON endpoints

| Method | URI | Access | Returns |
|---|---|---|---|
| GET | `/notifications/feed` | any active user | `{unread, items[{id,title,detail,severity,badge,time,read}]}` — top-bar bell, polled 30s |
| POST | `/notifications/{id}/read` | owner | `{ok}` |
| POST | `/notifications/read-all` | owner | `{ok}` |
| GET | `/alerts/feed` | any active user | latest alerts + access events for the notification center |
| GET | `/access/feed` | roles with access-module view | live door activity feed |
| GET | `/ai-bot/feed` | admins + security operators | `{openCount, todayCount, criticalToday, lastSweep, items[]}` — also triggers a throttled monitoring sweep |
| GET | `/cameras/live/feed` | any active user | `{cameras[{id,status,statusLabel,badge,fps,recording,detection{label,tone}}]}` — polled 10s by the Live Monitoring wall |
| POST | `/ai-bot/chat` | administrators only | `{reply, rows[]}` — body `{"message": "..."}` |

## Form endpoints (server-rendered, CSRF-protected)

Standard resource routes for `users`, `cameras`, `devices`, `visitors`,
`biometrics`, plus module actions — see `routes/web.php`; every mutation is
authorized by policy/FormRequest/capability check. Notable:

| Method | URI | Access |
|---|---|---|
| PATCH | `/users/{user}/role` | Super Admin only — promote/demote (never self, never another Super Admin) |
| POST | `/ai-bot/scan` | admins — run a monitoring sweep |
| PATCH | `/ai-bot/alerts/{aiAlert}` | admins — review status + notes |
| GET | `/ai-bot/history/export` | admins — filtered CSV |
| GET | `/ai-bot/report?date=YYYY-MM-DD` | AI-bot roles — printable daily report |

## Exports

All exports are CSV (Excel-compatible) streamed via `streamDownload`, capped at
5,000–10,000 rows, and restricted to the roles that manage the module (they contain
PII). Printable pages (`/ai-bot/report`, visitor badge/pass) use browser print-to-PDF.
