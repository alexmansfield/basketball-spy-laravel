<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\PlayerController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\AnalyticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Basketball Spy API Routes
| All routes are prefixed with /api and use Sanctum authentication
|
*/

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Teams (public within auth)
    Route::apiResource('teams', TeamController::class)->only(['index', 'show']);

    // Players (public within auth)
    Route::apiResource('players', PlayerController::class)->only(['index', 'show']);

    // Reports (organization-scoped)
    Route::apiResource('reports', ReportController::class);
    Route::post('reports/sync', [ReportController::class, 'sync']);

    // Analytics
    Route::prefix('analytics')->group(function () {
        // Organization dashboard (org_admin and super_admin only)
        Route::get('organization', [AnalyticsController::class, 'organizationDashboard'])
            ->middleware('role:org_admin,super_admin');

        // System-wide dashboard (super_admin only)
        Route::get('system', [AnalyticsController::class, 'superAdminDashboard'])
            ->middleware('role:super_admin');

        // Player analytics (all authenticated users)
        Route::get('players/{player}', [AnalyticsController::class, 'playerAnalytics']);
    });
});
