<?php

namespace App\Http\Controllers;

use App\Models\PoliticalGroup;
use App\Models\Vote;
use App\Models\DeputyVote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PoliticalGroupController extends Controller
{
    /**
     * Afficher la liste des partis politiques
     */
    public function index()
    {
        $parties = PoliticalGroup::withCount('deputies')
            ->orderByDesc('deputies_count')
            ->get();

        return Inertia::render('PoliticalGroups/Index', [
            'parties' => $parties,
        ]);
    }

    /**
     * Afficher les statistiques d'un parti politique
     */
    public function show(PoliticalGroup $politicalGroup)
    {
        $politicalGroup->load('deputies');

        $totalDeputies = $politicalGroup->deputies->count();
        $totalVotes = DeputyVote::whereIn('deputy_id', $politicalGroup->deputies->pluck('id'))->count();

        // Votes par position
        $votesByPosition = DeputyVote::whereIn('deputy_id', $politicalGroup->deputies->pluck('id'))
            ->selectRaw('position, COUNT(*) as count')
            ->groupBy('position')
            ->get()
            ->pluck('count', 'position');

        $pour = (int) ($votesByPosition['pour'] ?? 0);
        $contre = (int) ($votesByPosition['contre'] ?? 0);
        $abstention = (int) ($votesByPosition['abstention'] ?? 0);
        $nonVotant = (int) ($votesByPosition['non_votant'] ?? 0);

        // Calculer le nombre total de scrutins
        $totalScrutins = \App\Models\Vote::count();
        
        // Calculer les absences (total possible de votes - votes réels)
        $totalPossibleVotes = $totalDeputies * $totalScrutins;
        $absents = $totalPossibleVotes - $totalVotes;

        // Statistiques générales
        $stats = [
            'total_deputies' => (int) $totalDeputies,
            'total_votes' => (int) $totalVotes,
            'pour' => $pour,
            'contre' => $contre,
            'abstention' => $abstention,
            'non_votant' => $nonVotant,
            'absents' => (int) $absents,
        ];

        return Inertia::render('PoliticalGroups/Show', [
            'party' => $politicalGroup,
            'stats' => $stats,
        ]);
    }

    /**
     * Afficher les scrutins pour un parti politique
     */
    public function votes(PoliticalGroup $politicalGroup, Request $request)
    {
        $query = Vote::query();

        // Filtrer par position si demandé
        $position = $request->get('position');
        
        if ($position) {
            $query->whereHas('deputyVotes', function($q) use ($politicalGroup, $position) {
                $q->whereIn('deputy_id', $politicalGroup->deputies->pluck('id'))
                  ->where('position', $position);
            });
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

        // Calculer les statistiques pour chaque vote
        $votes->getCollection()->transform(function($vote) use ($politicalGroup) {
            $partyVotes = DeputyVote::where('vote_id', $vote->id)
                ->whereIn('deputy_id', $politicalGroup->deputies->pluck('id'))
                ->selectRaw('position, COUNT(*) as count')
                ->groupBy('position')
                ->get()
                ->pluck('count', 'position');

            $vote->party_votes = [
                'pour' => (int) ($partyVotes['pour'] ?? 0),
                'contre' => (int) ($partyVotes['contre'] ?? 0),
                'abstention' => (int) ($partyVotes['abstention'] ?? 0),
                'non_votant' => (int) ($partyVotes['non_votant'] ?? 0),
                'total' => (int) $politicalGroup->deputies->count(),
            ];

            return $vote;
        });

        return Inertia::render('PoliticalGroups/Votes', [
            'party' => $politicalGroup,
            'votes' => $votes,
            'filters' => $request->only(['position', 'search']),
        ]);
    }
}
