<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'subscription_tier',
        'advanced_analytics_enabled',
    ];

    protected $casts = [
        'advanced_analytics_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the users (scouts) for this organization.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all reports from scouts in this organization.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'user_id', 'id')
            ->join('users', 'reports.user_id', '=', 'users.id')
            ->where('users.organization_id', $this->id);
    }
}
