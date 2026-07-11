<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use SoftDeletes;

    protected $appends = ['age'];

    protected $fillable = [
        'team_id',
        'name',
        'jersey',
        'position',
        'height',
        'weight',
        'birthdate',
        'headshot_url',
        'minutes_played',
        'average_minutes_played',
        'sportsblaze_player_id',
        'stats_synced_at',
        'extra_attributes',
        'balldontlie_id',
        'nba_player_id',
        'espn_id',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'birthdate' => 'date',
        'stats_synced_at' => 'datetime',
        'minutes_played' => 'integer',
        'average_minutes_played' => 'decimal:2',
        'extra_attributes' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get player's age in years.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->birthdate) {
            return null;
        }

        return $this->birthdate->age;
    }

    /**
     * Get the team this player belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get all scout reports for this player.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function externalIds(): MorphMany
    {
        return $this->morphMany(ExternalId::class, 'entity', 'entity_type', 'entity_id');
    }
}
