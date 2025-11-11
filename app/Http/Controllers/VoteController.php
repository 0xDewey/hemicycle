<?php

namespace App\Http\Controllers;

use App\Models\PoliticalGroup;
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
            $query->where(function ($q) use ($search) {
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
        // Charger uniquement les champs nécessaires pour optimiser
        $vote->load(['deputyVotes' => function ($query) {
            $query->select('id', 'vote_id', 'deputy_id', 'position', 'par_delegation', 'cause_position')
                ->with(['deputy' => function ($q) {
                    $q->select('id', 'nom', 'prenom', 'departement', 'circonscription', 'groupe_politique')
                        ->with(['politicalGroup' => function ($pg) {
                            $pg->select('id', 'sigle', 'nom', 'libelle', 'libelle_abrege', 'couleur_associee as couleur');
                        }]);
                }]);
        }]);

        $deputyVotes = $vote->deputyVotes->groupBy('position');

        // Statistiques détaillées pour les non-votants
        $nonVotants = $deputyVotes->get('non_votant', collect());
        $nonVotantsGrouped = $nonVotants->groupBy('cause_position');

        $stats = [
            'pour' => (int) $deputyVotes->get('pour', collect())->count(),
            'contre' => (int) $deputyVotes->get('contre', collect())->count(),
            'abstention' => (int) $deputyVotes->get('abstention', collect())->count(),
            'non_votant' => (int) $nonVotants->count(),
            'non_votant_pan' => (int) $nonVotantsGrouped->get('PAN', collect())->count(), // Président de l'Assemblée
            'non_votant_gov' => (int) $nonVotantsGrouped->get('GOV', collect())->count(), // Gouvernement
            'non_votant_autres' => (int) $nonVotants->whereNotIn('cause_position', ['PAN', 'GOV'])->count(),
        ];

        // Calculer les absents par parti
        $totalDeputies = \App\Models\Deputy::count();
        $votingDeputies = $vote->deputyVotes->count();
        $stats['absents'] = (int) ($totalDeputies - $votingDeputies);

        // Statistiques par parti politique
        $partyStats = [];

        // Pour chaque position (pour, contre, abstention, non_votant)
        foreach (['pour', 'contre', 'abstention', 'non_votant'] as $position) {
            $votesInPosition = $deputyVotes->get($position, collect());
            $partyStats[$position] = $votesInPosition
                ->groupBy(function ($deputyVote) {
                    return $deputyVote->deputy->groupe_politique ?? 'Sans parti';
                })
                ->map(function ($votes, $party) use ($position) {
                    $deputy = $votes->first()->deputy;

                    $result = [
                        'party' => $party,
                        'party_name' => optional($deputy->politicalGroup)->nom ?? $party,
                        'party_color' => optional($deputy->politicalGroup)->couleur ?? '#808080',
                        'count' => (int) $votes->count(),
                    ];

                    // Pour les non-votants, ajouter les détails par cause
                    if ($position === 'non_votant') {
                        $result['pan'] = (int) $votes->where('cause_position', 'PAN')->count();
                        $result['gov'] = (int) $votes->where('cause_position', 'GOV')->count();
                        $result['autres'] = (int) $votes->whereNotIn('cause_position', ['PAN', 'GOV'])->count();
                    }

                    return $result;
                })
                ->sortByDesc('count')
                ->values();
        }

        // Calculer les absents par parti
        $allParties = \App\Models\PoliticalGroup::all();
        $partyStats['absents'] = $allParties->map(function ($party) use ($vote) {
            // Tous les députés du parti
            $totalInParty = \App\Models\Deputy::where('groupe_politique', $party->sigle)->count();

            // Députés du parti qui ont voté
            $votedInParty = $vote->deputyVotes()
                ->whereHas('deputy', function ($q) use ($party) {
                    $q->where('groupe_politique', $party->sigle);
                })
                ->count();

            $absents = $totalInParty - $votedInParty;

            return [
                'party' => $party->sigle,
                'party_name' => $party->nom,
                'party_color' => $party->couleur,
                'count' => (int) $absents,
                'total_deputies' => (int) $totalInParty,
            ];
        })
            ->filter(function ($stat) {
                return $stat['total_deputies'] > 0; // Exclure les partis sans députés
            })
            ->sortByDesc('count')
            ->values();

        // Transformer deputyVotes pour ne garder que les données essentielles
        $deputyVotesFormatted = $deputyVotes->map(function ($votes, $position) {
            return $votes->map(function ($deputyVote) {
                return [
                    'id' => $deputyVote->id,
                    'position' => $deputyVote->position,
                    'par_delegation' => $deputyVote->par_delegation,
                    'cause_position' => $deputyVote->cause_position,
                    'deputy' => [
                        'id' => $deputyVote->deputy->id,
                        'nom' => $deputyVote->deputy->nom,
                        'prenom' => $deputyVote->deputy->prenom,
                        'departement' => $deputyVote->deputy->departement,
                        'circonscription' => $deputyVote->deputy->circonscription,
                        'political_group' => $deputyVote->deputy->politicalGroup ? [
                            'sigle' => $deputyVote->deputy->politicalGroup->sigle,
                            'libelle_abrege' => $deputyVote->deputy->politicalGroup->libelle_abrege,
                            'couleur' => $deputyVote->deputy->politicalGroup->couleur,
                        ] : null,
                    ],
                ];
            })->values();
        });

        return Inertia::render('Votes/Show', [
            'vote' => $vote->only(['id', 'numero', 'titre', 'type', 'date_scrutin', 'resultat', 'pour', 'contre', 'abstention', 'demandeur']),
            'deputyVotes' => $deputyVotesFormatted,
            'stats' => $stats,
            'partyStats' => $partyStats,
        ]);
    }
}
