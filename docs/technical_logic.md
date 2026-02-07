# SARH — Architectural Blueprint (Technical Logic)
> **Version:** 1.5.0 | **Updated:** 2026-02-08
> **Scope:** Database schema, entity relationships, data flow architecture, and design decisions

---

## 1. Database Architecture Overview

### 1.1 Migration Execution Order

Migrations are timestamp-ordered to satisfy foreign key constraints:

| # | Timestamp | Migration | Tables Created | Dependencies |
|---|-----------|-----------|----------------|--------------|
| 1 | `0000_01_01_000001` | `create_branches_table` | `branches` | None |
| 2 | `0000_01_01_000002` | `create_departments_table` | `departments` | `branches` |
| 3 | `0000_01_01_000003` | `create_roles_permissions_tables` | `roles`, `permissions`, `role_permission` | None |
| 4 | `0001_01_01_000000` | `create_users_table` | `users`, `password_reset_tokens`, `sessions` | `branches`, `departments`, `roles` |
| 5 | `0001_01_01_000001` | `create_cache_table` | `cache`, `cache_locks` | None (Laravel default) |
| 6 | `0001_01_01_000002` | `create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` | None (Laravel default) |
| 7 | `2024_01_02_000001` | `create_attendance_logs_table` | `attendance_logs` | `users`, `branches` |
| 8 | `2024_01_02_000002` | `create_financial_reports_table` | `financial_reports` | `users`, `branches`, `departments` |
| 9 | `2024_01_02_000003` | `create_whistleblower_reports_table` | `whistleblower_reports` | `users` |
| 10 | `2024_01_02_000004` | `create_messaging_tables` | `conversations`, `conversation_participants`, `messages`, `circulars`, `circular_acknowledgments`, `performance_alerts` | `users`, `branches`, `departments`, `roles` |
| 11 | `2024_01_02_000005` | `create_gamification_tables` | `badges`, `user_badges`, `points_transactions` | `users` |
| 12 | `2024_01_02_000006` | `create_trap_interactions_table` | `trap_interactions` | `users` |
| 13 | `2024_01_02_000007` | `create_operational_tables` | `leave_requests`, `shifts`, `user_shifts`, `audit_logs`, `holidays` | `users`, `branches` |

**Total tables:** 26 (20 custom + 6 Laravel default)

---

### 1.2 Entity Relationship Map

```
branches ─┬── departments ──── users ─┬── attendance_logs
           │        │                   ├── leave_requests
           │        │                   ├── financial_reports
           │        │                   ├── messages
           │        │                   ├── performance_alerts
           │        │                   ├── trap_interactions
           │        │                   ├── points_transactions
           │        │                   ├── audit_logs
           │        │                   └── [self-ref: direct_manager_id]
           │        │
           │        └── financial_reports (scope=department)
           │
           ├── attendance_logs
           ├── financial_reports (scope=branch)
           └── holidays

roles ─── role_permission ─── permissions

users ─── user_badges ─── badges
users ─── conversation_participants ─── conversations ─── messages
users ─── user_shifts ─── shifts
users ─── circular_acknowledgments ─── circulars

whistleblower_reports (anonymous — no FK to reporter)
```

---

## 2. Core Data Flow Architecture

### 2.1 Attendance Check-In Flow

```
Employee GPS → Branch.distanceTo(lat, lng) [Haversine]
    │
    ├── distance ≤ geofence_radius (17m) → check_in_within_geofence = true
    │
    ├── Compare check_in_at vs Shift.start_time + grace_period
    │   ├── Within grace → status = 'present', delay_minutes = 0
    │   └── Beyond grace → status = 'late', delay_minutes = diff
    │
    └── Snapshot Financial Data:
        ├── cost_per_minute = User.cost_per_minute (calculated accessor)
        ├── delay_cost = delay_minutes × cost_per_minute
        ├── early_leave_cost = early_leave_minutes × cost_per_minute
        └── overtime_value = overtime_minutes × cost_per_minute × 1.5
```

