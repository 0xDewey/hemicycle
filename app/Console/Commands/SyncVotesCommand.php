<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Vote;
use App\Models\Deputy;
use App\Models\DeputyVote;
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
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://data.assemblee-nationale.fr/static/openData/repository/17/loi/scrutins/Scrutins.json.zip';
        $this->info("📥 Téléchargement des données des scrutins...");
        $this->info("Source: data.assemblee-nationale.fr");

        try {
            // Télécharger le fichier ZIP
            $response = Http::timeout(120)->get($url);

            if (!$response->successful()) {
                $this->error("❌ Erreur lors du téléchargement : " . $response->status());
                return 1;
            }

            $zipPath = storage_path('app/scrutins.zip');
            file_put_contents($zipPath, $response->body());
            $this->info("✓ Fichier téléchargé (" . $this->formatBytes(strlen($response->body())) . ")");

            // Extraire le ZIP
            $extractPath = storage_path('app/scrutins');
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();
                $this->info("✓ Fichier extrait");
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
                $jsonFiles = array_slice($jsonFiles, 0, (int)$limit);
            }

            $this->info("📊 Synchronisation de " . count($jsonFiles) . " scrutins...");

            $bar = $this->output->createProgressBar(count($jsonFiles));
            $bar->start();

            $countVotes = 0;
            $countDeputyVotes = 0;

            foreach ($jsonFiles as $jsonFile) {
                try {
                    $data = json_decode(file_get_contents($jsonFile), true);
                    
                    if (!isset($data['scrutin'])) {
                        $bar->advance();
                        continue;
                    }

                    $scrutin = $data['scrutin'];
                    $uid = $scrutin['uid'] ?? null;

                    if (!$uid) {
                        $bar->advance();
                        continue;
                    }

                    // Extraire les informations du scrutin
                    $numero = $scrutin['numero'] ?? null;
                    $titre = $scrutin['titre'] ?? 'Scrutin sans titre';
                    // Tronquer le titre à 250 caractères pour éviter les erreurs de base de données
                    $titre = mb_substr($titre, 0, 250);
                    $dateScrutin = $scrutin['dateScrutin'] ?? null;

                    // Extraire le type de vote
                    $typeVote = $scrutin['typeVote']['libelleTypeVote'] ?? null;

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
                            'meta' => $scrutin,
                            'last_synced_at' => now(),
                        ]
                    );

                    $countVotes++;

                    // Importer les votes individuels des députés
                    $deputyVotesImported = $this->importDeputyVotes($vote, $scrutin);
                    $countDeputyVotes += $deputyVotesImported;

                } catch (\Exception $e) {
                    $this->warn("⚠ Erreur pour " . basename($jsonFile) . " : " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Synchronisation terminée :");
            $this->info("   - $countVotes scrutins mis à jour");
            $this->info("   - $countDeputyVotes votes individuels importés");

            // Nettoyer les fichiers temporaires
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            $this->rrmdir($extractPath);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Importer les votes individuels des députés pour un scrutin
     */
    private function importDeputyVotes(Vote $vote, array $scrutin): int
    {
        $count = 0;
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
                $count += $this->processVotants($vote, $votants, 'pour');
            }

            // Traiter les votes "contre"
            if (isset($decompteNominatif['contres']['votant'])) {
                $votants = $decompteNominatif['contres']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $count += $this->processVotants($vote, $votants, 'contre');
            }

            // Traiter les abstentions
            if (isset($decompteNominatif['abstentions']['votant'])) {
                $votants = $decompteNominatif['abstentions']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $count += $this->processVotants($vote, $votants, 'abstention');
            }

            // Traiter les non-votants
            if (isset($decompteNominatif['nonVotants']['votant'])) {
                $votants = $decompteNominatif['nonVotants']['votant'];
                if (isset($votants['acteurRef'])) {
                    $votants = [$votants];
                }
                $count += $this->processVotants($vote, $votants, 'non_votant');
            }
        }

        return $count;
    }

    /**
     * Traiter une liste de votants
     */
    private function processVotants(Vote $vote, array $votants, string $position): int
    {
        $count = 0;

        foreach ($votants as $votant) {
            try {
                $acteurRef = $votant['acteurRef'] ?? null;
                if (!$acteurRef) {
                    continue;
                }

                // Trouver le député correspondant
                $deputy = Deputy::where('uid', $acteurRef)->first();
                if (!$deputy) {
                    continue;
                }

                DeputyVote::updateOrCreate(
                    [
                        'deputy_id' => $deputy->id,
                        'vote_id' => $vote->id,
                    ],
                    [
                        'acteur_ref' => $acteurRef,
                        'mandat_ref' => $votant['mandatRef'] ?? null,
                        'position' => $position,
                        'cause_position' => $votant['causePositionVote'] ?? null,
                        'par_delegation' => ($votant['parDelegation'] ?? 'false') === 'true',
                        'num_place' => $votant['numPlace'] ?? null,
                    ]
                );

                $count++;
            } catch (\Exception $e) {
                // Ignorer les erreurs individuelles
            }
        }

        return $count;
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

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Supprimer récursivement un répertoire
     */
    private function rrmdir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $path = $dir . "/" . $object;
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
