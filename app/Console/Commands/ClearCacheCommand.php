<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hemicycle:clear-cache {--type=* : Types de cache à vider (deputies, votes, circonscriptions, homepage, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vider le cache Redis pour certaines parties de l\'application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $types = $this->option('type');
        
        // Si aucun type n'est spécifié, vider tout
        if (empty($types)) {
            $types = ['all'];
        }

        $cleared = [];

        foreach ($types as $type) {
            switch ($type) {
                case 'deputies':
                    $this->clearDeputiesCache();
                    $cleared[] = 'Députés';
                    break;

                case 'votes':
                    $this->clearVotesCache();
                    $cleared[] = 'Votes';
                    break;

                case 'circonscriptions':
                    $this->clearCirconscriptionsCache();
                    $cleared[] = 'Circonscriptions';
                    break;

                case 'homepage':
                    Cache::forget('homepage.stats');
                    $cleared[] = 'Page d\'accueil';
                    break;

                case 'political-groups':
                    $this->clearPoliticalGroupsCache();
                    $cleared[] = 'Groupes politiques';
                    break;

                case 'all':
                    Cache::flush();
                    $this->info('✓ Tout le cache Redis a été vidé');
                    return Command::SUCCESS;
            }
        }

        if (!empty($cleared)) {
            $this->info('✓ Cache vidé pour : ' . implode(', ', $cleared));
        }

        return Command::SUCCESS;
    }

    /**
     * Vider le cache des députés
     */
    protected function clearDeputiesCache()
    {
        // Cache des services
        Cache::forget('deputies.all');
        Cache::forget('departments.with_count');
        Cache::forget('deputies.search_options');

        // Cache paramétré par département (on doit tous les vider)
        // Note: Redis ne supporte pas les wildcards natifs, mais on peut itérer
        $departments = \App\Models\Deputy::select('departement')
            ->distinct()
            ->pluck('departement');

        foreach ($departments as $code) {
            Cache::forget("deputies.department.{$code}");
            Cache::forget("department.stats.{$code}");
        }
    }

    /**
     * Vider le cache des votes
     */
    protected function clearVotesCache()
    {
        Cache::forget('votes.stats');
        Cache::forget('votes.types');

        // Cache paramétré par vote ID
        $votes = \App\Models\Vote::pluck('id');
        foreach ($votes as $voteId) {
            Cache::forget("vote.{$voteId}.details");
        }
    }

    /**
     * Vider le cache des circonscriptions
     */
    protected function clearCirconscriptionsCache()
    {
        Cache::forget('circonscriptions.geojson');

        // Cache paramétré par département
        $departments = \App\Models\Circonscription::select('code_departement')
            ->distinct()
            ->pluck('code_departement');

        foreach ($departments as $code) {
            Cache::forget("circonscriptions.department.{$code}");
        }
    }

    /**
     * Vider le cache des groupes politiques
     */
    protected function clearPoliticalGroupsCache()
    {
        Cache::forget('political_groups.list');
        Cache::forget('political_groups.with_deputies');

        // Cache paramétré par groupe
        $groups = \App\Models\PoliticalGroup::pluck('id');
        foreach ($groups as $groupId) {
            Cache::forget("political_group.{$groupId}.stats");
        }
    }
}