### 2.2 Financial Report Generation Flow

```
Input: scope (employee|branch|department|company), period (start, end)
    │
    ├── Query AttendanceLogs for scope+period
    │
    ├── Aggregate:
    │   ├── total_delay_minutes = SUM(delay_minutes)
    │   ├── total_delay_cost = SUM(delay_cost)
    │   ├── total_early_leave_cost = SUM(early_leave_cost)
    │   ├── total_overtime_cost = SUM(overtime_value)
    │   └── net_financial_impact = delay_cost + early_leave_cost - overtime_cost
    │
    └── Calculate:
        └── loss_percentage = (total_delay_cost / total_salary_budget) × 100
```

### 2.3 RBAC Authorization Flow

```
User Action Request
    │
    ├── is_super_admin == true → ALLOW (bypass all checks)
    │
    ├── Check User.role.permissions for required slug
    │   ├── Permission exists → ALLOW
    │   └── Permission missing → DENY
    │
    └── Security Level Check:
        └── User.security_level >= required_level → ALLOW
```

---

## 3. Schema Design Decisions

### 3.1 Cost-Per-Minute Snapshot Pattern

**Problem:** If an employee's salary changes mid-month, historical attendance records would show incorrect financial data if they query the user's current salary.

**Solution:** Each `attendance_logs` row stores a **snapshot** of `cost_per_minute` at check-in time. This creates an immutable financial record:

```
attendance_logs.cost_per_minute = User.basic_salary / (working_days × hours × 60)
attendance_logs.delay_cost      = delay_minutes × cost_per_minute  [Pre-calculated]
```

The `User` model provides this as a **computed accessor** (`getCostPerMinuteAttribute()`), and the `AttendanceLog.calculateFinancials()` method copies it at check-in.

### 3.2 Self-Referential Manager Hierarchy

`users.direct_manager_id → users.id` enables:
- `User.directManager()` — who manages this user
- `User.subordinates()` — all users this person manages
- `User.canManage(target)` — security_level comparison

### 3.3 Anonymous Whistleblower Design

No `user_id` foreign key on `whistleblower_reports`. Anonymity is enforced at the schema level:
- `ticket_number` — public tracking (e.g., `WB-A3F1B2C4-260207`)
- `anonymous_token` — SHA-256 hashed, given to reporter for follow-up
- `encrypted_content` — AES-256 via Laravel `encrypt()`

### 3.4 Polymorphic Points Transactions

`points_transactions` uses Laravel's `morphs('sourceable')` pattern:
- `sourceable_type` = `App\Models\AttendanceLog` → earned for on-time check-in
- `sourceable_type` = `App\Models\Badge` → earned from badge award
- This allows **any future model** to award/deduct points without schema changes

### 3.5 Hierarchical Departments

`departments.parent_id → departments.id` allows nesting (e.g., IT → Development → Frontend). Each department belongs to one branch.

### 3.6 Soft Deletes Strategy

Applied to: `users`, `branches`, `departments`, `messages`, `circulars`, `leave_requests`

**NOT** applied to: `attendance_logs`, `audit_logs`, `trap_interactions`, `financial_reports` — these are immutable records.

---

## 4. Index Strategy

| Table | Index | Purpose |
|-------|-------|---------|
| `users` | `(branch_id, status)` | Filter active users by branch |
| `users` | `(department_id, status)` | Filter active users by department |
| `users` | `security_level` | RBAC level filtering |
| `attendance_logs` | `UNIQUE(user_id, attendance_date)` | One record per employee per day |
| `attendance_logs` | `(branch_id, attendance_date)` | Branch daily reports |
| `attendance_logs` | `(status, attendance_date)` | Status-based queries |
| `financial_reports` | `(scope, period_start, period_end)` | Report filtering |
| `trap_interactions` | `(user_id, trap_type)` | Per-user trap analysis |
| `trap_interactions` | `(risk_level, is_reviewed)` | Pending review queue |
| `performance_alerts` | `(user_id, is_read)` | Unread alerts per user |
| `audit_logs` | `(auditable_type, auditable_id)` | Model-specific audit trail |
| `audit_logs` | `created_at` | Chronological browsing |

