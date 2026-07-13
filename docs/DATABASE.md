# Database Schema

All enum-like columns are stored as indexed `VARCHAR` and cast to PHP enums in the
models. All foreign keys are real constraints; relations to reference data use
`nullOnDelete()` so deleting hardware or people never breaks history rows.

## Tables

### users
Accounts for every role (employees are users). `role` (indexed, enum cast),
`status` (active/inactive/suspended, enforced at request time by the `active`
middleware), `last_login`, `notification_preferences` (json), `created_by` /
`updated_by` (self-FKs, null on delete), password nullable until the invitation
is accepted.

### cameras
`camera_id` (unique code), brand/type/status enums, network fields
(`ip_address`, `mac_address`, `rtsp_url`), `username` + `password`
(**encrypted cast, hidden**), placement (`location`, `building`, `floor`, `zone`),
`resolution`, `fps`, `recording_enabled`, `last_seen`.

### devices (IoT)
`device_id` (unique), type/protocol/status/signal enums, credentials (encrypted),
placement, `battery_level` (low-battery threshold = 20), `firmware_version`.

### visits (visitors)
Visitor identity + visit lifecycle: expected/check-in/check-out timestamps,
host + registrar FKs → users, `blacklisted`, badge, access level, status enum.
Sequential `visit_code` (`VST-00001`).

### doors / access_permissions / access_events
Doors link a device + camera and a required access level. Permissions belong to a
user or named visitor with schedule + validity windows (pivot `access_permission_door`).
`access_events` is the traffic log: person, badge, door, direction, result enum,
severity, method, `happened_at`.
**Indexes:** `(kind, happened_at)` composite, `result`, `severity`, `happened_at`,
`(person_name, happened_at)` composite (AI repeat-failure lookups), plus FK indexes.

### biometric_profiles / biometric_verifications
Enrollment per user (face/fingerprint/iris quality + timestamps, assigned reader)
and the verification log (result enum, subject, detail, `happened_at`).

### alerts
System alerts: `alert_code` (unique, `ALT-`), type (indexed), severity/status enums
(indexed), FKs to device/camera/door/user/visit/assignee (null on delete),
`ai_confidence`, `resolved_at`, `happened_at` (indexed).

### ai_alerts
AI Security Bot findings: `ai_code` (unique, `AIB-`), `event_type` (indexed),
`risk_level` (indexed) + `risk_score` (0-100), `analysis` (the explanation),
`recommendation`, FKs to camera/device/door/user/visit/reviewer (null on delete),
`status` (new/reviewing/actioned/resolved/false_positive, indexed),
`notified_channels` (json), `happened_at` (indexed), and the idempotency key:
**unique `(source_type, source_id, event_type)`** so monitoring sweeps never
duplicate an event.

### audit_logs
Actor snapshot (id FK + denormalized name/role), module, action, description,
status, IP, browser/OS/device, URL, HTTP method, `happened_at`.

### settings
Cached key-value store `(group, key, value json)` — appearance, mail (password
encrypted), security, plus AI bot state (`ai_bot.last_sweep`).

### Framework tables
`sessions` (database sessions), `cache`, `jobs` (database queue),
`notifications` (uuid morphs), `password_reset_tokens`.

## Seeders

`DatabaseSeeder` is **idempotent** (guarded `firstOrCreate` / count checks):
1 Super Admin (`admin@smartsecurity.test`), 15 demo users across roles, 18 cameras,
26 devices (incl. biometric readers), ~35 visits in every lifecycle state, doors +
permissions + ~320 access events, ~140 biometric verifications, ~72 system alerts,
~64 AI alerts (scored by the real `AiRiskAnalyzer`), including deliberate incident
patterns (repeated denials, blacklisted visitor, unknown faces) that light up the
AI insights.
