<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'abbreviation',
        'location',
        'nickname',
        'league',
        'url',
        'logo_url',
        'color',
        'arena_name',
        'arena_city',
        'arena_state',
        'arena_latitude',
        'arena_longitude',
        'extra_attributes',
        'balldontlie_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'extra_attributes' => 'array',
    ];

    /**
     * Get all players for this team.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function externalIds(): MorphMany
    {
        return $this->morphMany(ExternalId::class, 'entity', 'entity_type', 'entity_id');
    }
}
