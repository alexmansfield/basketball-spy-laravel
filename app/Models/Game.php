<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Game extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'status',
        'external_id',
        'balldontlie_id',
        'home_team_score',
        'away_team_score',
        'period',
        'time',
        'postseason',
        'season',
        'extra_attributes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'postseason' => 'boolean',
        'extra_attributes' => 'array',
    ];

    /**
     * Get the home team for this game.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * Get the away team for this game.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * Scope a query to only include games for a specific date.
     * Games are stored in UTC but we query by Eastern Time date (NBA standard).
     */
    public function scopeForDate($query, string $date)
    {
        // Convert ET date to UTC range
        // ET day starts at 05:00 UTC (or 04:00 during DST)
        $etStart = Carbon::parse($date, 'America/New_York')->startOfDay()->utc();
        $etEnd = Carbon::parse($date, 'America/New_York')->endOfDay()->utc();

        return $query->whereBetween('scheduled_at', [$etStart, $etEnd]);
    }

    /**
     * Scope a query to only include today's games.
     */
    public function scopeToday($query)
    {
        return $query->forDate(now('America/New_York')->toDateString());
    }

    /**
     * Scope for upcoming games (not finished, from now onwards).
     */
    public function scopeUpcoming($query)
    {
        return $query
            ->whereNotIn('status', ['final', 'closed', 'cancelled', 'postponed'])
            ->where('scheduled_at', '>=', now()->subHours(4)) // Include recently started games
            ->orderBy('scheduled_at');
    }
}
