<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GamesController extends Controller
{
    /**
     * Get today's games.
     *
     * GET /api/games/today
     */
    public function today(Request $request): JsonResponse
    {
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->today()
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'games' => $games->map(function ($game) {
                return $this->formatGame($game);
            }),
            'date' => now()->toDateString(),
        ]);
    }

    /**
     * Get games for a specific date.
     *
     * GET /api/games/{date}
     */
    public function byDate(string $date, Request $request): JsonResponse
    {
        $games = Game::with(['homeTeam', 'awayTeam'])
            ->forDate($date)
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'games' => $games->map(function ($game) {
                return $this->formatGame($game);
            }),
            'date' => $date,
        ]);
    }

    /**
     * Format a game for the mobile app.
     */
    private function formatGame(Game $game): array
    {
        return [
            'id' => (string) $game->id,
            'homeTeam' => [
                'id' => (string) $game->homeTeam->id,
                'name' => $game->homeTeam->nickname,
                'abbreviation' => $game->homeTeam->abbreviation,
                'logoUrl' => $game->homeTeam->logo_url,
                'color' => $game->homeTeam->color,
            ],
            'awayTeam' => [
                'id' => (string) $game->awayTeam->id,
                'name' => $game->awayTeam->nickname,
                'abbreviation' => $game->awayTeam->abbreviation,
                'logoUrl' => $game->awayTeam->logo_url,
                'color' => $game->awayTeam->color,
            ],
            'arena' => [
                'name' => $game->homeTeam->arena_name ?? $game->homeTeam->name . ' Arena',
                'city' => $game->homeTeam->arena_city ?? $game->homeTeam->location,
                'state' => $game->homeTeam->arena_state ?? '',
                'latitude' => (float) ($game->homeTeam->arena_latitude ?? 0),
                'longitude' => (float) ($game->homeTeam->arena_longitude ?? 0),
            ],
            'scheduledAt' => $game->scheduled_at->toISOString(),
            'status' => $game->status,
        ];
    }
}
