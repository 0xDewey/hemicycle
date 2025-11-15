<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Circonscription extends Model
{
    protected $fillable = [
        'ref',
        'code_departement',
        'numero',
        'nom',
        'geojson',
    ];

    /**
     * Table de correspondance entre codes GeoJSON et codes INSEE pour les DOM-TOM et la Corse
     */
    protected static $domtomMapping = [
        // Corse
        '2A' => '2A', // Corse-du-Sud
        '2B' => '2B', // Haute-Corse
        // DOM-TOM
        'ZA' => '971', // Guadeloupe
        'ZB' => '972', // Martinique
        'ZC' => '973', // Guyane
        'ZD' => '974', // La Réunion
        'ZM' => '976', // Mayotte
        'ZS' => '975', // Saint-Pierre-et-Miquelon
        'ZP' => '987', // Polynésie française
        'ZN' => '988', // Nouvelle-Calédonie
        'ZW' => '986', // Wallis-et-Futuna
        'ZX' => '977', // Saint-Barthélemy
        'ZY' => '978', // Saint-Martin
    ];

    /**
     * Convertir le code département GeoJSON en code INSEE
     */
    public function getInseeCode(): string
    {
        return self::$domtomMapping[$this->code_departement] ?? $this->code_departement;
    }

    /**
     * Relation avec les députés (utilisation d'une query callback)
     * Lien basé sur departement + numero de circonscription
     */
    public function deputies()
    {
        $inseeCode = $this->getInseeCode();
        
        return Deputy::where('departement', $inseeCode)
            ->where('circonscription', (string) $this->numero)
            ->where('is_active', true);
    }

    /**
     * Récupérer les données GeoJSON décodées
     */
    public function getGeometryAttribute()
    {
        return $this->geojson ? json_decode($this->geojson, true) : null;
    }
}
