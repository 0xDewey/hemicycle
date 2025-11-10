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
        Deputy::truncate();
        $defaultUrl = 'https://data.assemblee-nationale.fr/static/openData/repository/17/amo/deputes_actifs_mandats_actifs_organes_divises/AMO40_deputes_actifs_mandats_actifs_organes_divises.json.zip';
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
            $jsonFiles = glob("$extractPath/acteur/*.json");
            if (empty($jsonFiles)) {
                $this->error("❌ Aucun fichier JSON trouvé dans $extractPath/acteur/");
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

                    // Trouver le parti politique (PARPOL) actif
                    $groupePolitique = null;
                    $groupePolitiqueAbrege = null;
                    $parpolOrganeRef = null;
                    
                    foreach ($mandats as $mandat) {
                        if (($mandat['typeOrgane'] ?? '') === 'PARPOL' && 
                            (empty($mandat['dateFin']) || $mandat['dateFin'] === null)) {
                            $parpolOrganeRef = $mandat['organes']['organeRef'] ?? null;
                            break;
                        }
                    }
                    
                    // Si un organe PARPOL est trouvé, charger ses informations
                    if ($parpolOrganeRef) {
                        $organeFile = "$extractPath/organe/{$parpolOrganeRef}.json";
                        if (file_exists($organeFile)) {
                            $organeData = json_decode(file_get_contents($organeFile), true);
                            $organe = $organeData['organe'] ?? [];
                            
                            if (($organe['codeType'] ?? '') === 'PARPOL') {
                                $groupePolitique = $organe['libelle'] ?? null;
                                $groupePolitiqueAbrege = $organe['libelleAbrev'] ?? null;
                                
                                // Créer/mettre à jour le groupe politique dans la base de données
                                if ($groupePolitique && $groupePolitiqueAbrege) {
                                    \App\Models\PoliticalGroup::updateOrCreate(
                                        ['uid' => $parpolOrganeRef],
                                        [
                                            'nom' => $groupePolitique,
                                            'sigle' => $groupePolitiqueAbrege,
                                            'couleur' => $this->getPartyColor($groupePolitiqueAbrege),
                                            'libelle' => $groupePolitique,
                                            'libelle_abrege' => $groupePolitiqueAbrege,
                                        ]
                                    );
                                }
                            }
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
                            'groupe_politique' => $groupePolitiqueAbrege ?: null,
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
    
    /**
     * Obtenir une couleur par défaut pour un parti politique
     */
    private function getPartyColor($sigle)
    {
        // Couleurs approximatives des principaux partis français
        $colors = [
            'RN' => '#0d378a', // Rassemblement National - Bleu foncé
            'LR' => '#0066cc', // Les Républicains - Bleu
            'UDI' => '#00a8ff', // UDI - Bleu clair
            'LIOT' => '#ffa500', // LIOT - Orange
            'RE' => '#ffeb00', // Renaissance - Jaune
            'DEM' => '#ff9900', // Démocrate - Orange clair
            'HOR' => '#ffd700', // Horizons - Or
            'ECO' => '#00c000', // Écologiste - Vert
            'SOC' => '#ff1493', // Socialistes - Rose
            'LFI' => '#c9462c', // La France Insoumise - Rouge
            'FI' => '#c9462c', // France Insoumise - Rouge
            'GDR' => '#dd0000', // Gauche démocrate et républicaine - Rouge foncé
            'PCF' => '#dd0000', // Parti communiste français - Rouge
            'PS' => '#ff1493', // Parti socialiste - Rose
            'UMP' => '#0066cc', // Union pour un Mouvement Populaire - Bleu
            'MODEM' => '#ff9900', // MoDem - Orange
        ];

        return $colors[strtoupper($sigle)] ?? '#808080'; // Gris par défaut
    }
}
