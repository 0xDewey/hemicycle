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
        $votes = DeputyVote::with('vote')
            ->where('deputy_id', $deputy->id)
            ->latest('created_at')
            ->paginate(20);

        // Statistiques
        $stats = [
            'total' => DeputyVote::where('deputy_id', $deputy->id)->count(),
            'pour' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'pour')->count(),
            'contre' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'contre')->count(),
            'abstention' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'abstention')->count(),
            'non_votant' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'non_votant')->count(),
        ];

        // Ajouter les informations de groupe au député
        $deputyData = $deputy->toArray();
        $deputyData['political_group'] = [
            'libelle' => $deputy->groupe_politique,
            'libelle_abrege' => $deputy->groupe_politique,
            'couleur_associee' => null,
        ];

        return Inertia::render('Deputies/Votes', [
            'deputy' => $deputyData,
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
            ->where('deputy_id', $deputy->id);

        // Filtrer par position si demandé
        if ($position = $request->get('position')) {
            $query->where('position', $position);
        }

        // Trier
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $votes = $query->paginate($request->get('per_page', 20));

        // Statistiques
        $stats = [
            'total' => DeputyVote::where('deputy_id', $deputy->id)->count(),
            'pour' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'pour')->count(),
            'contre' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'contre')->count(),
            'abstention' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'abstention')->count(),
            'non_votant' => DeputyVote::where('deputy_id', $deputy->id)->where('position', 'non_votant')->count(),
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
