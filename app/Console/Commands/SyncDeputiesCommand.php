<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\Deputy;
use ZipArchive;

class SyncDeputiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:sync-deputies {--url= : URL personnalisée du fichier ZIP}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les données des députés depuis l\'Assemblée nationale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // URL par défaut ou URL personnalisée
        $defaultUrl = 'https://www.data.gouv.fr/api/1/datasets/r/fff6c0df-c527-4c6d-824b-46dff71db0ec';
        $url = $this->option('url') ?: $defaultUrl;

        $this->info("📥 Téléchargement des données des députés actifs...");
        $this->info("Source: " . parse_url($url, PHP_URL_HOST));

        if ($this->option('url')) {
            $this->warn("⚠ Utilisation d'une URL personnalisée");
        }

        try {
            // Télécharger le fichier ZIP
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                $this->error("❌ Erreur lors du téléchargement : " . $response->status());
                $this->newLine();
                $this->warn("💡 Pour trouver l'URL correcte :");
                $this->line("  1. Visitez https://www.data.gouv.fr/datasets/deputes-actifs");
                $this->line("  2. Téléchargez le fichier JSON ZIP des députés actifs");
                $this->line("  3. Utilisez: php artisan data:sync-deputies --url=VOTRE_URL");
                return 1;
            }

            $zipPath = storage_path('app/deputes.zip');
            file_put_contents($zipPath, $response->body());
            $this->info("✓ Fichier téléchargé");

            // Extraire le ZIP
            $extractPath = storage_path('app/deputes');
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

            // Lire tous les fichiers JSON des députés
            $jsonFiles = glob("$extractPath/json/acteur/*.json");
            if (empty($jsonFiles)) {
                $this->error("❌ Aucun fichier JSON trouvé dans $extractPath/json/acteur/");
                return 1;
            }

            $this->info("📊 Synchronisation de " . count($jsonFiles) . " députés...");

            $bar = $this->output->createProgressBar(count($jsonFiles));
            $bar->start();

            $count = 0;
            foreach ($jsonFiles as $jsonFile) {
                try {
                    $data = json_decode(file_get_contents($jsonFile), true);
                    
                    if (!isset($data['acteur'])) {
                        $bar->advance();
                        continue;
                    }

                    $acteur = $data['acteur'];
                    
                    // Extraire l'UID
                    $uid = $acteur['uid']['#text'] ?? $acteur['uid'] ?? null;
                    
                    if (!$uid) {
                        $bar->advance();
                        continue;
                    }

                    // Extraire les informations d'état civil
                    $etatCivil = $acteur['etatCivil'] ?? [];
                    $ident = $etatCivil['ident'] ?? [];
                    $nom = $ident['nom'] ?? 'Inconnu';
                    $prenom = $ident['prenom'] ?? 'Inconnu';
                    $civ = $ident['civ'] ?? null;

                    // Récupérer le mandat parlementaire actuel
                    $mandats = $acteur['mandats']['mandat'] ?? [];
                    if (!is_array($mandats)) {
                        $mandats = [$mandats];
                    }

                    // Trouver le mandat parlementaire actif (type ASSEMBLEE)
                    $mandatParlementaire = null;
                    foreach ($mandats as $mandat) {
                        if (($mandat['typeOrgane'] ?? '') === 'ASSEMBLEE' && 
                            (empty($mandat['dateFin']) || $mandat['dateFin'] === null)) {
                            $mandatParlementaire = $mandat;
                            break;
                        }
                    }

                    // Si pas de mandat parlementaire actif trouvé, chercher le plus récent
                    if (!$mandatParlementaire) {
                        foreach ($mandats as $mandat) {
                            if (($mandat['typeOrgane'] ?? '') === 'ASSEMBLEE') {
                                $mandatParlementaire = $mandat;
                                break;
                            }
                        }
                    }

                    // Trouver le groupe politique actif et extraire le libellé
                    $groupePolitique = null;
                    $groupePolitiqueAbrege = null;
                    foreach ($mandats as $mandat) {
                        if (($mandat['typeOrgane'] ?? '') === 'GP' && 
                            (empty($mandat['dateFin']) || $mandat['dateFin'] === null)) {
                            // Extraire le libellé complet et l'abrégé
                            $libelle = $mandat['libelle'] ?? null;
                            if ($libelle) {
                                $groupePolitique = $libelle;
                                // Extraire l'abréviation entre parenthèses ou prendre le premier mot
                                if (preg_match('/\(([^)]+)\)/', $libelle, $matches)) {
                                    $groupePolitiqueAbrege = $matches[1];
                                } else {
                                    $groupePolitiqueAbrege = explode(' ', $libelle)[0];
                                }
                            }
                            break;
                        }
                    }

                    // Extraire les informations de circonscription
                    $election = $mandatParlementaire['election'] ?? [];
                    $lieu = $election['lieu'] ?? [];
                    
                    Deputy::updateOrCreate(
                        ['uid' => $uid],
                        [
                            'nom' => $nom,
                            'prenom' => $prenom,
                            'circonscription' => $lieu['numCirco'] ?? null,
                            'departement' => $lieu['numDepartement'] ?? null,
                            'groupe_politique' => $groupePolitiqueAbrege ?: 'Non inscrit',
                            'photo' => null, // À compléter ultérieurement
                            'slug' => \Str::slug($prenom . '-' . $nom),
                            'meta' => $acteur,
                            'last_synced_at' => now(),
                        ]
                    );

                    $count++;
                } catch (\Exception $e) {
                    $this->warn("⚠ Erreur pour le fichier " . basename($jsonFile) . " : " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Synchronisation terminée : $count députés mis à jour");

            // Nettoyer les fichiers temporaires
            unlink($zipPath);
            $this->rrmdir($extractPath);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Supprimer récursivement un répertoire et son contenu
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