---

## 5. Eloquent Model Map

| Model | Table | Key Traits | SoftDeletes |
|-------|-------|------------|-------------|
| `User` | `users` | `HasFactory`, `Notifiable`, `SoftDeletes` | ✅ |
| `Branch` | `branches` | `HasFactory`, `SoftDeletes` | ✅ |
| `Department` | `departments` | `HasFactory`, `SoftDeletes` | ✅ |
| `Role` | `roles` | `HasFactory` | ❌ |
| `Permission` | `permissions` | `HasFactory` | ❌ |
| `AttendanceLog` | `attendance_logs` | `HasFactory` | ❌ |
| `FinancialReport` | `financial_reports` | `HasFactory` | ❌ |
| `WhistleblowerReport` | `whistleblower_reports` | `HasFactory` | ❌ |
| `Conversation` | `conversations` | `HasFactory` | ❌ |
| `Message` | `messages` | `HasFactory`, `SoftDeletes` | ✅ |
| `Circular` | `circulars` | `HasFactory`, `SoftDeletes` | ✅ |
| `CircularAcknowledgment` | `circular_acknowledgments` | `HasFactory` | ❌ |
| `PerformanceAlert` | `performance_alerts` | `HasFactory` | ❌ |
| `Badge` | `badges` | `HasFactory` | ❌ |
| `PointsTransaction` | `points_transactions` | `HasFactory` | ❌ |
| `TrapInteraction` | `trap_interactions` | `HasFactory` | ❌ |
| `LeaveRequest` | `leave_requests` | `HasFactory`, `SoftDeletes` | ✅ |
| `Shift` | `shifts` | `HasFactory` | ❌ |
| `AuditLog` | `audit_logs` | `HasFactory` | ❌ |
| `Holiday` | `holidays` | `HasFactory` | ❌ |

---

## 6. Naming Convention Compliance

| Element | Convention | Status |
|---------|-----------|--------|
| DB columns | `snake_case` | ✅ Enforced |
| Model names | `PascalCase` | ✅ |
| Method names | `camelCase` | ✅ |
| Relationships | `camelCase` | ✅ |
| Pivot tables | alphabetical `role_permission`, `user_badges` | ✅ |
| Migration files | `snake_case` with timestamp prefix | ✅ |
| Route names | `snake_case` (pending) | ⏳ |
| Config keys | `snake_case` | ✅ |

---

## 7. Phase 1 — Attendance & Geofencing Service Layer

### 7.1 Service Architecture

```
PWA (Browser Geolocation API)
    │
    └── POST /attendance/check-in  {latitude, longitude}
            │
            ├── AttendanceController@checkIn
            │       │
            │       ├── GeofencingService::validatePosition(Branch, lat, lng)
            │       │       ├── Haversine distance calculation
            │       │       └── Returns: {distance_meters, within_geofence}
            │       │
            │       └── AttendanceService::checkIn(User, lat, lng, ip, device)
            │               ├── 1. Load user's branch
            │               ├── 2. GeofencingService → distance + geofence status
            │               ├── 3. Resolve current shift (User.currentShift())
            │               ├── 4. Create AttendanceLog record
            │               ├── 5. AttendanceLog.evaluateAttendance(shift_start, grace)
            │               ├── 6. AttendanceLog.calculateFinancials() — SNAPSHOT
            │               ├── 7. Save to DB
            │               └── 8. Return AttendanceLog
            │
            └── POST /attendance/check-out  {latitude, longitude}
                    │
                    └── AttendanceService::checkOut(User, lat, lng)
                            ├── 1. Find today's log
                            ├── 2. GeofencingService → checkout geofence
                            ├── 3. Calculate worked_minutes from check_in/check_out diff
                            ├── 4. Calculate overtime / early_leave
                            ├── 5. Recalculate financials
                            └── 6. Save + return
```

