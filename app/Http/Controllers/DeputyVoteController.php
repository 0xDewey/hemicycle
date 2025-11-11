<?php

namespace App\Http\Controllers;

use App\Models\Deputy;
use App\Models\Vote;
use App\Models\DeputyVote;
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
        
        $votes = DeputyVote::with('vote')
            ->forDeputy($deputy->id)
            ->orderByVoteDate()
            ->paginate(20);

        // Nombre total de scrutins
        $totalScrutins = Vote::count();
        
        // Nombre de votes du député
        $totalVotesDeputy = DeputyVote::forDeputy($deputy->id)->count();
        
        // Calcul des absences
        $absents = $totalScrutins - $totalVotesDeputy;
        $tauxAbsenteisme = $totalScrutins > 0 ? round(($absents / $totalScrutins) * 100, 1) : 0;

        // Statistiques
        $stats = [
            'total' => (int) $totalVotesDeputy,
            'pour' => (int) DeputyVote::forDeputy($deputy->id)->where('position', 'pour')->count(),
            'contre' => (int) DeputyVote::forDeputy($deputy->id)->where('position', 'contre')->count(),
            'abstention' => (int) DeputyVote::forDeputy($deputy->id)->where('position', 'abstention')->count(),
            'non_votant' => (int) DeputyVote::forDeputy($deputy->id)->where('position', 'non_votant')->count(),
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
