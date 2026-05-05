<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExternalId extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'provider',
        'provider_league',
        'external_id',
    ];

    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }
}