### 7.2 GeofencingService — Haversine Implementation

**File:** `app/Services/GeofencingService.php`

```
Haversine Formula (Earth as sphere, R = 6,371,000 m):

  Δlat = lat₂ - lat₁  (in radians)
  Δlng = lng₂ - lng₁  (in radians)

  a = sin²(Δlat/2) + cos(lat₁) × cos(lat₂) × sin²(Δlng/2)
  c = 2 × atan2(√a, √(1-a))
  distance = R × c

  Accuracy: ±0.5m for distances < 100m (sufficient for 17m geofence)
```

The service delegates to `Branch::distanceTo()` for the actual calculation, keeping the model as the single source of truth for Haversine math.

### 7.3 Financial Snapshot Mechanism

```
On CHECK-IN:
  attendance_logs.cost_per_minute = User.getCostPerMinuteAttribute()
    → basic_salary / (working_days × working_hours × 60)

On EVALUATE:
  attendance_logs.delay_cost = delay_minutes × cost_per_minute
  attendance_logs.early_leave_cost = early_leave_minutes × cost_per_minute
  attendance_logs.overtime_value = overtime_minutes × cost_per_minute × 1.5

IMMUTABILITY GUARANTEE:
  Once check-in occurs, cost_per_minute is FROZEN in the attendance_log row.
  Even if User.basic_salary changes the next day, historical records remain accurate.
```

### 7.4 Attendance Status Decision Tree

```
check_in_at == null?
  ├── YES → status = 'absent'
  └── NO
        │
        check_in_within_geofence == false?
        ├── YES → status = 'late' (flagged: out-of-geofence)
        │         check_in REJECTED by controller (HTTP 422)
        └── NO
              │
              check_in_at ≤ shift_start + grace_period?
              ├── YES → status = 'present', delay_minutes = 0
              └── NO  → status = 'late', delay_minutes = diff in minutes
```

### 7.5 Check-Out Financial Calculation

```
worked_minutes = diff(check_out_at, check_in_at) in minutes
expected_minutes = Shift.duration_minutes

IF worked_minutes < expected_minutes:
  early_leave_minutes = expected_minutes - worked_minutes
  early_leave_cost = early_leave_minutes × cost_per_minute

IF worked_minutes > expected_minutes:
  overtime_minutes = worked_minutes - expected_minutes
  overtime_value = overtime_minutes × cost_per_minute × 1.5
```

---

## 8. Phase 2 — Psychological Trap System & Logarithmic Risk Engine

### 8.1 Trap Registry Schema

```
traps
  ├── id
  ├── name_ar        — Arabic display name
  ├── name_en        — English display name
  ├── trap_code      — UNIQUE slug (e.g., SALARY_PEEK)
  ├── description_ar — Arabic explanation
  ├── description_en — English explanation
  ├── risk_weight    — 1-10, multiplier for severity
  ├── fake_response_type — enum: success | error | warning
  ├── is_active      — boolean
  └── timestamps

trap_interactions (UPDATED)
  ├── trap_id → FK to traps  (NEW — replaces free-text trap_type)
  └── metadata → json  (replaces interaction_data naming)
```

### 8.2 Logarithmic Risk Scoring Algorithm

