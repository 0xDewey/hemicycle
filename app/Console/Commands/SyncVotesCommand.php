<?php

namespace App\Console\Commands;

use App\Models\Deputy;
use App\Models\DeputyVote;
use App\Models\Vote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class SyncVotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:sync-votes {--limit= : Limiter le nombre de scrutins à importer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les données des scrutins depuis l\'Assemblée nationale';

    /**
     * Cache des députés pour éviter les requêtes répétées
     */
    private $deputiesCache = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://data.assemblee-nationale.fr/static/openData/repository/17/loi/scrutins/Scrutins.json.zip';
        $this->info('📥 Téléchargement des données des scrutins...');
        $this->info('Source: data.assemblee-nationale.fr');

        try {
            // Télécharger le fichier ZIP
            $response = Http::timeout(120)->get($url);

            if (! $response->successful()) {
                $this->error('❌ Erreur lors du téléchargement : '.$response->status());

                return 1;
            }

            $zipPath = storage_path('app/scrutins.zip');
            file_put_contents($zipPath, $response->body());
            $this->info('✓ Fichier téléchargé ('.$this->formatBytes(strlen($response->body())).')');

            // Extraire le ZIP
            $extractPath = storage_path('app/scrutins');
            if (! file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === true) {
                $zip->extractTo($extractPath);
                $zip->close();
                $this->info('✓ Fichier extrait');
            } else {
                $this->error("❌ Impossible d'extraire le fichier ZIP");

                return 1;
            }

            // Lire tous les fichiers JSON de scrutins
            $jsonFiles = glob("$extractPath/json/*.json");
            if (empty($jsonFiles)) {
                $this->error("❌ Aucun fichier JSON trouvé dans $extractPath/json/");

                return 1;
            }

            // Limiter si demandé
            if ($limit = $this->option('limit')) {
                $jsonFiles = array_slice($jsonFiles, 0, (int) $limit);
            }

            $this->info('📊 Synchronisation de '.count($jsonFiles).' scrutins...');

            // Charger tous les députés en cache une seule fois
            $this->info('📋 Chargement des députés en cache...');
            $deputies = Deputy::all();
            foreach ($deputies as $deputy) {
                $this->deputiesCache[$deputy->uid] = $deputy->id;
            }
            $this->info('✓ '.count($this->deputiesCache).' députés en cache');

            $bar = $this->output->createProgressBar(count($jsonFiles));
            $bar->start();

            $countVotes = 0;
            $countDeputyVotes = 0;

            foreach ($jsonFiles as $jsonFile) {
                try {
                    DB::beginTransaction();

                    $data = json_decode(file_get_contents($jsonFile), true);

                    if (! isset($data['scrutin'])) {
                        DB::rollBack();
                        $bar->advance();

                        continue;
                    }

                    $scrutin = $data['scrutin'];
                    $uid = $scrutin['uid'] ?? null;

                    if (! $uid) {
                        DB::rollBack();
                        $bar->advance();

                        continue;
                    }

                    // Extraire les informations du scrutin
                    $numero = $scrutin['numero'] ?? null;
                    $titre = ucfirst($scrutin['titre']) ?? 'Scrutin sans titre';
                    // Tronquer le titre à 250 caractères pour éviter les erreurs de base de données
                    $titre = mb_substr($titre, 0, 250);
                    $dateScrutin = $scrutin['dateScrutin'] ?? null;

                    // Extraire le type de vote
                    $typeVote = ucfirst($scrutin['typeVote']['libelleTypeVote']) ?? null;

                    // Extraire les résultats
                    $syntheseVote = $scrutin['syntheseVote'] ?? [];
                    $decompte = $syntheseVote['decompte'] ?? [];

                    $pour = $decompte['pour'] ?? 0;
                    $contre = $decompte['contre'] ?? 0;
                    $abstention = $decompte['abstentions'] ?? 0;

                    // Déterminer le résultat
                    $sort = $scrutin['sort']['code'] ?? 'rejeté';
                    $resultat = str_contains(strtolower($sort), 'adopt') ? 'adopté' : 'rejeté';

                    // Extraire la description
                    $description = $scrutin['objet']['libelle'] ?? null;

                    // Extraire le nom du demandeur
                    $demandeurNom = null;
                    if (isset($scrutin['demandeur']['texte'])) {
                        $demandeurNom = $scrutin['demandeur']['texte'];
                    }

                    // Créer ou mettre à jour le vote
                    $vote = Vote::updateOrCreate(
                        ['uid' => $uid],
                        [
                            'numero' => $numero,
                            'type' => $typeVote,
                            'date_scrutin' => $dateScrutin,
                            'titre' => $titre,
                            'description' => $description,
                            'pour' => $pour,
                            'contre' => $contre,
                            'abstention' => $abstention,
                            'resultat' => $resultat,
                            'demandeur' => $demandeurNom,
                            'meta' => $scrutin,
                            'last_synced_at' => now(),
                        ]
                    );

                    $countVotes++;

                    // Importer les votes individuels des députés
                    $deputyVotesImported = $this->importDeputyVotes($vote, $scrutin);
                    $countDeputyVotes += $deputyVotesImported;

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->warn('⚠ Erreur pour '.basename($jsonFile).' : '.$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info('✅ Synchronisation terminée :');
            $this->info("   - $countVotes scrutins mis à jour");
            $this->info("   - $countDeputyVotes votes individuels importés");

            // Nettoyer les fichiers temporaires
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            $this->rrmdir($extractPath);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Importer les votes individuels des députés pour un scrutin
     */
    private function importDeputyVotes(Vote $vote, array $scrutin): int
    {
        $deputyVotesData = [];
        $ventilation = $scrutin['ventilationVotes']['organe']['groupes']['groupe'] ?? [];

        foreach ($ventilation as $groupe) {
            $decompteNominatif = $groupe['vote']['decompteNominatif'] ?? [];

            // Traiter les votes "pour"
            if (isset($decompteNominatif['pours']['votant'])) {
                $votants = $decompteNominatif['pours']['votant'];
                // Si c'est un seul votant, le mettre dans un tableau
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $this->collectVotants($vote, $votants, 'pour', $deputyVotesData);
            }

            // Traiter les votes "contre"
            if (isset($decompteNominatif['contres']['votant'])) {
                $votants = $decompteNominatif['contres']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $this->collectVotants($vote, $votants, 'contre', $deputyVotesData);
            }

            // Traiter les abstentions
            if (isset($decompteNominatif['abstentions']['votant'])) {
                $votants = $decompteNominatif['abstentions']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $this->collectVotants($vote, $votants, 'abstention', $deputyVotesData);
            }

            // Traiter les non-votants
            if (isset($decompteNominatif['nonVotants']['votant'])) {
                $votants = $decompteNominatif['nonVotants']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $this->collectVotants($vote, $votants, 'non_votant', $deputyVotesData);
            }
        }

        // Batch insert/update tous les votes en une seule fois
        if (! empty($deputyVotesData)) {
            // Supprimer les votes existants pour ce scrutin
            DeputyVote::where('vote_id', $vote->id)->delete();

            // Insérer tous les nouveaux votes par batch
            foreach (array_chunk($deputyVotesData, 500) as $chunk) {
                DeputyVote::insert($chunk);
            }
        }

        return count($deputyVotesData);
    }

    /**
     * Collecter les votants dans un tableau pour batch insert
     */
    private function collectVotants(Vote $vote, array $votants, string $position, array &$deputyVotesData): void
    {
        $now = now();

        foreach ($votants as $votant) {
            try {
                $acteurRef = $votant['acteurRef'] ?? null;
                if (! $acteurRef) {
                    continue;
                }

                // Utiliser le cache au lieu d'une requête
                if (! isset($this->deputiesCache[$acteurRef])) {
                    continue;
                }

                $deputyId = $this->deputiesCache[$acteurRef];

                $deputyVotesData[] = [
                    'deputy_id' => $deputyId,
                    'vote_id' => $vote->id,
                    'acteur_ref' => $acteurRef,
                    'mandat_ref' => $votant['mandatRef'] ?? null,
                    'position' => $position,
                    'cause_position' => $votant['causePositionVote'] ?? null,
                    'par_delegation' => ($votant['parDelegation'] ?? 'false') === 'true',
                    'num_place' => $votant['numPlace'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Supprimer récursivement un répertoire
     */
    private function rrmdir($dir)
    {
        if (! is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                $path = $dir.'/'.$object;
                if (is_dir($path)) {
                    $this->rrmdir($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}
