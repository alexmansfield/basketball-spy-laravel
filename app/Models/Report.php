<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'player_id',
        'user_id',
        'team_id_at_time',
        // OFFENSE
        'offense_shooting',
        'offense_finishing',
        'offense_driving',
        'offense_dribbling',
        'offense_creating',
        'offense_passing',
        // DEFENSE
        'defense_one_on_one',
        'defense_blocking',
        'defense_team_defense',
        'defense_rebounding',
        // INTANGIBLES
        'intangibles_effort',
        'intangibles_role_acceptance',
        'intangibles_iq',
        'intangibles_awareness',
        // ATHLETICISM
        'athleticism_hands',
        'athleticism_length',
        'athleticism_quickness',
        'athleticism_jumping',
        'athleticism_strength',
        'athleticism_coordination',
        // ADDITIONAL
        'notes',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the player this report is for.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get the scout (user) who created this report.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team the player belonged to when this report was created.
     */
    public function teamAtTime(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id_at_time');
    }

    /**
     * Check if this report has been synced to the server.
     */
    public function isSynced(): bool
    {
        return $this->synced_at !== null;
    }

    /**
     * Calculate the average rating across all categories.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $ratings = array_filter([
            $this->offense_shooting,
            $this->offense_finishing,
            $this->offense_driving,
            $this->offense_dribbling,
            $this->offense_creating,
            $this->offense_passing,
            $this->defense_one_on_one,
            $this->defense_blocking,
            $this->defense_team_defense,
            $this->defense_rebounding,
            $this->intangibles_effort,
            $this->intangibles_role_acceptance,
            $this->intangibles_iq,
            $this->intangibles_awareness,
            $this->athleticism_hands,
            $this->athleticism_length,
            $this->athleticism_quickness,
            $this->athleticism_jumping,
            $this->athleticism_strength,
            $this->athleticism_coordination,
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : null;
    }
}