```
Formula: NewPoints = 10 × (2^n − 1)

Where:
  n = COUNT of trap_interactions for this specific user
  (all-time, across ALL trap types)

Progression table:
  n=1  →  10 × (2¹ − 1) =   10 points
  n=2  →  10 × (2² − 1) =   30 points
  n=3  →  10 × (2³ − 1) =   70 points
  n=4  →  10 × (2⁴ − 1) =  150 points
  n=5  →  10 × (2⁵ − 1) =  310 points
  n=6  →  10 × (2⁶ − 1) =  630 points
  n=10 → 10 × (2¹⁰ − 1) = 10230 points

Math guarantee:
  Each subsequent trigger is MORE costly than the sum of all previous triggers.
  This creates a powerful deterrent: a 2nd mistake costs 3× the first.
```

### 8.3 TrapResponseService Architecture

```
User clicks trap element
    │
    └── TrapController@trigger (POST /traps/trigger)
            │
            ├── 1. Validate: trap_code, request metadata
            ├── 2. Resolve Trap model by trap_code
            ├── 3. Create TrapInteraction record
            │       ├── user_id, trap_id, ip_address, user_agent
            │       ├── metadata (JSON: page_url, click_coords, timing)
            │       └── risk_level = derived from trap.risk_weight
            │
            ├── 4. User::incrementRiskScore()
            │       ├── Count existing interactions (n)
            │       ├── new_score = 10 × (2^(n+1) − 1)
            │       └── forceFill(['risk_score' => new_score])->save()
            │
            └── 5. TrapResponseService::generateFakeResponse(Trap)
                    ├── SALARY_PEEK    → fake salary table JSON
                    ├── PRIVILEGE_ESCALATION → success message JSON
                    ├── SYSTEM_BYPASS  → warning confirmation JSON
                    └── DATA_EXPORT    → {progress_url, download_url (encoded/empty)}
```

### 8.4 Risk Level Classification

```
risk_weight (trap) → risk_level (interaction):
  1-3  → 'low'
  4-6  → 'medium'
  7-8  → 'high'
  9-10 → 'critical'
```

---

## 9. Phase 3 — Employee PWA Architecture

### 9.1 Technology Stack

```
Frontend:
  ├── Livewire 3       — Server-driven reactive components
  ├── Alpine.js        — Client-side interactivity (bundled with Livewire)
  ├── Tailwind CSS     — Utility-first styling with RTL support
  ├── Tajawal Font     — Google Fonts, Arabic-first typography
  └── Blade Views      — RTL layout with dir="rtl" / dir="ltr" toggle

Component Architecture:
  layouts/
    └── pwa.blade.php           — RTL master layout with sidebar navigation
  livewire/
    ├── employee-dashboard      — Container for all widgets
    ├── attendance-widget        — Real-time GPS status with check-in/out
    ├── gamification-widget      — Points, streaks, badges display
    ├── financial-widget         — Discipline score & delay costs
    ├── circulars-widget         — Latest circulars with acknowledgment
    ├── whistleblower-form       — Anonymous encrypted report submission
    ├── whistleblower-track      — Track report by anonymous token
    ├── messaging-inbox          — Conversations list with unread counts
    └── messaging-chat           — Single conversation with real-time messages
```

### 9.2 Whistleblower Encryption Flow

```
Employee opens /whistleblower (no auth required for anonymity)
    │
    ├── 1. Fills form: category, severity, content (plaintext)
    │
    ├── 2. On submit (WhistleblowerForm Livewire component):
    │       ├── Generate ticket_number = WB-{8hex}-{yymmdd}
    │       ├── Generate anonymous_token = SHA-256(random_bytes(32))
    │       ├── Encrypt content: encrypt($plaintext)  ← AES-256-CBC
    │       └── Store WhistleblowerReport (no user_id, no FK)
    │
    ├── 3. Display to user:
    │       ├── ticket_number (for reference)
    │       └── anonymous_token (for follow-up — shown ONCE)
    │
    └── 4. Only security_level >= 10 can decrypt via Filament panel
```

### 9.3 Messaging Architecture

