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
     * GET /api/players?team_id=1&sort=minutes
     *
     * Returns players for a specific team, sorted by jersey number by default.
     * Use sort=minutes to sort by combined minutes rank (requires SportsBlaze API data).
     *
     * If no team_id provided, returns paginated list of all players.
     */
    public function index(Request $request): JsonResponse
    {
        $teamId = $request->input('team_id');
        $search = $request->input('search');
        $sort = $request->input('sort', 'jersey'); // Default to jersey sorting

        if ($sort === 'minutes') {
            $players = $this->getPlayersRankedByMinutes($teamId, $search);
        } else {
            $players = $this->getPlayersSortedByJersey($teamId, $search);
        }

        // Apply pagination if no team_id specified
        if (!$teamId) {
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;

            $total = $players->count();
            $paginatedPlayers = $players->slice($offset, $perPage)->values();

            return response()->json([
                'data' => $paginatedPlayers,
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ]);
        }

        return response()->json($players);
    }

    /**
     * Get players sorted by jersey number.
     */
    protected function getPlayersSortedByJersey(?string $teamId, ?string $search)
    {
        $query = Player::with('team');

        if ($teamId) {
            // Support lookup by numeric ID or team abbreviation
            if (is_numeric($teamId)) {
                $query->where('team_id', $teamId);
            } else {
                $query->whereHas('team', function ($q) use ($teamId) {
                    $q->whereRaw('LOWER(abbreviation) = ?', [strtolower($teamId)]);
                });
            }
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->get()->sortBy(fn($p) => (int) $p->jersey)->values();
    }

    /**
     * Get players ranked by combined minutes metrics.
     *
     * Ranks players by both total_minutes and average_minutes, then sorts
     * by the average of these ranks. Players with no stats go to the end.
     */
    protected function getPlayersRankedByMinutes(?string $teamId, ?string $search)
    {
        $query = Player::with('team');

        if ($teamId) {
            // Support lookup by numeric ID or team abbreviation
            if (is_numeric($teamId)) {
                $query->where('team_id', $teamId);
            } else {
                $query->whereHas('team', function ($q) use ($teamId) {
                    $q->whereRaw('LOWER(abbreviation) = ?', [strtolower($teamId)]);
                });
            }
        }

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $players = $query->get();

        // Separate players with stats from those without
        $withStats = $players->filter(fn($p) => $p->minutes_played !== null && $p->average_minutes_played !== null);
        $withoutStats = $players->filter(fn($p) => $p->minutes_played === null || $p->average_minutes_played === null);

        if ($withStats->isEmpty()) {
            // No stats available, fall back to jersey number sorting
            return $players->sortBy(fn($p) => (int) $p->jersey)->values();
        }

        // Rank by total minutes (higher minutes = lower rank number = better)
        $sortedByTotal = $withStats->sortByDesc('minutes_played')->values();
        $totalRanks = $sortedByTotal->mapWithKeys(fn($p, $idx) => [$p->id => $idx + 1]);

        // Rank by average minutes (higher average = lower rank number = better)
        $sortedByAvg = $withStats->sortByDesc('average_minutes_played')->values();
        $avgRanks = $sortedByAvg->mapWithKeys(fn($p, $idx) => [$p->id => $idx + 1]);

        // Calculate combined rank (average of both ranks)
        $withStats = $withStats->map(function ($player) use ($totalRanks, $avgRanks) {
            $player->total_rank = $totalRanks[$player->id];
            $player->avg_rank = $avgRanks[$player->id];
            $player->combined_rank = ($player->total_rank + $player->avg_rank) / 2;
            return $player;
        });

        // Sort by combined rank (lowest first), then jersey as tiebreaker
        $sorted = $withStats->sortBy([
            ['combined_rank', 'asc'],
            [fn($p) => (int) $p->jersey, 'asc'],
        ])->values();

        // Append players without stats, sorted by jersey
        $withoutStatsSorted = $withoutStats->sortBy(fn($p) => (int) $p->jersey)->values();

        return $sorted->concat($withoutStatsSorted)->values();
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
