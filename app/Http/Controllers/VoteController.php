<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoteController extends Controller
{
    /**
     * Afficher la liste de tous les scrutins
     */
    public function index(Request $request)
    {
        $query = Vote::query();

        // Filtrer par type si demandé
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Filtrer par résultat si demandé
        if ($resultat = $request->get('resultat')) {
            $query->where('resultat', $resultat);
        }

        // Recherche
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $votes = $query->orderByDesc('date_scrutin')
            ->paginate(20)
            ->withQueryString();

        // Statistiques générales
        $stats = [
            'total' => Vote::count(),
            'adopte' => Vote::where('resultat', 'adopté')->count(),
            'rejete' => Vote::where('resultat', 'rejeté')->count(),
        ];

        // Types de votes disponibles
        $types = Vote::selectRaw('DISTINCT type')
            ->whereNotNull('type')
            ->pluck('type')
            ->filter();

        return Inertia::render('Votes/Index', [
            'votes' => $votes,
            'stats' => $stats,
            'types' => $types,
            'filters' => $request->only(['type', 'resultat', 'search']),
        ]);
    }

    /**
     * Afficher le détail d'un scrutin
     */
    public function show(Vote $vote)
    {
        $vote->load(['deputyVotes.deputy']);

        // Ajouter les informations de groupe à chaque député
        foreach ($vote->deputyVotes as $deputyVote) {
            $deputy = $deputyVote->deputy;
            $deputyVote->deputy->political_group = [
                'libelle' => $deputy->groupe_politique,
                'libelle_abrege' => $deputy->groupe_politique,
                'couleur_associee' => null,
            ];
        }

        $deputyVotes = $vote->deputyVotes->groupBy('position');

        $stats = [
            'pour' => $deputyVotes->get('pour', collect())->count(),
            'contre' => $deputyVotes->get('contre', collect())->count(),
            'abstention' => $deputyVotes->get('abstention', collect())->count(),
            'non_votant' => $deputyVotes->get('non_votant', collect())->count(),
        ];

        return Inertia::render('Votes/Show', [
            'vote' => $vote,
            'deputyVotes' => $deputyVotes,
            'stats' => $stats,
        ]);
    }
}
