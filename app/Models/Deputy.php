<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deputy extends Model
{
    protected $fillable = [
        'uid',
        'nom',
        'prenom',
        'circonscription',
        'departement',
        'groupe_politique',
        'mandate_start_date',
        'mandate_end_date',
        'is_active',
        'cause_mandat',
        'ref_circonscription',
        'photo',
        'slug',
        'meta',
        'last_synced_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'last_synced_at' => 'datetime',
        'mandate_start_date' => 'date',
        'mandate_end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec les interventions
     */
    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    /**
     * Relation avec les votes du député
     */
    public function deputyVotes(): HasMany
    {
        return $this->hasMany(DeputyVote::class);
    }

    /**
     * Relation avec le groupe politique
     */
    public function politicalGroup(): BelongsTo
    {
        return $this->belongsTo(PoliticalGroup::class, 'groupe_politique', 'sigle');
    }

    /**
     * Accessor pour le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }
}
