<?php

namespace App\Console\Commands;

use App\Models\Deputy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
        $defaultUrl = 'https://data.assemblee-nationale.fr/static/openData/repository/17/amo/deputes_actifs_mandats_actifs_organes_divises/AMO40_deputes_actifs_mandats_actifs_organes_divises.json.zip';
        $url = $this->option('url') ?: $defaultUrl;

        $this->info('📥 Téléchargement des données des députés actifs...');
        $this->info('Source: '.parse_url($url, PHP_URL_HOST));

        if ($this->option('url')) {
            $this->warn("⚠ Utilisation d'une URL personnalisée");
        }

        try {
            // Télécharger le fichier ZIP
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                $this->error('❌ Erreur lors du téléchargement : '.$response->status());
                $this->newLine();
                $this->warn("💡 Pour trouver l'URL correcte :");
                $this->line('  1. Visitez https://www.data.gouv.fr/datasets/deputes-actifs');
                $this->line('  2. Téléchargez le fichier JSON ZIP des députés actifs');
                $this->line('  3. Utilisez: php artisan data:sync-deputies --url=VOTRE_URL');

                return 1;
            }

            $zipPath = storage_path('app/deputes.zip');
            file_put_contents($zipPath, $response->body());
            $this->info('✓ Fichier téléchargé');

            // Extraire le ZIP
            $extractPath = storage_path('app/deputes');
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

            // Lire tous les fichiers JSON des députés
            $jsonFiles = glob("$extractPath/acteur/*.json");
            if (empty($jsonFiles)) {
                $this->error("❌ Aucun fichier JSON trouvé dans $extractPath/acteur/");

                return 1;
            }

            $this->info('📊 Synchronisation de '.count($jsonFiles).' députés...');

            $bar = $this->output->createProgressBar(count($jsonFiles));
            $bar->start();

            $count = 0;
            $processedUids = []; // Pour tracker les députés traités
            
            foreach ($jsonFiles as $jsonFile) {
                try {
                    $data = json_decode(file_get_contents($jsonFile), true);

                    if (! isset($data['acteur'])) {
                        $bar->advance();

                        continue;
                    }

                    $acteur = $data['acteur'];

                    // Extraire l'UID
                    $uid = $acteur['uid']['#text'] ?? $acteur['uid'] ?? null;

                    if (! $uid) {
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
                    if (! is_array($mandats)) {
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
                    if (! $mandatParlementaire) {
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
                    $causeMandat = $election['causeMandat'] ?? null;
                    $refCirconscription = $election['refCirconscription'] ?? null;

                    // Extraire les dates de mandat
                    $mandateStartDate = $mandatParlementaire['dateDebut'] ?? null;
                    $mandateEndDate = $mandatParlementaire['dateFin'] ?? null;
                    
                    // Un député est actif si dateFin est null ou vide
                    $isActive = empty($mandateEndDate);

                    // Vérifier si le député existe déjà pour récupérer sa photo existante
                    $existingDeputy = Deputy::where('uid', $uid)->first();
                    $localPhotoPath = $existingDeputy?->photo;

                    // Télécharger la photo uniquement si elle n'existe pas déjà
                    if (!$localPhotoPath) {
                        // Format source: https://www.assemblee-nationale.fr/dyn/static/tribun/17/photos/carre/643089.jpg
                        // Le numéro est l'UID sans le préfixe "PA"
                        $photoNumber = str_replace('PA', '', $uid);
                        $photoUrl = "https://www.assemblee-nationale.fr/dyn/static/tribun/17/photos/carre/{$photoNumber}.jpg";
                        
                        // Télécharger et sauvegarder localement
                        try {
                            $photoResponse = Http::timeout(10)->get($photoUrl);
                            if ($photoResponse->successful()) {
                                $photoDir = storage_path('app/public/deputies');
                                if (!file_exists($photoDir)) {
                                    mkdir($photoDir, 0755, true);
                                }
                                
                                $photoFilename = "{$photoNumber}.jpg";
                                $photoPath = "{$photoDir}/{$photoFilename}";
                                file_put_contents($photoPath, $photoResponse->body());
                                $localPhotoPath = "deputies/{$photoFilename}";
                            }
                        } catch (\Exception $e) {
                            // Si le téléchargement échoue, on continue sans photo
                        }
                    }

                    Deputy::updateOrCreate(
                        ['uid' => $uid],
                        [
                            'nom' => $nom,
                            'prenom' => $prenom,
                            'circonscription' => $lieu['numCirco'] ?? null,
                            'departement' => $lieu['numDepartement'] ?? null,
                            'groupe_politique' => $groupePolitiqueAbrege ?: null,
                            'mandate_start_date' => $mandateStartDate,
                            'mandate_end_date' => $mandateEndDate,
                            'is_active' => $isActive,
                            'cause_mandat' => $causeMandat,
                            'ref_circonscription' => $refCirconscription,
                            'photo' => $localPhotoPath,
                            'slug' => \Str::slug($prenom.'-'.$nom),
                            'meta' => $acteur,
                            'last_synced_at' => now(),
                        ]
                    );

                    // Ajouter l'UID à la liste des députés traités
                    $processedUids[] = $uid;

                    $count++;
                } catch (\Exception $e) {
                    $this->warn('⚠ Erreur pour le fichier '.basename($jsonFile).' : '.$e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Synchronisation terminée : $count députés mis à jour");

            // Désactiver les députés qui ne sont plus dans le fichier d'import
            $deactivatedCount = Deputy::whereNotIn('uid', $processedUids)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            
            if ($deactivatedCount > 0) {
                $this->info("📝 $deactivatedCount député(s) désactivé(s)");
            }

            // Vider le cache des députés
            $this->info('🔄 Vidage du cache des députés...');
            $this->call('hemicycle:clear-cache', ['--type' => ['deputies', 'homepage']]);

            // Nettoyer les fichiers temporaires
            unlink($zipPath);
            $this->rrmdir($extractPath);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Supprimer récursivement un répertoire et son contenu
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

    /**
     * Obtenir une couleur par défaut pour un parti politique
     */
    private function getPartyColor($sigle)
    {
        // Couleurs officielles des groupes parlementaires de la XVIIe législature (2024-2029)
        $colors = [
            // Extrême droite
            'RN' => '#0d378a', // Rassemblement National - Bleu marine

            // Droite
            'REP' => '#0066CC', // Les Républicains - Bleu
            'DR' => '#0066CC', // Droite Républicaine - Bleu
            'UDR' => '#0066CC', // Union des Démocrates et Républicains - Bleu

            // Centre droit
            'UDI' => '#00ADEE', // UDI - Bleu clair
            'LIOT' => '#ee7f01', // Libertés, Indépendants, Outre-mer et Territoires - Orange

            // Centre
            'EPR' => '#FFEB00', // Ensemble pour la République - Jaune
            'RE' => '#FFEB00', // Renaissance (ex-LREM) - Jaune
            'ESBMP' => '#FFEB00', // Ensemble - Jaune
            'ENSEM' => '#FFEB00', // Ensemble - Jaune
            'MODEM' => '#FF9900', // MoDem - Orange
            'DEM' => '#FF9900', // Démocrate - Orange
            'HOR' => '#F07C13', // Horizons - Orange foncé
            'ACT' => '#FFEB00', // Agir ensemble - Jaune

            // Gauche écologiste
            'ECO' => '#00C000', // Écologiste - NUPES - Vert
            'ECOLO' => '#00C000', // Écologiste - Vert
            'EELV' => '#00C000', // Europe Écologie Les Verts - Vert

            // Gauche socialiste
            'SOC' => '#FF8080', // Socialistes et apparentés - Rose
            'PS' => '#FF8080', // Parti socialiste - Rose
            'GDR' => '#DD0000', // Gauche démocrate et républicaine - NUPES - Rouge
            'RPS' => '#681F62', // Régions et Peuples Solidaires - Violet

            // Gauche radicale
            'LFI' => '#CC2443', // La France Insoumise - NUPES - Rouge carmin
            'FI' => '#CC2443', // France Insoumise - Rouge carmin
            'NUPES' => '#CC2443', // NUPES - Rouge carmin
            'GDR-NUPES' => '#DD0000', // Gauche démocrate et républicaine - NUPES - Rouge

            // Autres
            'NI' => '#CCCCCC', // Non-inscrits - Gris
            'PCF' => '#DD0000', // Parti communiste français - Rouge
            'PRG' => '#FF8080', // Parti radical de gauche - Rose
            'UMP' => '#0066CC', // Union pour un Mouvement Populaire (historique) - Bleu
        ];

        return $colors[strtoupper($sigle)] ?? '#808080'; // Gris par défaut
    }
}
