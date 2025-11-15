<?php

namespace App\Http\Controllers;

use App\Models\Circonscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CirconscriptionController extends Controller
{
    /**
     * Récupérer toutes les circonscriptions au format GeoJSON
     * Optimisé pour ne charger que les données essentielles
     */
    public function geojson(Request $request)
    {
        $departmentCode = $request->query('department');
        
        // Cache différencié selon le département
        $cacheKey = $departmentCode 
            ? "circonscriptions.geojson.department.{$departmentCode}"
            : 'circonscriptions.geojson.light';
        
        return Cache::remember($cacheKey, 7200, function () use ($departmentCode) {
            $query = Circonscription::query();
            
            // Filtrer par département si spécifié
            if ($departmentCode) {
                $query->where('code_departement', $departmentCode);
            }
            
            // Sélectionner uniquement les colonnes nécessaires
            $circonscriptions = $query->select([
                'id',
                'ref',
                'code_departement',
                'numero',
                'nom',
                'geojson'
            ])->get();

            // Eager load des députés avec relations optimisées
            $deputiesByCirconscription = \App\Models\Deputy::where('is_active', true)
                ->when($departmentCode, fn($q) => $q->where('departement', $departmentCode))
                ->select(['id', 'nom', 'prenom', 'departement', 'circonscription', 'groupe_politique', 'photo'])
                ->with(['politicalGroup:id,sigle,nom,couleur_associee'])
                ->get()
                ->groupBy(function ($deputy) {
                    return $deputy->departement . '-' . $deputy->circonscription;
                });

            $features = $circonscriptions->map(function ($circ) use ($deputiesByCirconscription) {
                $geometry = $circ->geojson ? json_decode($circ->geojson, true) : null;
                
                if (!$geometry) {
                    return null;
                }
                
                // Récupérer les députés depuis le cache local
                $circKey = $circ->code_departement . '-' . $circ->numero;
                $deputies = $deputiesByCirconscription->get($circKey, collect());
                
                return [
                    'type' => 'Feature',
                    'properties' => [
                        'ref' => $circ->ref,
                        'code_departement' => $circ->code_departement,
                        'numero' => $circ->numero,
                        'nom' => $circ->nom,
                        'deputies_count' => $deputies->count(),
                        'deputies' => $deputies->map(fn($d) => [
                            'id' => $d->id,
                            'nom' => $d->nom,
                            'prenom' => $d->prenom,
                            'full_name' => $d->full_name,
                            'groupe_politique' => $d->groupe_politique,
                            'political_group' => $d->politicalGroup ? [
                                'sigle' => $d->politicalGroup->sigle,
                                'nom' => $d->politicalGroup->nom,
                                'couleur' => $d->politicalGroup->couleur,
                            ] : null,
                            'photo' => $d->photo,
                        ])->values(),
                    ],
                    'geometry' => $geometry,
                ];
            })->filter();

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features->values(),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        });
    }

    /**
     * Récupérer les circonscriptions d'un département (optimisé)
     */
    public function byDepartment(string $code)
    {
        return Cache::remember("circonscriptions.department.{$code}", 7200, function () use ($code) {
            $circonscriptions = Circonscription::where('code_departement', $code)
                ->select(['id', 'ref', 'code_departement', 'numero', 'nom', 'geojson'])
                ->get();

            // Charger tous les députés du département en une seule requête
            $deputies = \App\Models\Deputy::where('departement', $code)
                ->where('is_active', true)
                ->select(['id', 'nom', 'prenom', 'circonscription', 'groupe_politique', 'photo'])
                ->with(['politicalGroup:id,sigle,nom,couleur_associee'])
                ->get()
                ->groupBy('circonscription');

            // Attacher les députés à chaque circonscription
            $circonscriptions->each(function ($circ) use ($deputies) {
                $circ->deputies_list = $deputies->get((string) $circ->numero, collect())->values();
            });

            return response()->json($circonscriptions, 200, [], JSON_UNESCAPED_UNICODE);
        });
    }
}
