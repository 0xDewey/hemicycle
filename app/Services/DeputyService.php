<?php

namespace App\Services;

use App\Models\Deputy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;


class DeputyService
{
    /**
     * Récupère tous les députés depuis la base de données
     */
    public function getAllDeputies(): Collection
    {
        return Deputy::orderBy('nom')->get();
    }

    /**
     * Récupère les députés par département
     */
    public function getDeputiesByDepartment(string $departmentCode): Collection
    {
        return Deputy::where('departement', $departmentCode)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Récupère la liste des départements avec le nombre de députés
     */
    public function getDepartments(): array
    {
        $departments = Deputy::select('departement', DB::raw('count(*) as count'))
            ->whereNotNull('departement')
            ->groupBy('departement')
            ->orderBy('departement')
            ->get();

        return $departments->map(function ($dept) {
            return [
                'code' => $dept->departement,
                'name' => $this->getDepartmentName($dept->departement),
                'count' => $dept->count
            ];
        })->toArray();
    }

    /**
     * Récupère les options de recherche enrichies (départements + députés)
     */
    public function getSearchOptions(): array
    {
        $options = [];

        // Ajouter les départements
        $departments = Deputy::select('departement', DB::raw('count(*) as count'))
            ->whereNotNull('departement')
            ->groupBy('departement')
            ->orderBy('departement')
            ->get();

        foreach ($departments as $dept) {
            $deptName = $this->getDepartmentName($dept->departement);
            $options[] = [
                'value' => $dept->departement,
                'label' => "{$dept->departement} - {$deptName} ({$dept->count} député" . ($dept->count > 1 ? 's' : '') . ")",
                'type' => 'department',
            ];
        }

        // Ajouter les députés avec leur département
        $deputies = Deputy::select('id', 'prenom', 'nom', 'departement')
            ->whereNotNull('departement')
            ->orderBy('nom')
            ->get();

        foreach ($deputies as $deputy) {
            $deptName = $this->getDepartmentName($deputy->departement);
            $options[] = [
                'value' => $deputy->departement,
                'label' => "{$deputy->prenom} {$deputy->nom} ({$deputy->departement} - {$deptName})",
                'type' => 'deputy',
            ];
        }

        return $options;
    }

    /**
     * Récupère les statistiques agrégées par département
     */
    public function getDepartmentStatistics(string $departmentCode): array
    {
        $deputies = Deputy::where('departement', $departmentCode)
            ->orderBy('nom')
            ->get();

        if ($deputies->isEmpty()) {
            return [];
        }

        $stats = [
            'total_deputies' => $deputies->count(),
            'department_code' => $departmentCode,
            'department_name' => $this->getDepartmentName($departmentCode),
            'deputies' => []
        ];

        foreach ($deputies as $deputy) {
            $stats['deputies'][] = [
                'id' => $deputy->id,
                'uid' => $deputy->uid,
                'name' => $deputy->nom,
                'firstname' => $deputy->prenom,
                'full_name' => $deputy->full_name,
                'group' => $deputy->groupe_politique,
                'group_full' => $deputy->groupe_politique,
                'group_color' => null, // Pourra être ajouté plus tard
                'constituency' => $deputy->circonscription,
                'photo' => $deputy->photo,
                'slug' => $deputy->slug,
            ];
        }

        return $stats;
    }

    /**
     * Récupère les données d'un député par son slug
     */
    public function getDeputyBySlug(string $slug): ?Deputy
    {
        return Deputy::where('slug', $slug)->first();
    }

    /**
     * Récupère les données d'un député par son ID
     */
    public function getDeputyById(int $id): ?Deputy
    {
        return Deputy::find($id);
    }

    /**
     * Récupère le nom d'un département par son code
     */
    private function getDepartmentName(string $code): string
    {
        $departments = [
            '01' => 'Ain', '02' => 'Aisne', '03' => 'Allier', '04' => 'Alpes-de-Haute-Provence',
            '05' => 'Hautes-Alpes', '06' => 'Alpes-Maritimes', '07' => 'Ardèche', '08' => 'Ardennes',
            '09' => 'Ariège', '10' => 'Aube', '11' => 'Aude', '12' => 'Aveyron',
            '13' => 'Bouches-du-Rhône', '14' => 'Calvados', '15' => 'Cantal', '16' => 'Charente',
            '17' => 'Charente-Maritime', '18' => 'Cher', '19' => 'Corrèze', '2A' => 'Corse-du-Sud',
            '2B' => 'Haute-Corse', '21' => 'Côte-d\'Or', '22' => 'Côtes-d\'Armor', '23' => 'Creuse',
            '24' => 'Dordogne', '25' => 'Doubs', '26' => 'Drôme', '27' => 'Eure',
            '28' => 'Eure-et-Loir', '29' => 'Finistère', '30' => 'Gard', '31' => 'Haute-Garonne',
            '32' => 'Gers', '33' => 'Gironde', '34' => 'Hérault', '35' => 'Ille-et-Vilaine',
            '36' => 'Indre', '37' => 'Indre-et-Loire', '38' => 'Isère', '39' => 'Jura',
            '40' => 'Landes', '41' => 'Loir-et-Cher', '42' => 'Loire', '43' => 'Haute-Loire',
            '44' => 'Loire-Atlantique', '45' => 'Loiret', '46' => 'Lot', '47' => 'Lot-et-Garonne',
            '48' => 'Lozère', '49' => 'Maine-et-Loire', '50' => 'Manche', '51' => 'Marne',
            '52' => 'Haute-Marne', '53' => 'Mayenne', '54' => 'Meurthe-et-Moselle', '55' => 'Meuse',
            '56' => 'Morbihan', '57' => 'Moselle', '58' => 'Nièvre', '59' => 'Nord',
            '60' => 'Oise', '61' => 'Orne', '62' => 'Pas-de-Calais', '63' => 'Puy-de-Dôme',
            '64' => 'Pyrénées-Atlantiques', '65' => 'Hautes-Pyrénées', '66' => 'Pyrénées-Orientales',
            '67' => 'Bas-Rhin', '68' => 'Haut-Rhin', '69' => 'Rhône', '70' => 'Haute-Saône',
            '71' => 'Saône-et-Loire', '72' => 'Sarthe', '73' => 'Savoie', '74' => 'Haute-Savoie',
            '75' => 'Paris', '76' => 'Seine-Maritime', '77' => 'Seine-et-Marne', '78' => 'Yvelines',
            '79' => 'Deux-Sèvres', '80' => 'Somme', '81' => 'Tarn', '82' => 'Tarn-et-Garonne',
            '83' => 'Var', '84' => 'Vaucluse', '85' => 'Vendée', '86' => 'Vienne',
            '87' => 'Haute-Vienne', '88' => 'Vosges', '89' => 'Yonne', '90' => 'Territoire de Belfort',
            '91' => 'Essonne', '92' => 'Hauts-de-Seine', '93' => 'Seine-Saint-Denis', '94' => 'Val-de-Marne',
            '95' => 'Val-d\'Oise', '971' => 'Guadeloupe', '972' => 'Martinique', '973' => 'Guyane',
            '974' => 'La Réunion', '976' => 'Mayotte',
        ];

        return $departments[$code] ?? 'Département ' . $code;
    }

    /**
     * Récupère la date de dernière synchronisation
     */
    public function getLastSyncDate(): ?string
    {
        $lastDeputy = Deputy::orderBy('last_synced_at', 'desc')->first();
        return $lastDeputy?->last_synced_at?->format('d/m/Y à H:i');
    }

    /**
     * Récupère la participation quotidienne d'un député
     */
    public function getDailyParticipation(int $deputyId, int $days = 30): array
    {
        $deputy = Deputy::find($deputyId);
        
        if (!$deputy) {
            return [];
        }

        // Générer les dates pour les N derniers jours
        $endDate = now();
        $startDate = now()->subDays($days - 1);

        // Récupérer tous les votes du député dans cette période
        $votes = DB::table('deputy_votes')
            ->join('votes', 'deputy_votes.vote_id', '=', 'votes.id')
            ->where('deputy_votes.deputy_id', $deputyId)
            ->whereBetween('votes.date_scrutin', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                DB::raw('DATE(votes.date_scrutin) as date'),
                DB::raw('COUNT(*) as total_votes'),
                DB::raw('SUM(CASE WHEN deputy_votes.position = "pour" THEN 1 ELSE 0 END) as pour'),
                DB::raw('SUM(CASE WHEN deputy_votes.position = "contre" THEN 1 ELSE 0 END) as contre'),
                DB::raw('SUM(CASE WHEN deputy_votes.position = "abstention" THEN 1 ELSE 0 END) as abstention'),
                DB::raw('SUM(CASE WHEN deputy_votes.position = "non_votant" THEN 1 ELSE 0 END) as non_votant')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Construire le tableau avec tous les jours, même ceux sans vote
        $result = [];
        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $votes->get($dateStr);

            $result[] = [
                'date' => $dateStr,
                'total_votes' => $dayData ? (int)$dayData->total_votes : 0,
                'pour' => $dayData ? (int)$dayData->pour : 0,
                'contre' => $dayData ? (int)$dayData->contre : 0,
                'abstention' => $dayData ? (int)$dayData->abstention : 0,
                'non_votant' => $dayData ? (int)$dayData->non_votant : 0,
            ];
        }

        return $result;
    }
}
