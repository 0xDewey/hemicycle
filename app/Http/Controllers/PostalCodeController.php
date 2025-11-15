<?php

namespace App\Http\Controllers;

use App\Models\Circonscription;
use Illuminate\Http\Request;

class PostalCodeController extends Controller
{
    public function search(Request $request)
    {
        $postalCode = $request->input('postal_code');
        
        if (!$postalCode || strlen($postalCode) < 2) {
            return response()->json(['error' => 'Code postal invalide'], 400);
        }
        
        // Stratégie 1 : Essayer de trouver les coordonnées GPS du code postal
        $coordinates = $this->geocodePostalCode($postalCode);
        
        if ($coordinates) {
            // Trouver la circonscription qui contient ce point
            $circonscription = $this->findCirconscriptionByPoint($coordinates['lat'], $coordinates['lon']);
            
            if ($circonscription) {
                $deputies = $circonscription->deputies()->get();
                
                return response()->json([
                    'method' => 'geocoding',
                    'postal_code' => $postalCode,
                    'coordinates' => $coordinates,
                    'circonscription' => [
                        'ref' => $circonscription->ref,
                        'code_departement' => $circonscription->code_departement,
                        'numero' => $circonscription->numero,
                        'nom' => $circonscription->nom,
                        'deputies' => $deputies,
                        'deputies_count' => $deputies->count(),
                    ],
                ]);
            }
        }
        
        // Stratégie 2 (fallback) : Utiliser le code département
        $inseeCode = $this->getDepartmentFromPostalCode($postalCode);
        
        if (!$inseeCode) {
            return response()->json(['error' => 'Code postal non trouvé'], 404);
        }
        
        $geojsonCode = $this->convertInseeToGeojson($inseeCode);
        $circonscriptions = Circonscription::where('code_departement', $geojsonCode)->get();
        
        $result = $circonscriptions->map(function ($circonscription) {
            $deputies = $circonscription->deputies()->get();
            return [
                'ref' => $circonscription->ref,
                'code_departement' => $circonscription->code_departement,
                'numero' => $circonscription->numero,
                'nom' => $circonscription->nom,
                'deputies' => $deputies,
                'deputies_count' => $deputies->count(),
            ];
        });
        
        return response()->json([
            'method' => 'department_fallback',
            'department_code' => $geojsonCode,
            'insee_code' => $inseeCode,
            'circonscriptions' => $result,
        ]);
    }
    
