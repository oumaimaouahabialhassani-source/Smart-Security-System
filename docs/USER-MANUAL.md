# User Manual — Smart Security System

## Signing in

Open the application URL and enter your email and password. There is no public
sign-up: accounts are created by an Administrator. Five failed attempts lock the
form temporarily (the threshold is configurable in Settings → Security). Use
"Forgot password?" to receive a reset link by email. If your account is suspended
or deactivated, your session ends on your next action.

## Roles at a glance

| Capability | Administrator | Security Officer | Manager | Receptionist | Employee |
|---|:-:|:-:|:-:|:-:|:-:|
| View dashboards & modules | ✔ | ✔ | ✔ | ✔ | ✔ |
| Manage users | ✔ | — | — | — | — |
| Register/edit visitors, print badges, export | ✔ | ✔ | — | ✔ | — |
| Check visitors in/out | ✔ | ✔ | — | — | — |
| Manage cameras & IoT devices | ✔ | ✔ | — | — | — |
| Enroll/verify biometrics, export | ✔ | ✔ | — | — | — |
| Access permissions, door lock/unlock, export logs | ✔ | ✔ | — | — | — |
| Temporary visitor access | ✔ | ✔ | — | ✔ | — |
| Lockdown (Lock All Doors), delete permissions | ✔ | — | — | — | — |
| Manage alerts (assign/resolve/export) | ✔ | ✔ | — | — | — |
| Reports & Analytics | ✔ | — | ✔ | — | — |
| Settings, backups, audit logs | ✔ | — | — | — | — |

## Modules

### Dashboard
Live overview: today's entries, denied attempts, camera/device health, weekly
access chart and the latest access events.

### Users (Administrator)
Create staff accounts (role + status), edit, suspend or delete. Safety rails: you
cannot delete yourself, and the last active Administrator can never be demoted,
suspended or deleted.

### Visitors
Register a visitor (identity, host employee, purpose, security screening), then
**Check In** on arrival and **Check Out** on departure — both movements are written
to the access log automatically. Print a **badge** (card) or **pass** (full page)
from the row actions. Blacklisted visitors trigger a security alert on check-in
attempts. Filter by status/department/date, export the filtered list as CSV.

### Cameras & IoT Devices
Hardware inventory with status, location, battery and signal monitoring. Low
battery and offline states surface on the dashboard and as alerts.

### Biometrics
Enroll a face (camera capture), fingerprint or iris for any user, then run
**Verify Identity** to simulate a reader check — every attempt is logged, mirrored
into the access log, and failures raise alerts. The device panel lets
administrators restart or sync readers.

### Access Control
Grant permanent or **temporary** (time-boxed) door permissions, per door, schedule
and access level. Lock or unlock individual doors; **Lock All Doors** is the
administrator-only lockdown. **Access Logs** lists every attempt with result
badges (granted, denied, expired badge, unauthorized…), filters and CSV export.
The live feed refreshes automatically.

### Alerts
The SOC page: every alert with severity, status lifecycle (New → Investigating →
Resolved/Closed), assignment to an officer, notes, facility map, insights and
system health. The bell in the top bar polls for new alerts on every page.
Configure which notifications you receive under Notification Settings.

### Reports & Analytics (Administrator, Manager)
Nine tabs of cross-module KPIs and charts (visitors, access heatmap, biometrics,
alerts, audit…) with a global date-range filter, per-section CSV export and print.

### Settings (Administrator)
Ten groups: company identity, security policy (password rules, session timeout,
login attempts), notifications, camera/biometric/device defaults, appearance
(logo, theme, accent color), email/SMTP with test send, backups (manual +
scheduled, download/restore), and system info.

### Audit Logs (Administrator)
Automatic trail of every significant action — logins and failures, CRUD in every
module, role and status changes, settings edits, door commands — with user, role,
IP, browser/OS, URL and method. Filter and export as CSV.

## Tips

- Every table has a search box and filters; the **Export CSV** button respects the
  active filters (shown only to roles allowed to export that module).
- **Print** buttons produce printer-friendly output — use "Save as PDF" in the
  print dialog for PDF reports.
- The theme (dark/light) and accent color follow Settings → Appearance.
