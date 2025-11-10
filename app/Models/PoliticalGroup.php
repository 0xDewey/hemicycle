<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PoliticalGroup extends Model
{
    protected $fillable = [
        'uid',
        'nom',
        'sigle',
        'couleur',
        'code',
        'libelle',
        'libelle_abrege',
        'couleur_associee',
        'position_politique',
        'date_debut',
        'date_fin',
        'meta',
        'last_synced_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relation avec les députés
     */
    public function deputies(): HasMany
    {
        return $this->hasMany(Deputy::class, 'groupe_politique', 'sigle');
    }
}