    /**
     * Géocoder un code postal français en coordonnées GPS
     * Utilise l'API gratuite du gouvernement français
     */
    private function geocodePostalCode($postalCode)
    {
        try {
            // API officielle du gouvernement français (gratuite et sans clé)
            $url = "https://api-adresse.data.gouv.fr/search/?q=" . urlencode($postalCode) . "&type=municipality&limit=1";
            
            $response = @file_get_contents($url);
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (isset($data['features'][0]['geometry']['coordinates'])) {
                $coords = $data['features'][0]['geometry']['coordinates'];
                return [
                    'lon' => $coords[0],
                    'lat' => $coords[1],
                ];
            }
        } catch (\Exception $e) {
            // En cas d'erreur, on continue avec la méthode fallback
            \Log::warning("Erreur de géocodage pour le code postal {$postalCode}: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Trouver la circonscription qui contient un point GPS
     * Utilise un algorithme de point-in-polygon
     */
    private function findCirconscriptionByPoint($lat, $lon)
    {
        // Récupérer toutes les circonscriptions (on pourrait optimiser avec une bounding box)
        $circonscriptions = Circonscription::all();
        
        foreach ($circonscriptions as $circonscription) {
            if ($this->isPointInCirconscription($lat, $lon, $circonscription)) {
                return $circonscription;
            }
        }
        
        return null;
    }
    
    /**
     * Vérifier si un point est dans une circonscription
     * Gère les MultiPolygons et les Polygons
     */
    private function isPointInCirconscription($lat, $lon, $circonscription)
    {
        $geometry = json_decode($circonscription->geojson, true);
        
        if (!$geometry || !isset($geometry['type'])) {
            return false;
        }
        
        if ($geometry['type'] === 'MultiPolygon') {
            // Un MultiPolygon contient plusieurs polygones
            foreach ($geometry['coordinates'] as $polygon) {
                if ($this->isPointInPolygon($lat, $lon, $polygon[0])) {
                    return true;
                }
            }
        } elseif ($geometry['type'] === 'Polygon') {
            // Un Polygon a un seul contour
            if ($this->isPointInPolygon($lat, $lon, $geometry['coordinates'][0])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Algorithme Ray Casting pour déterminer si un point est dans un polygone
     * @param float $lat Latitude du point
     * @param float $lon Longitude du point
     * @param array $polygon Tableau de coordonnées [lon, lat]
     * @return bool
     */
    private function isPointInPolygon($lat, $lon, $polygon)
    {
        $inside = false;
        $count = count($polygon);
        
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $polygon[$i][0]; // longitude
            $yi = $polygon[$i][1]; // latitude
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];
            
            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lon < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);
            
            if ($intersect) {
                $inside = !$inside;
            }
        }
        
        return $inside;
    }
    
    /**
     * Convertir un code INSEE en code GeoJSON (inverse du mapping)
     */
    private function convertInseeToGeojson($inseeCode)
    {
        // Mapping inverse : INSEE -> GeoJSON
        $reverseMapping = [
            '971' => 'ZA', // Guadeloupe
            '972' => 'ZB', // Martinique
            '973' => 'ZC', // Guyane
            '974' => 'ZD', // La Réunion
            '976' => 'ZM', // Mayotte
            '975' => 'ZS', // Saint-Pierre-et-Miquelon
            '987' => 'ZP', // Polynésie française
            '988' => 'ZN', // Nouvelle-Calédonie
            '986' => 'ZW', // Wallis-et-Futuna
            '977' => 'ZX', // Saint-Barthélemy
            '978' => 'ZY', // Saint-Martin
        ];
        
        return $reverseMapping[$inseeCode] ?? $inseeCode;
    }
    
    private function getDepartmentFromPostalCode($postalCode)
    {
        // Normaliser le code postal (supprimer les espaces, convertir en majuscules)
        $postalCode = strtoupper(trim($postalCode));
        
        // Vérifier la longueur minimale
        if (strlen($postalCode) < 2) {
            return null;
        }
        
        // Compléter avec des zéros si nécessaire (pour les codes courts)
        if (strlen($postalCode) < 5 && is_numeric($postalCode)) {
            $postalCode = str_pad($postalCode, 5, '0', STR_PAD_LEFT);
        }
        
        // DOM-TOM (vérifier en premier car ils commencent par 97)
        if (str_starts_with($postalCode, '971')) return '971'; // Guadeloupe
        if (str_starts_with($postalCode, '972')) return '972'; // Martinique
        if (str_starts_with($postalCode, '973')) return '973'; // Guyane
        if (str_starts_with($postalCode, '974')) return '974'; // La Réunion
        if (str_starts_with($postalCode, '976')) return '976'; // Mayotte
        if (str_starts_with($postalCode, '975')) return '975'; // Saint-Pierre-et-Miquelon
        if (str_starts_with($postalCode, '977')) return '977'; // Saint-Barthélemy
        if (str_starts_with($postalCode, '978')) return '978'; // Saint-Martin
        if (str_starts_with($postalCode, '986')) return '986'; // Wallis-et-Futuna
        if (str_starts_with($postalCode, '987')) return '987'; // Polynésie française
        if (str_starts_with($postalCode, '988')) return '988'; // Nouvelle-Calédonie
        
        // Corse
        if (str_starts_with($postalCode, '200') || str_starts_with($postalCode, '201')) {
            return '2A'; // Corse-du-Sud
        }
        if (str_starts_with($postalCode, '202') || str_starts_with($postalCode, '206')) {
            return '2B'; // Haute-Corse
        }
        
        // Départements métropolitains (01-95)
        // Extraire les 2 premiers chiffres
        $deptCode = substr($postalCode, 0, 2);
        
        // Vérifier si c'est un nombre valide entre 01 et 95 (sauf 20 qui est la Corse)
        if (is_numeric($deptCode)) {
            $deptNum = (int)$deptCode;
            if ($deptNum >= 1 && $deptNum <= 19) {
                return str_pad($deptCode, 2, '0', STR_PAD_LEFT);
            }
            // Sauter 20 (Corse, déjà géré avec 2A et 2B)
            if ($deptNum >= 21 && $deptNum <= 95) {
                return str_pad($deptCode, 2, '0', STR_PAD_LEFT);
            }
        }
        
        return null;
    }
}
