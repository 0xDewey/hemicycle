<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeputyVote extends Model
{
    protected $fillable = [
        'deputy_id',
        'vote_id',
        'acteur_ref',
        'mandat_ref',
        'position',
        'cause_position',
        'par_delegation',
        'num_place',
    ];

    protected $casts = [
        'par_delegation' => 'boolean',
    ];

    /**
     * Relation avec le député
     */
    public function deputy(): BelongsTo
    {
        return $this->belongsTo(Deputy::class);
    }

    /**
     * Relation avec le vote/scrutin
     */
    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }
}
