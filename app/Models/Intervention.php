<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Intervention extends Model
{
    protected $fillable = [
        'uid',
        'deputy_id',
        'deputy_uid',
        'date_intervention',
        'type',
        'seance',
        'contenu',
        'meta',
        'last_synced_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_intervention' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relation avec le député
     */
    public function deputy(): BelongsTo
    {
        return $this->belongsTo(Deputy::class);
    }
}
