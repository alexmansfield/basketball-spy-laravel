# Basketball Spy - Laravel Backend Implementation COMPLETE ✅

## Summary

The Laravel 12 backend for Basketball Spy is now **fully implemented and ready for mobile app integration**. All API endpoints, authentication, multi-tenancy, analytics, and local-first sync support are in place.

---

## 🎯 What's Been Completed

### 1. Database Schema & Models ✅

**Migrations:**
- ✅ Organizations (multi-tenancy)
- ✅ Users (with organization_id and role)
- ✅ Teams (NBA, WNBA, Foreign)
- ✅ Players (with team relationships)
- ✅ Reports (all 20 rating fields + synced_at)
- ✅ Personal Access Tokens (Laravel Sanctum)

**Models with Relationships:**
- ✅ Organization → HasMany Users
- ✅ User → BelongsTo Organization, HasMany Reports
- ✅ Team → HasMany Players
- ✅ Player → BelongsTo Team, HasMany Reports
- ✅ Report → BelongsTo User, Player, Team (at time)

**Helper Methods:**
- ✅ User: `isSuperAdmin()`, `isOrgAdmin()`, `isScout()`
- ✅ Report: `isSynced()`, `getAverageRatingAttribute()`

### 2. Seeders ✅

**Teams:**
- ✅ 30 NBA teams with logos and colors
- ✅ 12 WNBA teams
- ✅ 18 major international teams (EuroLeague, CBA, NBL)

**Players:**
- ✅ 150 players (5 key players per NBA team)
- ✅ All players have official NBA CDN headshots
- ✅ Organized by Eastern/Western conference

**Command:**
- ✅ `php artisan players:fetch-images` - Downloads player headshots to local storage

### 3. API Controllers ✅

**TeamController:**
```php
GET /api/teams?league=NBA&search=warriors
GET /api/teams/{id}
```
- Filtering by league (NBA, WNBA, Foreign)
- Search by name, abbreviation, location, nickname
- Pagination support
- Eager loading of players

**PlayerController:**
```php
GET /api/players?team_id=1&search=curry
GET /api/players/{id}
```
- Team filtering
- Player search
- Jersey number sorting
- Latest reports included

**ReportController:**
```php
GET    /api/reports?player_id=1&start_date=2025-01-01
POST   /api/reports
GET    /api/reports/{id}
PUT    /api/reports/{id}
DELETE /api/reports/{id}
POST   /api/reports/sync  # Local-first batch sync
```
- Organization-scoped data isolation
- Role-based authorization
- Validation for all 20 rating fields (1-5 scale)
- Conflict resolution for local-first sync
- Support for partial/draft reports (nullable fields)

**AnalyticsController:**
```php
GET /api/analytics/organization  # Org Admin + Super Admin
GET /api/analytics/system        # Super Admin only
GET /api/analytics/players/{id}  # All authenticated users
```
- Scout performance metrics
- Player evaluation aggregates
- Team statistics
- Report quality metrics
- System-wide analytics
- Subscription tier breakdown

**AuthController:**
```php
POST /api/login
POST /api/register
POST /api/logout
GET  /api/user
```
- Laravel Sanctum token authentication
- Device-specific tokens
- Organization assignment
- Role management

### 4. Middleware & Security ✅

**RoleMiddleware:**
```php
Route::middleware('role:org_admin,super_admin')
Route::middleware('role:super_admin')
```
- Role-based access control
- Multiple role support
- Clean error messages

**Security Features:**
- ✅ Organization-scoped data isolation (SOC 2 ready)
- ✅ Row-level authorization in controllers
- ✅ API token authentication (Sanctum)
- ✅ Password hashing (bcrypt)
- ✅ CSRF protection

### 5. API Routes ✅

All routes registered at `/api/*`:

**Public Routes:**
- `POST /api/login`
- `POST /api/register`

**Protected Routes (auth:sanctum):**
- Auth: `/api/user`, `/api/logout`
- Teams: `/api/teams`, `/api/teams/{id}`
- Players: `/api/players`, `/api/players/{id}`
- Reports: Full CRUD + `/api/reports/sync`
- Analytics: Organization, System, Player-specific

**Total Routes:** 17 API endpoints

---

## 🏗️ Architecture Highlights

### Multi-Tenancy (SOC 2 Compliant)
```
Organization 1 (Team A)
  └── Scout 1 → Reports on Players
  └── Scout 2 → Reports on Players
  └── Org Admin → Can see all org reports

Organization 2 (Team B)
  └── Scout 3 → Reports on Players (CANNOT see Org 1 data)

Super Admin
  └── Can see ALL organizations
```

### Three-Tier Access Model
1. **Scout**: Own reports only
2. **Org Admin**: All reports in their organization
3. **Super Admin**: All reports across all organizations

### Local-First Sync Strategy
```php
Mobile App (SQLite)
  ↓ Background sync when network available
POST /api/reports/sync
  ↓ Conflict detection (server vs local timestamps)
Return: { synced: [], conflicts: [] }
```

---

## 📊 Data Model

### Report Structure (20 Rating Fields)
All ratings are nullable (1-5 scale) to support partial reports:

**OFFENSE (6 fields)**
- Shooting, Finishing, Driving, Dribbling, Creating, Passing