```
Conversation System:
  ├── Direct (1-to-1)   — Two participants
  ├── Group              — Multiple participants
  └── Broadcast          — Circulars with acknowledgment tracking

Message Flow:
  User opens /messaging → MessagingInbox component
    ├── Lists conversations with latest message preview
    ├── Shows unread count per conversation
    └── Click → opens MessagingChat component
          ├── Messages displayed in bubble format (RTL)
          ├── New message via Livewire form submission
          ├── Mark messages as read on view
          └── Real-time updates via Livewire polling (3s)

Circular Acknowledgment:
  Admin publishes circular → employees see it in CircularsWidget
    ├── Employee clicks "قرأت واطلعت" (I have read this)
    ├── Creates CircularAcknowledgment record
    └── Admin can track who read vs. who hasn't
```

### 9.4 Trap Integration in PWA

```
Traps are rendered as normal-looking UI elements in the dashboard:
  ├── SALARY_PEEK      — Button in sidebar: "عرض رواتب الزملاء"
  ├── DATA_EXPORT      — Button in footer: "تصدير كل السجلات"
  └── Clicks trigger Alpine.js → POST /traps/trigger → show fake response

The traps MUST be indistinguishable from real features.
Only users with is_trap_target=true see the trap elements.
```

---

## 10. Command Center — Aggregation & Security Architecture (v1.4.0)

### 10.1 Financial Aggregation Engine

```
FinancialReportingService
├── getDailyLoss(date, ?branch_id)
│   └── SUM(attendance_logs.delay_cost) WHERE attendance_date = date
├── getBranchPerformance(month)
│   └── Per-branch: on_time_rate, geofence_compliance, total_loss
├── getDelayImpactAnalysis(start, end, scope, ?scope_id)
│   └── potential_loss = total_delay_minutes × avg_cost_per_minute
│   └── actual_loss = SUM(delay_cost)
│   └── roi = (potential - actual) / potential × 100
└── getPredictiveMonthlyLoss(month)
    └── avg_daily = SUM(delay_cost this month) / working_days_elapsed
    └── predicted = avg_daily × remaining_working_days + accumulated
```

### 10.2 Security Gates for Level 10 Data

```
Whistleblower Vault:
├── Gate: auth()->user()->security_level >= 10
├── Decryption: decrypt(encrypted_content) — only in Filament page render
├── Audit: AuditLog::record('vault_access', $report) on every view
└── No export/download — view-only in browser

Trap Interaction Audit:
├── Gate: same security_level >= 10
├── Full interaction_data JSON display
├── Risk trajectory chart: user's risk_score over time via trap interactions
└── Audit: logged every access
```

### 10.3 Widget Architecture (Filament Dashboard)

| Widget | Class | Type | Sort | Level |
|--------|-------|------|------|-------|
| RealTimeLossCounter | StatsOverviewWidget | Stats | 0 | All admin |
| BranchPerformanceHeatmap | TableWidget | Table | 1 | All admin |
| IntegrityAlertHub | TableWidget | Table | 3 | Level 10 only |

---

## 11. Changelog

| Date | Version | Changes |
|------|---------|--------|
| 2026-02-07 | 1.0.0 | Initial database schema — 13 migrations, 20 models, 2 seeders, complete RBAC with 10 levels and 42 permissions |
| 2026-02-07 | 1.1.0 | Phase 1 — Attendance & Geofencing service layer with GeofencingService, AttendanceService, AttendanceController, Filament AttendanceResource |
| 2026-02-07 | 1.2.0 | Phase 2 — Psychological Trap System: traps table, TrapResponseService, logarithmic risk scoring (10→30→70→150→310), Filament TrapResource + TrapInteractionResource + RiskWidget |
| 2026-02-07 | 1.3.0 | Phase 3 — Employee PWA: Livewire 3 components (dashboard, widgets, messaging, whistleblower), Tailwind RTL layout, Tajawal font, trap integration, circular acknowledgments |
| 2026-02-08 | 1.4.0 | Phase 4 — Command Center: FinancialReportingService, 3 dashboard widgets (RealTimeLossCounter, BranchPerformanceHeatmap, IntegrityAlertHub), WhistleblowerVault + TrapAuditLog Filament pages, predictive analytics, security gate for Level 10 |
| 2026-02-08 | 1.5.0 | Phase 5 (Final) — Production Hardening: BranchScope policy, caching layer for financial queries, performance indexes migration, sarh:install Artisan command, Vite prod optimization, bilingual audit, README_PROD.md deployment guide |

