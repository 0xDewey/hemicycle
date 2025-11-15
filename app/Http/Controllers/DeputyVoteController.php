<?php

namespace App\Http\Controllers;

use App\Models\Deputy;
use App\Models\DeputyVote;
use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeputyVoteController extends Controller
{
    /**
     * Afficher les votes d'un député
     */
    public function show(Deputy $deputy)
    {
        // Charger la relation politicalGroup
        $deputy->load('politicalGroup');

        // Filtrer les votes selon les dates de mandat
        $votesQuery = DeputyVote::with('vote')
            ->forDeputy($deputy->id)
            ->whereHas('vote', function ($query) use ($deputy) {
                // Seuls les votes pendant le mandat du député
                if ($deputy->mandate_start_date) {
                    $query->where('date_scrutin', '>=', $deputy->mandate_start_date);
                }
                if ($deputy->mandate_end_date) {
                    $query->where('date_scrutin', '<=', $deputy->mandate_end_date);
                }
            });

        $votes = $votesQuery->orderByVoteDate()->paginate(20);

        // Nombre total de scrutins pendant le mandat du député
        $totalScrutinsQuery = Vote::query();
        if ($deputy->mandate_start_date) {
            $totalScrutinsQuery->where('date_scrutin', '>=', $deputy->mandate_start_date);
        }
        if ($deputy->mandate_end_date) {
            $totalScrutinsQuery->where('date_scrutin', '<=', $deputy->mandate_end_date);
        }
        $totalScrutins = $totalScrutinsQuery->count();

        // Nombre de votes du député pendant son mandat
        $totalVotesDeputyQuery = DeputyVote::forDeputy($deputy->id)
            ->whereHas('vote', function ($query) use ($deputy) {
                if ($deputy->mandate_start_date) {
                    $query->where('date_scrutin', '>=', $deputy->mandate_start_date);
                }
                if ($deputy->mandate_end_date) {
                    $query->where('date_scrutin', '<=', $deputy->mandate_end_date);
                }
            });
        $totalVotesDeputy = $totalVotesDeputyQuery->count();

        // Calcul des absences
        $absents = $totalScrutins - $totalVotesDeputy;
        $tauxAbsenteisme = $totalScrutins > 0 ? round(($absents / $totalScrutins) * 100, 1) : 0;

        // Statistiques par position pendant le mandat
        $baseStatsQuery = DeputyVote::forDeputy($deputy->id)
            ->whereHas('vote', function ($query) use ($deputy) {
                if ($deputy->mandate_start_date) {
                    $query->where('date_scrutin', '>=', $deputy->mandate_start_date);
                }
                if ($deputy->mandate_end_date) {
                    $query->where('date_scrutin', '<=', $deputy->mandate_end_date);
                }
            });

        // Statistiques
        $stats = [
            'total' => (int) $totalVotesDeputy,
            'pour' => (int) (clone $baseStatsQuery)->where('position', 'pour')->count(),
            'contre' => (int) (clone $baseStatsQuery)->where('position', 'contre')->count(),
            'abstention' => (int) (clone $baseStatsQuery)->where('position', 'abstention')->count(),
            'non_votant' => (int) (clone $baseStatsQuery)->where('position', 'non_votant')->count(),
            'absents' => (int) $absents,
            'total_scrutins' => (int) $totalScrutins,
            'taux_absenteisme' => $tauxAbsenteisme,
        ];

        return Inertia::render('Deputies/Votes', [
            'deputy' => $deputy,
            'votes' => $votes,
            'stats' => $stats,
        ]);
    }

    /**
     * API pour récupérer les votes d'un député
     */
    public function api(Deputy $deputy, Request $request)
    {
        $query = DeputyVote::with('vote')
            ->forDeputy($deputy->id);

        // Filtrer par position si demandé
        if ($position = $request->get('position')) {
            $query->where('position', $position);
        }

        // Trier
        $sortBy = $request->get('sort_by', 'date_scrutin');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'date_scrutin') {
            $query->orderByVoteDate($sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $votes = $query->paginate($request->get('per_page', 20));

        // Statistiques
        $stats = [
            'total' => DeputyVote::forDeputy($deputy->id)->count(),
            'pour' => DeputyVote::forDeputy($deputy->id)->where('position', 'pour')->count(),
            'contre' => DeputyVote::forDeputy($deputy->id)->where('position', 'contre')->count(),
            'abstention' => DeputyVote::forDeputy($deputy->id)->where('position', 'abstention')->count(),
            'non_votant' => DeputyVote::forDeputy($deputy->id)->where('position', 'non_votant')->count(),
        ];

        return response()->json([
            'votes' => $votes,
            'stats' => $stats,
            'deputy' => $deputy,
        ]);
    }

    /**
     * Afficher les détails d'un vote spécifique
     */
    public function voteDetail(Vote $vote)
    {
        $deputyVotes = DeputyVote::with('deputy')
            ->where('vote_id', $vote->id)
            ->get()
            ->groupBy('position');

        $stats = [
            'pour' => $deputyVotes->get('pour', collect())->count(),
            'contre' => $deputyVotes->get('contre', collect())->count(),
            'abstention' => $deputyVotes->get('abstention', collect())->count(),
            'non_votant' => $deputyVotes->get('non_votant', collect())->count(),
        ];

        return Inertia::render('Votes/Detail', [
            'vote' => $vote,
            'deputyVotes' => $deputyVotes,
            'stats' => $stats,
        ]);
    }
}
