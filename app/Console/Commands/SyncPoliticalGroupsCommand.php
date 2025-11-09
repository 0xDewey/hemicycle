<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PoliticalGroup;
use ZipArchive;

class SyncPoliticalGroupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:sync-political-groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les groupes politiques depuis l\'Assemblée nationale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://www.data.gouv.fr/api/1/datasets/r/4612d596-9a78-4ec6-b60c-ccc1ee11f8c0';
        
        $this->info("📥 Téléchargement des données des groupes politiques...");
        $this->info("Source: data.gouv.fr");

        try {
            // Télécharger le fichier CSV
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                $this->error("❌ Erreur lors du téléchargement : " . $response->status());
                return 1;
            }

            $csvPath = storage_path('app/groupes.csv');
            file_put_contents($csvPath, $response->body());
            $this->info("✓ Fichier téléchargé");

            // Lire le fichier CSV
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                $this->error("❌ Impossible d'ouvrir le fichier CSV");
                return 1;
            }

            // Lire l'en-tête
            $header = fgetcsv($handle, 0, ',');
            if (!$header) {
                $this->error("❌ Fichier CSV vide ou invalide");
                fclose($handle);
                return 1;
            }

            $this->info("✓ Fichier CSV ouvert");
            
            // Compter les lignes pour la barre de progression
            $lineCount = 0;
            while (!feof($handle)) {
                if (fgets($handle)) {
                    $lineCount++;
                }
            }
            rewind($handle);
            fgetcsv($handle, 0, ','); // Skip header again

            $bar = $this->output->createProgressBar($lineCount);
            $bar->start();

            $count = 0;

            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                try {
                    if (count($data) < count($header)) {
                        $bar->advance();
                        continue;
                    }

                    $row = array_combine($header, $data);
                    
                    // Le CSV contient directement les IDs des groupes (pas de codeType)
                    $uid = $row['id'] ?? null;
                    if (!$uid) {
                        $bar->advance();
                        continue;
                    }

                    // Ne prendre que les groupes qui commencent par PO (organes)
                    if (!str_starts_with($uid, 'PO')) {
                        $bar->advance();
                        continue;
                    }

                    PoliticalGroup::updateOrCreate(
                        ['uid' => $uid],
                        [
                            'code' => $row['libelleAbrege'] ?? $row['libelleAbrev'] ?? null,
                            'libelle' => $row['libelle'] ?? 'Groupe sans nom',
                            'libelle_abrege' => $row['libelleAbrege'] ?? $row['libelleAbrev'] ?? null,
                            'couleur_associee' => $row['couleurAssociee'] ?? null,
                            'position_politique' => isset($row['positionPolitique']) && $row['positionPolitique'] !== '' ? (int)$row['positionPolitique'] : null,
                            'date_debut' => !empty($row['dateDebut']) ? $row['dateDebut'] : null,
                            'date_fin' => null, // Pas de dateFin dans ce CSV (groupes actifs)
                            'meta' => $row,
                            'last_synced_at' => now(),
                        ]
                    );

                    $count++;
                } catch (\Exception $e) {
                    $this->warn("⚠ Erreur pour une ligne : " . $e->getMessage());
                }

                $bar->advance();
            }

            fclose($handle);
            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Synchronisation terminée : $count groupes politiques mis à jour");

            // Nettoyer le fichier temporaire
            unlink($csvPath);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur : " . $e->getMessage());
            return 1;
        }
    }
}
