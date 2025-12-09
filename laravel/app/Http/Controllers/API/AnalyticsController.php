<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Player;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Organization dashboard analytics.
     *
     * GET /api/analytics/organization
     *
     * Returns organization admin and super admin analytics:
     * - Scout performance metrics
     * - Player evaluation aggregates
     * - Team statistics
     * - Report quality metrics
     */
    public function organizationDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get organization ID
        $organizationId = $user->organization_id;

        if (!$organizationId && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'No organization assigned'], 403);
        }

        // Scout Performance Metrics
        $scoutMetrics = User::where('organization_id', $organizationId)
            ->withCount('reports')
            ->with(['reports' => function ($query) {
                $query->select(
                    'user_id',
                    DB::raw('COUNT(DISTINCT player_id) as players_evaluated'),
                    DB::raw('COUNT(*) as total_reports')
                );
            }])
            ->get()
            ->map(function ($scout) {
                $reports = $scout->reports;

                // Calculate average rating across all fields
                $avgRating = null;
                if ($reports->count() > 0) {
                    $avgRating = Report::where('user_id', $scout->id)
                        ->selectRaw('
                            AVG((
                                COALESCE(offense_shooting, 0) + COALESCE(offense_finishing, 0) +
                                COALESCE(offense_driving, 0) + COALESCE(offense_dribbling, 0) +
                                COALESCE(offense_creating, 0) + COALESCE(offense_passing, 0) +
                                COALESCE(defense_one_on_one, 0) + COALESCE(defense_blocking, 0) +
                                COALESCE(defense_team_defense, 0) + COALESCE(defense_rebounding, 0) +
                                COALESCE(intangibles_effort, 0) + COALESCE(intangibles_role_acceptance, 0) +
                                COALESCE(intangibles_iq, 0) + COALESCE(intangibles_awareness, 0) +
                                COALESCE(athleticism_hands, 0) + COALESCE(athleticism_length, 0) +
                                COALESCE(athleticism_quickness, 0) + COALESCE(athleticism_jumping, 0) +
                                COALESCE(athleticism_strength, 0) + COALESCE(athleticism_coordination, 0)
                            ) / 20) as avg_rating
                        ')
                        ->first()
                        ->avg_rating;
                }

                return [
                    'scout_id' => $scout->id,
                    'scout_name' => $scout->name,
                    'total_reports' => $scout->reports_count,
                    'players_evaluated' => Report::where('user_id', $scout->id)
                        ->distinct('player_id')
                        ->count('player_id'),
                    'average_rating' => round($avgRating, 2),
                ];
            });

        // Player Evaluation Aggregates (Top 10 most evaluated players)
        $topEvaluatedPlayers = Report::select('player_id', DB::raw('COUNT(*) as evaluation_count'))
            ->whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->groupBy('player_id')
            ->orderByDesc('evaluation_count')
            ->limit(10)
            ->with('player.team')
            ->get();

        // Team Statistics
        $teamStats = Report::select('team_id_at_time', DB::raw('COUNT(*) as reports_count'))
            ->whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->groupBy('team_id_at_time')
            ->orderByDesc('reports_count')
            ->with('teamAtTime')
            ->get();

        // Report Quality Metrics
        $qualityMetrics = [
            'total_reports' => Report::whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })->count(),
            'complete_reports' => Report::whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })->whereNotNull('synced_at')->count(),
            'draft_reports' => Report::whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })->whereNull('synced_at')->count(),
            'reports_this_month' => Report::whereHas('user', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })->whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json([
            'scout_performance' => $scoutMetrics,
            'top_evaluated_players' => $topEvaluatedPlayers,
            'team_statistics' => $teamStats,
            'quality_metrics' => $qualityMetrics,
        ]);
    }

    /**
     * Super admin system-wide analytics.
     *
     * GET /api/analytics/system
     *
     * Super admin only. Returns:
     * - System-wide analytics
     * - All organizations' metrics
     * - Usage statistics
     * - Subscription tier breakdown
     */
    public function superAdminDashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Organization metrics
        $organizationMetrics = Organization::withCount(['users', 'users as scout_count' => function ($query) {
            $query->where('role', 'scout');
        }])->get()->map(function ($org) {
            $reportCount = Report::whereHas('user', function ($query) use ($org) {
                $query->where('organization_id', $org->id);
            })->count();

            return [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'subscription_tier' => $org->subscription_tier,
                'total_users' => $org->users_count,
                'total_scouts' => $org->scout_count,
                'total_reports' => $reportCount,
                'advanced_analytics_enabled' => $org->advanced_analytics_enabled,
            ];
        });

        // System-wide statistics
        $systemStats = [
            'total_organizations' => Organization::count(),
            'total_users' => User::count(),
            'total_scouts' => User::where('role', 'scout')->count(),
            'total_reports' => Report::count(),
            'total_players' => Player::count(),
            'reports_this_month' => Report::whereMonth('created_at', now()->month)->count(),
            'active_scouts_this_month' => Report::whereMonth('created_at', now()->month)
                ->distinct('user_id')
                ->count('user_id'),
        ];

        // Subscription tier breakdown
        $subscriptionBreakdown = Organization::select('subscription_tier', DB::raw('COUNT(*) as count'))
            ->groupBy('subscription_tier')
            ->get();

        return response()->json([
            'organization_metrics' => $organizationMetrics,
            'system_statistics' => $systemStats,
            'subscription_breakdown' => $subscriptionBreakdown,
        ]);
    }

    /**
     * Player-specific analytics.
     *
     * GET /api/analytics/players/{id}
     *
     * Returns aggregated ratings for a player:
     * - Average scores across all scouts in organization
     * - Historical trend data
     * - Rating breakdown by category
     */
    public function playerAnalytics(Request $request, Player $player): JsonResponse
    {
        $user = $request->user();
        $organizationId = $user->organization_id;

        // Get all reports for this player from the user's organization
        $query = Report::where('player_id', $player->id);

        if (!$user->isSuperAdmin()) {
            $query->whereHas('user', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'player' => $player->load('team'),
                'message' => 'No reports available for this player',
                'aggregated_ratings' => null,
            ]);
        }

        // Calculate aggregate ratings
        $aggregatedRatings = [
            'offense' => [
                'shooting' => round($reports->avg('offense_shooting'), 2),
                'finishing' => round($reports->avg('offense_finishing'), 2),
                'driving' => round($reports->avg('offense_driving'), 2),
                'dribbling' => round($reports->avg('offense_dribbling'), 2),
                'creating' => round($reports->avg('offense_creating'), 2),
                'passing' => round($reports->avg('offense_passing'), 2),
            ],
            'defense' => [
                'one_on_one' => round($reports->avg('defense_one_on_one'), 2),
                'blocking' => round($reports->avg('defense_blocking'), 2),
                'team_defense' => round($reports->avg('defense_team_defense'), 2),
                'rebounding' => round($reports->avg('defense_rebounding'), 2),
            ],
            'intangibles' => [
                'effort' => round($reports->avg('intangibles_effort'), 2),
                'role_acceptance' => round($reports->avg('intangibles_role_acceptance'), 2),
                'iq' => round($reports->avg('intangibles_iq'), 2),
                'awareness' => round($reports->avg('intangibles_awareness'), 2),
            ],
            'athleticism' => [
                'hands' => round($reports->avg('athleticism_hands'), 2),
                'length' => round($reports->avg('athleticism_length'), 2),
                'quickness' => round($reports->avg('athleticism_quickness'), 2),
                'jumping' => round($reports->avg('athleticism_jumping'), 2),
                'strength' => round($reports->avg('athleticism_strength'), 2),
                'coordination' => round($reports->avg('athleticism_coordination'), 2),
            ],
        ];

        // Historical trend (last 10 reports)
        $historicalTrend = $reports->sortByDesc('created_at')->take(10)->map(function ($report) {
            return [
                'date' => $report->created_at->format('Y-m-d'),
                'average_rating' => $report->average_rating,
                'scout_name' => $report->user->name,
            ];
        })->values();

        return response()->json([
            'player' => $player->load('team'),
            'aggregated_ratings' => $aggregatedRatings,
            'overall_average' => round($reports->avg(function ($report) {
                return $report->average_rating;
            }), 2),
            'total_evaluations' => $reports->count(),
            'unique_scouts' => $reports->unique('user_id')->count(),
            'historical_trend' => $historicalTrend,
        ]);
    }
}
