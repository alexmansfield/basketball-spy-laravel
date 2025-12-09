# Basketball Spy - Architecture Overview

## System Architecture

Basketball Spy is built on a **local-first, API-first, security-first** architecture designed for basketball scouts who need to work offline and sync data when connectivity is available.

## Architecture Principles

### 1. AI-First
- AI integrations should be additive and meaningful, not performative
- Enhances scout workflows naturally
- Future: AI-powered player comparisons, trend detection

### 2. Security-First (SOC 2 Compliance)
- Multi-tenant architecture with strict data isolation
- Organization-level security (multiple scouts, teams, orgs)
- Zero possibility of data crossover or breaches
- Row-level security in database queries
- API authentication via Laravel Sanctum

### 3. Local-First
- **CRITICAL**: Everything saves locally FIRST before syncing
- Scouts can work with zero network connectivity
- Partial/draft reports saved offline automatically
- Graceful degradation when network fails
- Proactive sync when network available
- Conflict resolution strategy for multi-device scenarios
- No data loss under any circumstances

### 4. API-First
- RESTful API design
- Mobile app consumes same API as potential future web dashboard
- Versioned endpoints for backward compatibility

## Technology Stack

### Backend (Laravel 12)
```
Laravel 12 (PHP 8.5)
├── Framework: Laravel
├── Database: PostgreSQL (production) / SQLite (testing)
├── Authentication: Laravel Sanctum
├── Queue: Redis (future)
├── Cache: Redis (future)
├── Storage: Laravel Cloud Storage
└── Monitoring: Laravel Telescope (dev) + Nightwatch (prod)
```

**Key Packages:**
- **Sanctum**: API token authentication
- **Pennant**: Feature flags for advanced analytics
- **Horizon**: Queue monitoring (future)
- **Telescope**: Development debugging
- **Nightwatch**: Application monitoring (production)

### Mobile (React Native + Expo)
```
React Native
├── Framework: Expo (managed workflow)
├── Database: SQLite (via expo-sqlite)
├── Sync: WatermelonDB (future) or custom sync
├── State: React Context + AsyncStorage
├── Navigation: React Navigation
├── UI: shadcn-inspired components
└── Build: EAS Build
```

## System Components

### 1. Laravel API Server

**Responsibilities:**
- User authentication and authorization
- Data storage (teams, players, reports)
- Analytics computation
- Conflict resolution for sync
- Multi-tenant data isolation

**Endpoint Categories:**
- `/api/auth` - Authentication
- `/api/teams` - Team management
- `/api/players` - Player management
- `/api/reports` - Report CRUD + sync
- `/api/analytics` - Analytics dashboards

### 2. React Native Mobile App

**Responsibilities:**
- Player evaluation UI
- Offline data storage
- Background sync
- Local search and filtering
- Analytics visualization

**Key Features:**
- Works 100% offline
- Syncs when network available
- Handles sync conflicts gracefully
- Beautiful, scout-friendly UI

### 3. PostgreSQL Database

**Responsibilities:**
- Persistent data storage
- Multi-tenant data isolation
- Analytics aggregation
- Historical data retention

**Tables:**
- `organizations` - Multi-tenant orgs
- `users` - Scouts, admins
- `teams` - NBA, WNBA, Foreign
- `players` - Player roster
- `reports` - Scout evaluations

## Data Flow

### Report Creation Flow
```
1. Scout opens mobile app (offline)
   ↓
2. Selects team and player
   ↓
3. Fills out evaluation (20 rating fields)
   ↓
4. Saves report to SQLite (local)
   ✓ Report stored locally, synced_at = null
   ↓
5. When network available, background sync starts
   ↓
6. POST /api/reports/sync with batch of local reports
   ↓
7. Server checks for conflicts (timestamp comparison)
   ↓
8. Response: { synced: [...], conflicts: [...] }
   ↓
9. App updates local SQLite with server IDs
   ✓ synced_at = timestamp
   ↓
10. If conflicts, show resolution UI to scout
```

