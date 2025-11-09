<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    protected $fillable = [
        'uid',
        'numero',
        'type',
        'date_scrutin',
        'titre',
        'description',
        'pour',
        'contre',
        'abstention',
        'resultat',
        'meta',
        'last_synced_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_scrutin' => 'date',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relation avec les votes individuels des députés
     */
    public function deputyVotes(): HasMany
    {
        return $this->hasMany(DeputyVote::class);
    }
}
