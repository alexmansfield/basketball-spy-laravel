<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('scheduled_at', $date);
    }

    /**
     * Scope a query to only include today's games.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', now()->toDateString());
    }
}
