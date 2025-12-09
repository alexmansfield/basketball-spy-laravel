<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'team_id',
        'name',
        'jersey',
        'position',
        'height',
        'weight',
        'headshot_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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
}