---

## 12. Phase 5 — Production Hardening & Security Architecture (Final)

### 12.1 Security Hardening: BranchScope Policy

```
Problem: Non-Super Admin users in Filament could see data from all branches.
Solution: Global BranchScope middleware applied to AttendanceLog queries in Filament.

Logic:
  IF user.is_super_admin → no scope (sees all)
  ELSE → auto-filter by user.branch_id

Applied to:
  ├── AttendanceLogResource (Filament table query)
  ├── FinancialReportingService (getDailyLoss, getBranchPerformance)
  └── No global scope on model (avoids test contamination)
```

### 12.2 Caching Strategy

```
Cache Driver: config('cache.default') — file/redis/database
Cache TTL: 300 seconds (5 minutes) for financial aggregations

Cached Methods:
  ├── getDailyLoss(date, branch)     → key: sarh.loss.{date}.{branch}
  ├── getBranchPerformance(month)    → key: sarh.perf.{month}
  └── getPredictiveMonthlyLoss(month)→ key: sarh.predict.{month}

Non-Cached (real-time):
  └── getDelayImpactAnalysis() — on-demand report, user-triggered

Cache Invalidation:
  └── TTL-based (auto-expire after 5 minutes)
  └── Manual: php artisan cache:clear
```

### 12.3 Performance Index Migration

```
Migration: add_production_indexes

attendance_logs:
  ├── INDEX(delay_cost)           — SUM aggregations in loss counter
  ├── INDEX(user_id, status)      — Employee performance queries
  └── INDEX(attendance_date, delay_cost) — Daily loss sum optimization

trap_interactions:
  ├── INDEX(trap_id)              — JOIN with traps table
  ├── INDEX(created_at)           — Chronological audit trail
  └── INDEX(user_id, created_at)  — Risk trajectory per user

audit_logs:
  ├── INDEX(user_id)              — User audit trail
  └── INDEX(action)               — Action-type filtering
```

### 12.4 Installation Command: `php artisan sarh:install`

```
Step 1: Verify environment
  ├── Check PHP >= 8.2
  ├── Check required extensions (openssl, pdo, mbstring, tokenizer, xml, ctype, json, bcmath)
  ├── Check APP_KEY is set
  └── Check database connection

Step 2: Run migrations
  └── php artisan migrate --force

Step 3: Seed core data
  ├── RolesAndPermissionsSeeder (10 roles + 42 permissions)
  ├── BadgesSeeder (8 badges)
  └── TrapsSeeder (4 psychological traps)

Step 4: Create Super Admin (Level 10)
  ├── Prompt: Name (AR), Name (EN), Email, Password
  ├── Assign role: super_admin
  └── Call: setSecurityLevel(10) + promoteToSuperAdmin()

Step 5: Finalize
  ├── php artisan storage:link
  ├── php artisan config:cache
  ├── php artisan route:cache
  └── Display success summary
```

### 12.5 Vite Production Build

```
vite.config.js optimizations:
  ├── CSS purging via Tailwind (content paths scoped)
  ├── Build target: 'es2020' (modern browsers for PWA)
  ├── Minification: esbuild (default)
  └── Build command: npm run build → public/build/

PWA Asset Strategy:
  ├── CSS: resources/css/app.css → bundled + purged
  ├── JS:  resources/js/app.js  → bundled + tree-shaken
  └── Manifest: public/build/manifest.json (generated by Vite)
```