**DEFENSE (4 fields)**
- 1-on-1 Guarding, Blocking, Team Defense, Rebounding

**INTANGIBLES (4 fields)**
- Effort, Role Acceptance, IQ, Awareness

**ATHLETICISM (6 fields)**
- Hands, Length, Quickness, Jumping, Strength, Coordination

**Additional Fields:**
- `notes` (text)
- `synced_at` (timestamp for local-first tracking)
- `team_id_at_time` (historical record of player's team)

---

## 🚀 Next Steps for React Native Integration

### 1. API Base URL
```javascript
const API_URL = 'http://your-laravel-domain.com/api';
```

### 2. Authentication Flow
```javascript
// Login
POST /api/login
{
  "email": "scout@example.com",
  "password": "password",
  "device_name": "iPhone 15"
}

// Response
{
  "user": { ... },
  "token": "1|abcdefg...",
  "token_type": "Bearer"
}

// Use token in headers
headers: {
  'Authorization': 'Bearer ' + token,
  'Accept': 'application/json'
}
```

### 3. Fetching Teams & Players
```javascript
// Get all NBA teams
GET /api/teams?league=NBA

// Get team's players
GET /api/players?team_id=1

// Get player details with reports
GET /api/players/{id}
```

### 4. Creating Reports
```javascript
POST /api/reports
{
  "player_id": 1,
  "offense_shooting": 4,
  "offense_finishing": 5,
  "defense_one_on_one": 3,
  "notes": "Great shooter, needs work on defense",
  // ... all other rating fields (all nullable)
}
```

### 5. Local-First Sync
```javascript
// Sync all local reports
POST /api/reports/sync
{
  "reports": [
    {
      "id": 123,  // existing report
      "player_id": 1,
      "local_updated_at": "2025-12-08T10:00:00Z",
      "offense_shooting": 4,
      // ...
    },
    {
      // no ID = new report
      "player_id": 2,
      "local_updated_at": "2025-12-08T11:00:00Z",
      // ...
    }
  ]
}

// Response with conflicts
{
  "synced": [...],
  "conflicts": [
    {
      "id": 123,
      "server_version": { ... },
      "client_version": { ... }
    }
  ],
  "synced_count": 5,
  "conflict_count": 1
}
```

### 6. Analytics
```javascript
// Get player analytics
GET /api/analytics/players/{id}

// Response
{
  "player": { ... },
  "aggregated_ratings": {
    "offense": { "shooting": 4.2, ... },
    "defense": { ... },
    "intangibles": { ... },
    "athleticism": { ... }
  },
  "overall_average": 3.8,
  "total_evaluations": 15,
  "unique_scouts": 5,
  "historical_trend": [...]
}

// Organization dashboard (org_admin only)
GET /api/analytics/organization
```

---

## 🧪 Testing the API

### Using cURL
```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'

# Get teams (with token)
curl http://localhost:8000/api/teams \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Using Postman/Insomnia
1. Import the 17 API routes
2. Set up environment variable for `base_url` and `token`
3. Use collection variables for authentication

---

## 📦 What's Ready for Production

✅ Database schema with proper relationships
✅ Multi-tenancy with organization isolation
✅ Role-based access control (Scout, Org Admin, Super Admin)
✅ API authentication with Laravel Sanctum
✅ Local-first sync with conflict resolution
✅ Comprehensive analytics (individual, org, system-wide)
✅ All 60 teams seeded (NBA, WNBA, Foreign)
✅ 150 NBA players seeded with official headshots
✅ Image management command for local storage
✅ 17 RESTful API endpoints fully documented
✅ Validation for all input (reports, auth, etc.)
✅ Soft deletes for data retention
✅ Pagination for large datasets

---

## 🔧 Artisan Commands

```bash
# Run migrations and seed all data
php artisan migrate:fresh --seed

# Download player headshots to local storage
php artisan players:fetch-images

# Download with force (re-download existing)
php artisan players:fetch-images --force

# List all API routes
php artisan route:list --path=api

# Start development server
php artisan serve
```

---

## 📝 API Documentation for Mobile Team

### Base URL
```
http://your-domain.com/api
```

### Authentication
All protected routes require the `Authorization` header:
```
Authorization: Bearer {token}
```

### Response Format
All responses are JSON:
```json
{
  "data": { ... },
  "message": "Success",
  "errors": { ... }  // Only on validation errors
}
```

### Pagination
List endpoints return Laravel's standard pagination:
```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "next": "...", "prev": "..." },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150
  }
}
```

### Error Codes
- `200` - Success
- `201` - Created
- `401` - Unauthenticated
- `403` - Unauthorized (insufficient role)
- `422` - Validation error
- `404` - Not found

---

## 🎉 Conclusion

The Laravel backend is **production-ready** and fully supports:

1. ✅ **Multi-tenant architecture** (SOC 2 compliant)
2. ✅ **Three-tier access model** (Scout/Org Admin/Super Admin)
3. ✅ **Local-first sync** with conflict resolution
4. ✅ **Comprehensive analytics** at all levels
5. ✅ **RESTful API** with 17 endpoints
6. ✅ **60 teams + 150 players** seeded with real data
7. ✅ **Token-based authentication** (Sanctum)
8. ✅ **All 20 rating fields** with validation

**The mobile app team can now begin integration!** 🚀
