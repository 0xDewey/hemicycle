<?php

namespace App\Services;

use App\Models\Department;
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
        return Deputy::where('is_active', true)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Récupère les députés par département
     */
    public function getDeputiesByDepartment(string $departmentCode): Collection
    {
        return Deputy::where('departement', $departmentCode)
            ->where('is_active', true)
            ->orderBy('nom')
            ->get();
    }

    /**
     * Récupère la liste des départements avec le nombre de députés
     */
    public function getDepartments(): array
    {
        return Department::getAllWithDeputyCount();
    }

    /**
     * Récupère les options de recherche enrichies (départements + députés)
     */
    public function getSearchOptions(): array
    {
        $options = [];

        // Ajouter les départements
        $departments = Department::getAllWithDeputyCount();

        foreach ($departments as $dept) {
            $options[] = [
                'value' => $dept['code'],
                'label' => "{$dept['code']} - {$dept['name']} ({$dept['count']} député".($dept['count'] > 1 ? 's' : '').')',
                'type' => 'department',
            ];
        }

        // Ajouter les députés avec leur département
        $deputies = Deputy::select('id', 'prenom', 'nom', 'departement')
            ->where('is_active', true)
            ->whereNotNull('departement')
            ->orderBy('nom')
            ->get();

        foreach ($deputies as $deputy) {
            $deptName = Department::getNameByCode($deputy->departement);
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
            ->where('is_active', true)
            ->with('politicalGroup')
            ->orderBy('nom')
            ->get();

        if ($deputies->isEmpty()) {
            return [];
        }

        $stats = [
            'total_deputies' => $deputies->count(),
            'department_code' => $departmentCode,
            'department_name' => Department::getNameByCode($departmentCode),
            'deputies' => [],
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
                'group_color' => $deputy->politicalGroup?->couleur_associee,
                'political_group' => $deputy->politicalGroup ? [
                    'sigle' => $deputy->politicalGroup->libelle_abrege,
                    'nom' => $deputy->politicalGroup->libelle,
                    'couleur' => $deputy->politicalGroup->couleur_associee,
                ] : null,
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

        if (! $deputy) {
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

        // Récupérer le nombre total de scrutins par jour (indépendamment du député)
        $totalScrutins = DB::table('votes')
            ->whereBetween('date_scrutin', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select(
                DB::raw('DATE(date_scrutin) as date'),
                DB::raw('COUNT(*) as total_scrutins')
            )
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Construire le tableau avec tous les jours, même ceux sans vote
        $result = [];
        for ($date = clone $startDate; $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $dayData = $votes->get($dateStr);
            $scrutinsData = $totalScrutins->get($dateStr);

            $result[] = [
                'date' => $dateStr,
                'total_votes' => $dayData ? (int) $dayData->total_votes : 0,
                'total_scrutins' => $scrutinsData ? (int) $scrutinsData->total_scrutins : 0,
                'pour' => $dayData ? (int) $dayData->pour : 0,
                'contre' => $dayData ? (int) $dayData->contre : 0,
                'abstention' => $dayData ? (int) $dayData->abstention : 0,
                'non_votant' => $dayData ? (int) $dayData->non_votant : 0,
            ];
        }

        return $result;
    }
}