### Sync Conflict Resolution
```
Scenario: Scout edits report on mobile while another scout
          edits same report on different device

1. Mobile App A: Updates report locally (synced_at = T1)
2. Mobile App B: Updates same report locally (synced_at = T2)
3. App A syncs first → Server accepts (updated_at = T1)
4. App B tries to sync
   ↓
5. Server detects: server updated_at (T1) > client local_updated_at (T2)
   ↓
6. Server returns conflict:
   {
     "conflicts": [{
       "id": 123,
       "server_version": { ... },
       "client_version": { ... }
     }]
   }
   ↓
7. App B shows conflict resolution UI:
   - Keep server version
   - Keep my version
   - Merge changes
```

## Multi-Tenancy Architecture

### Three-Tier Access Model

```
┌─────────────────────────────────────────┐
│  Super Admin (Service Provider - Us)   │
│  • View all organizations               │
│  • System-wide analytics                │
│  • Subscription management              │
└─────────────────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
┌───────▼──────────┐  ┌───────▼──────────┐
│ Organization A   │  │ Organization B   │
│ • Team Lakers    │  │ • Team Celtics   │
│                  │  │                  │
│ Org Admin        │  │ Org Admin        │
│ • View all scouts│  │ • View all scouts│
│ • Org analytics  │  │ • Org analytics  │
│                  │  │                  │
│ Scout 1          │  │ Scout 3          │
│ • Own reports    │  │ • Own reports    │
│                  │  │                  │
│ Scout 2          │  │ Scout 4          │
│ • Own reports    │  │ • Own reports    │
└──────────────────┘  └──────────────────┘
```

**Data Isolation Rules:**
1. Scouts can only see their own reports
2. Org Admins can see all reports in their organization
3. Super Admins can see all organizations
4. Database queries are automatically scoped by organization_id

### Implementation Pattern
```php
// Example: Get reports for user
$query = Report::where('user_id', $user->id);

if ($user->isOrgAdmin()) {
    $query = Report::whereHas('user', function($q) use ($user) {
        $q->where('organization_id', $user->organization_id);
    });
}

if ($user->isSuperAdmin()) {
    $query = Report::query(); // All reports
}
```

## Analytics Architecture

### Scout Performance Metrics
```sql
SELECT
    user_id,
    COUNT(*) as total_reports,
    COUNT(DISTINCT player_id) as players_evaluated,
    AVG((offense_shooting + offense_finishing + ... ) / 20) as avg_rating
FROM reports
WHERE user_id = ?
GROUP BY user_id
```

### Player Aggregates
```sql
SELECT
    AVG(offense_shooting) as avg_shooting,
    AVG(defense_one_on_one) as avg_defense,
    COUNT(*) as total_evaluations,
    COUNT(DISTINCT user_id) as unique_scouts
FROM reports
WHERE player_id = ?
  AND user_id IN (SELECT id FROM users WHERE organization_id = ?)
```

### System-Wide Analytics (Super Admin)
```sql
-- Organization metrics
SELECT
    o.name,
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT r.id) as total_reports,
    o.subscription_tier
FROM organizations o
LEFT JOIN users u ON u.organization_id = o.id
LEFT JOIN reports r ON r.user_id = u.id
GROUP BY o.id
```

## Security Architecture

### Authentication Flow
```
1. User enters email + password in mobile app
   ↓
2. POST /api/login
   {
     "email": "scout@team.com",
     "password": "...",
     "device_name": "iPhone 15"
   }
   ↓
3. Server validates credentials
   ↓
4. Server creates Sanctum token
   ↓
5. Response:
   {
     "token": "1|abcdefg...",
     "user": { id, name, role, organization }
   }
   ↓
6. App stores token in secure storage
   ↓
7. All future API requests include:
   Authorization: Bearer {token}
```

