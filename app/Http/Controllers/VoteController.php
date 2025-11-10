<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\PoliticalGroup;
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

        // Partis politiques disponibles
        $parties = PoliticalGroup::select('sigle', 'nom', 'couleur')
            ->orderBy('nom')
            ->get();

        return Inertia::render('Votes/Index', [
            'votes' => $votes,
            'stats' => $stats,
            'types' => $types,
            'parties' => $parties,
            'filters' => $request->only(['type', 'resultat', 'search']),
        ]);
    }

    /**
     * Afficher le détail d'un scrutin
     */
    public function show(Vote $vote)
    {
        $vote->load(['deputyVotes.deputy.politicalGroup']);

        $deputyVotes = $vote->deputyVotes->groupBy('position');

        $stats = [
            'pour' => $deputyVotes->get('pour', collect())->count(),
            'contre' => $deputyVotes->get('contre', collect())->count(),
            'abstention' => $deputyVotes->get('abstention', collect())->count(),
            'non_votant' => $deputyVotes->get('non_votant', collect())->count(),
        ];

        // Statistiques par parti politique
        $partyStats = [];
        foreach (['pour', 'contre', 'abstention', 'non_votant'] as $position) {
            $votesInPosition = $deputyVotes->get($position, collect());
            $partyStats[$position] = $votesInPosition
                ->groupBy(function($deputyVote) {
                    return $deputyVote->deputy->groupe_politique ?? 'Sans parti';
                })
                ->map(function($votes, $party) {
                    $deputy = $votes->first()->deputy;
                    return [
                        'party' => $party,
                        'party_name' => $deputy->politicalGroup->nom ?? $party,
                        'party_color' => $deputy->politicalGroup->couleur ?? '#808080',
                        'count' => $votes->count(),
                    ];
                })
                ->sortByDesc('count')
                ->values();
        }

        return Inertia::render('Votes/Show', [
            'vote' => $vote,
            'deputyVotes' => $deputyVotes,
            'stats' => $stats,
            'partyStats' => $partyStats,
        ]);
    }
}
