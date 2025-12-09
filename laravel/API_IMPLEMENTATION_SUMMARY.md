# Basketball Spy - Laravel API Implementation Summary

## ✅ Completed Infrastructure

### Database Schema
All migrations created and run successfully:

1. **organizations** - Multi-tenant support
   - id, name, subscription_tier, advanced_analytics_enabled

2. **users** (scouts) - Extended with:
   - organization_id (foreign key)
   - role (scout, org_admin, super_admin)

3. **teams** - 60 teams seeded (NBA, WNBA, Foreign)
   - All fields with logos and colors

4. **players** - Ready for seeding
   - team_id, name, jersey, position, height, weight, headshot_url

5. **reports** - Complete with all 20 rating fields
   - Offense (6 fields), Defense (4 fields), Intangibles (4 fields), Athleticism (6 fields)
   - synced_at for local-first tracking

6. **personal_access_tokens** - Laravel Sanctum for API auth

### Models & Relationships
All Eloquent models configured with:
- Proper relationships (BelongsTo, HasMany)
- Mass assignment protection
- Soft deletes
- Helper methods (isSuperAdmin, isOrgAdmin, isScout, isSynced, getAverageRatingAttribute)

## 🔧 TODO: API Controllers Implementation

The controller files have been created but need implementation. Here's what each should contain:

### TeamController (app/Http/Controllers/API/TeamController.php)
```php
public function index(Request $request)
{
    // GET /api/teams?league=NBA&search=warriors
    // Returns paginated teams, filterable by league, searchable by name
    // Include logo_url and color for mobile app
}

public function show(Team $team)
{
    // GET /api/teams/{id}
    // Returns team with players relationship loaded
}
```

### PlayerController (app/Http/Controllers/API/PlayerController.php)
```php
public function index(Request $request)
{
    // GET /api/players?team_id=1
    // Returns players for a specific team, sorted by jersey number
}

public function show(Player $player)
{
    // GET /api/players/{id}
    // Returns player with team and latest reports
}
```

### ReportController (app/Http/Controllers/API/ReportController.php)
```php
public function index(Request $request)
{
    // GET /api/reports?player_id=1&user_id=1
    // Returns reports, scoped to user's organization
    // Supports filtering by player, date range
}

public function store(Request $request)
{
    // POST /api/reports
    // Creates/updates report (upsert based on synced_at)
    // Validates all rating fields (1-5 range)
    // Sets synced_at timestamp
}

public function show(Report $report)
{
    // GET /api/reports/{id}
    // Returns single report with player/team relationships
    // Organization-scoped authorization
}

public function sync(Request $request)
{
    // POST /api/reports/sync
    // Batch endpoint for local-first sync
    // Accepts array of reports from mobile app
    // Returns conflicts for resolution
}
```

### AnalyticsController (app/Http/Controllers/API/AnalyticsController.php)
```php
public function organizationDashboard(Request $request)
{
    // GET /api/analytics/organization
    // Organization admin and super admin only
    // Returns:
    // - Scout performance metrics
    // - Player evaluation aggregates
    // - Team statistics
    // - Report quality metrics
}

public function superAdminDashboard(Request $request)
{
    // GET /api/analytics/system
    // Super admin only
    // Returns:
    // - System-wide analytics
    // - All organizations' metrics
    // - Usage statistics
    // - Subscription tier breakdown
}

public function playerAnalytics(Player $player)
{
    // GET /api/analytics/players/{id}
    // Returns aggregated ratings for a player
    // Average scores across all scouts in organization
    // Historical trend data
}
```

## 🔒 API Routes (routes/api.php)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Teams (public within auth)
    Route::apiResource('teams', TeamController::class)->only(['index', 'show']);

    // Players (public within auth)
    Route::apiResource('players', PlayerController::class)->only(['index', 'show']);

    // Reports (organization-scoped)
    Route::apiResource('reports', ReportController::class);
    Route::post('reports/sync', [ReportController::class, 'sync']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('organization', [AnalyticsController::class, 'organizationDashboard'])
            ->middleware('role:org_admin,super_admin');
        Route::get('system', [AnalyticsController::class, 'superAdminDashboard'])
            ->middleware('role:super_admin');
        Route::get('players/{player}', [AnalyticsController::class, 'playerAnalytics']);
    });
});

// Auth endpoints
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
```

## 🔐 Middleware Needed

Create `app/Http/Middleware/RoleMiddleware.php`:
```php
public function handle($request, Closure $next, ...$roles)
{
    if (!in_array($request->user()->role, $roles)) {
        abort(403, 'Unauthorized');
    }
    return $next($request);
}
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

## 📊 Analytics Queries Examples

### Organization Dashboard
```php
// Scout performance
$scoutMetrics = Report::where('user_id', $userId)
    ->selectRaw('
        user_id,
        COUNT(*) as total_reports,
        AVG((offense_shooting + offense_finishing + ... ) / 20) as avg_rating,
        COUNT(DISTINCT player_id) as players_evaluated
    ')
    ->groupBy('user_id')
    ->get();

// Player aggregates
$playerAggregates = Report::where('player_id', $playerId)
    ->selectRaw('
        AVG(offense_shooting) as avg_shooting,
        AVG(defense_one_on_one) as avg_defense,
        ...
    ')
    ->first();
```

## 🎯 Next Steps

1. Implement controller methods
2. Create RoleMiddleware
3. Set up API routes
4. Seed player data (next task)
5. Test API endpoints with Postman/Insomnia
6. Document API for React Native team
