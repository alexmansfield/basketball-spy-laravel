<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     *
     * GET /api/teams?league=NBA&search=warriors
     *
     * Supports filtering by league and searching by name.
     * Returns paginated results with logo and color for mobile app.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Team::query();

        // Filter by league (NBA, WNBA, Foreign)
        if ($request->has('league')) {
            $query->where('league', $request->league);
        }

        // Search by team name (case-insensitive)
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('abbreviation', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('nickname', 'like', "%{$search}%");
            });
        }

        // Order by league and name
        $query->orderBy('league')->orderBy('name');

        // Paginate results (15 per page by default, configurable via ?per_page=)
        $perPage = $request->get('per_page', 15);
        $teams = $query->paginate($perPage);

        return response()->json($teams);
    }

    /**
     * Display the specified team with its players.
     *
     * GET /api/teams/{id}
     *
     * Returns team details with players relationship loaded.
     */
    public function show(Team $team): JsonResponse
    {
        // Load players relationship and order by jersey number
        $team->load(['players' => function ($query) {
            $query->orderByRaw('CAST(jersey AS UNSIGNED)');
        }]);

        return response()->json($team);
    }
}
