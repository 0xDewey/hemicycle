<?php

namespace App\Http\Controllers;

use App\Models\Circonscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CirconscriptionController extends Controller
{
    /**
     * Récupérer toutes les circonscriptions au format GeoJSON
     */
    public function geojson()
    {
        return Cache::remember('circonscriptions.geojson', 7200, function () {
            $circonscriptions = Circonscription::all();

            $features = $circonscriptions->map(function ($circ) {
                $geometry = $circ->geojson ? json_decode($circ->geojson, true) : null;
                
                // Charger les députés actifs pour cette circonscription
                $deputies = $circ->deputies()->get();
                
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
                            'photo' => $d->photo,
                        ]),
                    ],
                    'geometry' => $geometry,
                ];
            })->filter(fn($feature) => $feature['geometry'] !== null);

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features->values(),
            ]);
        });
    }

    /**
     * Récupérer les circonscriptions d'un département
     */
    public function byDepartment(string $code)
    {
        return Cache::remember("circonscriptions.department.{$code}", 7200, function () use ($code) {
            $circonscriptions = Circonscription::where('code_departement', $code)->get();

            // Charger les députés pour chaque circonscription
            $circonscriptions->each(function ($circ) {
                $circ->deputies_list = $circ->deputies()->get();
            });

            return response()->json($circonscriptions);
        });
    }
}
