<?php

namespace App\Http\Controllers;

use App\Services\DeputyService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeputyController extends Controller
{
    public function __construct(
        private DeputyService $deputyService
    ) {}

    /**
     * Affiche la page principale
     */
    public function index()
    {
        $departments = $this->deputyService->getDepartments();
        $searchOptions = $this->deputyService->getSearchOptions();
        $lastSync = $this->deputyService->getLastSyncDate();

        return Inertia::render('Deputies/Index', [
            'departments' => $departments,
            'searchOptions' => $searchOptions,
            'lastSyncDate' => $lastSync,
        ]);
    }

    /**
     * API: Récupère les députés par département
     */
    public function byDepartment(string $code)
    {
        return response()->json(
            $this->deputyService->getDeputiesByDepartment($code)
        );
    }

    /**
     * API: Récupère un député par son slug
     */
    public function show(string $slug)
    {
        $deputy = $this->deputyService->getDeputyBySlug($slug);

        if (! $deputy) {
            return response()->json([
                'error' => 'Député non trouvé',
            ], 404);
        }

        return response()->json($deputy);
    }

    /**
     * API: Récupère tous les départements
     */
    public function getDepartments()
    {
        return response()->json($this->deputyService->getDepartments());
    }

    /**
     * API: Récupère les statistiques d'un département
     */
    public function getDepartmentStats(string $code)
    {
        $stats = $this->deputyService->getDepartmentStatistics($code);

        if (empty($stats)) {
            return response()->json([
                'error' => 'Département non trouvé ou sans députés',
            ], 404);
        }

        return response()->json($stats);
    }

    /**
     * API: Récupère la participation quotidienne d'un député
     */
    public function getDailyParticipation(int $id, Request $request)
    {
        $days = $request->get('days', 30);

        $participation = $this->deputyService->getDailyParticipation($id, $days);

        return response()->json($participation);
    }
}
