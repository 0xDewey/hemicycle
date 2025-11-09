<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vote;
use App\Models\DeputyVote;
use App\Models\Deputy;

class VerifyVotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:verify-votes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la cohérence des données de votes importées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("🔍 Vérification des données de votes...");
        $this->newLine();

        // 1. Vérifier le nombre de votes
        $totalVotes = Vote::count();
        $this->info("📊 Total de scrutins : $totalVotes");

        // 2. Vérifier le nombre de votes individuels
        $totalDeputyVotes = DeputyVote::count();
        $this->info("📊 Total de votes individuels : $totalDeputyVotes");

        // 3. Vérifier le nombre de députés
        $totalDeputies = Deputy::count();
        $this->info("📊 Total de députés : $totalDeputies");
        $this->newLine();

        // 4. Vérifier les scrutins sans votes individuels
        $votesWithoutDeputyVotes = Vote::doesntHave('deputyVotes')->count();
        if ($votesWithoutDeputyVotes > 0) {
            $this->warn("⚠ $votesWithoutDeputyVotes scrutin(s) sans votes individuels");
        } else {
            $this->info("✓ Tous les scrutins ont des votes individuels");
        }

        // 5. Vérifier la cohérence des totaux
        $this->info("🔍 Vérification de la cohérence des totaux...");
        $inconsistencies = 0;

        Vote::with('deputyVotes')->chunk(100, function ($votes) use (&$inconsistencies) {
            foreach ($votes as $vote) {
                $pour = $vote->deputyVotes->where('position', 'pour')->count();
                $contre = $vote->deputyVotes->where('position', 'contre')->count();
                $abstention = $vote->deputyVotes->where('position', 'abstention')->count();

                if ($pour != $vote->pour || $contre != $vote->contre || $abstention != $vote->abstention) {
                    $this->warn("⚠ Incohérence pour le scrutin #{$vote->numero} ({$vote->uid}):");
                    $this->line("   Base: Pour=$vote->pour, Contre=$vote->contre, Abstention=$vote->abstention");
                    $this->line("   Votes: Pour=$pour, Contre=$contre, Abstention=$abstention");
                    $inconsistencies++;
                }
            }
        });

        if ($inconsistencies === 0) {
            $this->info("✓ Tous les totaux sont cohérents");
        } else {
            $this->error("✗ $inconsistencies incohérence(s) détectée(s)");
        }
        $this->newLine();

        // 6. Statistiques par position
        $this->info("📊 Répartition des votes:");
        $positions = DeputyVote::selectRaw('position, COUNT(*) as count')
            ->groupBy('position')
            ->get();

        foreach ($positions as $position) {
            $this->line("   - {$position->position}: {$position->count}");
        }
        $this->newLine();

        // 7. Députés les plus actifs
        $this->info("👥 Top 10 députés les plus actifs:");
        $activeDeputies = DeputyVote::selectRaw('deputy_id, COUNT(*) as vote_count')
            ->groupBy('deputy_id')
            ->orderByDesc('vote_count')
            ->limit(10)
            ->with('deputy')
            ->get();

        foreach ($activeDeputies as $index => $deputyVote) {
            $deputy = $deputyVote->deputy;
            if ($deputy) {
                $this->line(sprintf(
                    "   %d. %s %s - %d votes",
                    $index + 1,
                    $deputy->prenom,
                    $deputy->nom,
                    $deputyVote->vote_count
                ));
            }
        }
        $this->newLine();

        // 8. Scrutins récents
        $this->info("📅 5 scrutins les plus récents:");
        $recentVotes = Vote::orderByDesc('date_scrutin')->limit(5)->get();

        foreach ($recentVotes as $vote) {
            $this->line(sprintf(
                "   - %s: %s (Pour: %d, Contre: %d)",
                $vote->date_scrutin->format('d/m/Y'),
                \Str::limit($vote->titre, 60),
                $vote->pour,
                $vote->contre
            ));
        }

        $this->newLine();
        $this->info("✅ Vérification terminée !");

        return 0;
    }
}
