<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'code',
        'name',
        'order',
    ];

    /**
     * Relation avec les députés
     */
    public function deputies(): HasMany
    {
        return $this->hasMany(Deputy::class, 'departement', 'code');
    }

    /**
     * Récupérer un département par son code
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Récupérer le nom d'un département par son code
     */
    public static function getNameByCode(string $code): string
    {
        $dept = static::findByCode($code);
        return $dept?->name ?? 'Département ' . $code;
    }

    /**
     * Récupérer tous les départements triés par nom
     */
    public static function getAllSorted(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('name')->get();
    }

    /**
     * Récupérer tous les départements avec le nombre de députés
     */
    public static function getAllWithDeputyCount(): array
    {
        return static::withCount('deputies')
            ->orderBy('name')
            ->get()
            ->map(fn($dept) => [
                'code' => $dept->code,
                'name' => $dept->name,
                'count' => $dept->deputies_count,
            ])
            ->toArray();
    }
}
