<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlayerController extends Controller
{
    /**
     * Display a listing of players.
     *
     * GET /api/players?team_id=1
     *
     * Returns players for a specific team, sorted by jersey number.
     * If no team_id provided, returns paginated list of all players.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with('team');

        // Filter by team if provided
        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Search by player name if provided
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Order by jersey number (cast to unsigned for proper numeric sorting)
        $query->orderByRaw('CAST(jersey AS UNSIGNED)');

        // Paginate or return all if team_id is specified
        if ($request->has('team_id')) {
            $players = $query->get();
            return response()->json($players);
        } else {
            $perPage = $request->get('per_page', 20);
            $players = $query->paginate($perPage);
            return response()->json($players);
        }
    }

    /**
     * Display the specified player.
     *
     * GET /api/players/{id}
     *
     * Returns player with team and latest reports.
     */
    public function show(Player $player): JsonResponse
    {
        // Load team relationship
        $player->load('team');

        // Load latest 10 reports for this player
        $player->load(['reports' => function ($query) {
            $query->latest('created_at')->limit(10)->with('user:id,name');
        }]);

        return response()->json($player);
    }
}