### Role-Based Access Control (RBAC)
```php
// Middleware checks user role
Route::middleware('role:org_admin,super_admin')->group(function () {
    Route::get('/analytics/organization', ...);
});

// In controllers
if (!$user->isSuperAdmin()) {
    $query->whereHas('user', function($q) use ($user) {
        $q->where('organization_id', $user->organization_id);
    });
}
```

## Database Schema Design

### Key Design Decisions

**1. team_id_at_time in reports table**
- Players can switch teams during season
- Historical accuracy: report records which team player was on
- Enables analytics like "how did scouts rate this player on Team A vs Team B"

**2. Nullable rating fields**
- Scouts can save partial/draft reports
- Supports offline workflow: start report, save, finish later
- Validation: all fields 1-5 when present, but can be null

**3. synced_at timestamp**
- Tracks when local report was synced to server
- NULL = not yet synced (draft or offline)
- NOT NULL = synced successfully
- Used for conflict detection during sync

**4. Soft deletes**
- Data retention for compliance and historical analysis
- deleted_at column instead of hard DELETE
- Scouts' work is never truly lost

## Performance Considerations

### Database Indexing
```sql
-- Frequently queried fields
CREATE INDEX idx_reports_user_id ON reports(user_id);
CREATE INDEX idx_reports_player_id ON reports(player_id);
CREATE INDEX idx_reports_created_at ON reports(created_at);
CREATE INDEX idx_reports_synced_at ON reports(synced_at);

-- Organization scoping
CREATE INDEX idx_users_organization_id ON users(organization_id);
```

### Pagination Strategy
- All list endpoints return paginated results (default: 15-20 per page)
- Mobile app implements infinite scroll
- Reduces memory usage and network bandwidth

### Eager Loading
```php
// Avoid N+1 queries
$reports = Report::with(['player.team', 'user:id,name'])->get();

// Instead of:
foreach ($reports as $report) {
    $report->player; // N queries
    $report->user;   // N queries
}
```

## Scalability Considerations

### Future Optimizations

**1. Caching (Redis)**
```php
// Cache team lists (rarely change)
$teams = Cache::remember('teams:nba', 3600, function () {
    return Team::where('league', 'NBA')->get();
});

// Cache player analytics
$analytics = Cache::remember("player:{$id}:analytics", 600, fn() =>
    $this->computePlayerAnalytics($id)
);
```

**2. Background Jobs (Horizon)**
```php
// Process sync batches in background
dispatch(new SyncReportsJob($reports));

// Compute heavy analytics asynchronously
dispatch(new ComputeAnalyticsJob($organizationId));
```

**3. Database Read Replicas**
- Analytics queries run on read replica
- Write operations go to primary
- Reduces load on primary database

## Deployment Architecture

### Production Environment
```
┌─────────────────────┐
│   Mobile Apps       │
│  (iOS + Android)    │
└──────────┬──────────┘
           │
           │ HTTPS
           ▼
┌─────────────────────┐
│   Laravel Cloud     │
│   (Load Balanced)   │
│                     │
│  ┌──────────────┐  │
│  │ Laravel API  │  │
│  │  (PHP-FPM)   │  │
│  └──────┬───────┘  │
│         │          │
│         ▼          │
│  ┌──────────────┐  │
│  │ PostgreSQL   │  │
│  │  (Primary)   │  │
│  └──────────────┘  │
│                     │
│  ┌──────────────┐  │
│  │   Redis      │  │
│  │ (Cache+Queue)│  │
│  └──────────────┘  │
└─────────────────────┘
```

### Monitoring & Observability
- **Laravel Telescope** (dev): Query debugging, exceptions, requests
- **Nightwatch** (prod): Application performance monitoring
- **Laravel Horizon** (future): Queue monitoring
- **Logs**: Centralized logging for debugging

---

## Next Steps

- [Laravel Backend Setup](./02-laravel-setup.md)
- [Database Schema Details](./04-database-schema.md)
- [Local-First Sync Implementation](./05-local-first-sync.md)
