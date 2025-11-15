<?php

namespace App\Console\Commands;

use App\Models\Circonscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncCirconscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:sync-circonscriptions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les circonscriptions législatives depuis data.gouv.fr';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = 'https://www.data.gouv.fr/api/1/datasets/r/8b681b69-739c-47eb-a96b-06e8e2d8dc08';

        $this->info('📥 Téléchargement des données des circonscriptions...');
        $this->info('Source: data.gouv.fr');

        try {
            // Télécharger le fichier GeoJSON
            $response = Http::timeout(60)->get($url);

            if (!$response->successful()) {
                $this->error('❌ Erreur lors du téléchargement : ' . $response->status());
                return 1;
            }

            $this->info('✓ Fichier téléchargé');

            // Parser le GeoJSON
            $geojson = $response->json();

            if (!isset($geojson['features'])) {
                $this->error('❌ Format GeoJSON invalide');
                return 1;
            }

            $features = $geojson['features'];
            $this->info('📊 Traitement de ' . count($features) . ' circonscriptions...');

            $bar = $this->output->createProgressBar(count($features));
            $bar->start();

            $count = 0;
            foreach ($features as $feature) {
                try {
                    $properties = $feature['properties'] ?? [];
                    $geometry = $feature['geometry'] ?? null;

                    // Extraire les informations depuis la nouvelle structure
                    $codeDepartement = $properties['codeDepartement'] ?? null;
                    $nomDepartement = $properties['nomDepartement'] ?? null;
                    $codeCirconscription = $properties['codeCirconscription'] ?? null;
                    $nomCirconscription = $properties['nomCirconscription'] ?? null;

                    // Extraire le numéro de circonscription depuis le code
                    // Format: "9213" -> "13", "ZM02" -> "02", "7515" -> "15"
                    $numero = null;
                    if ($codeCirconscription && strlen($codeCirconscription) >= 2) {
                        // Prendre les 2 derniers caractères
                        $numero = substr($codeCirconscription, -2);
                    }

                    // Utiliser le code de circonscription comme référence unique
                    $ref = $codeCirconscription;

                    if (!$ref || !$codeDepartement || !$numero) {
                        $bar->advance();
                        continue;
                    }

                    // Sauvegarder la géométrie en JSON
                    $geojsonGeometry = $geometry ? json_encode($geometry) : null;

                    Circonscription::updateOrCreate(
                        ['ref' => $ref],
                        [
                            'code_departement' => $codeDepartement,
                            'numero' => $numero,
                            'nom' => $nomCirconscription ?: "Circonscription {$numero} - {$nomDepartement}",
                            'geojson' => $geojsonGeometry,
                        ]
                    );

                    $count++;
                } catch (\Exception $e) {
                    $this->warn('⚠ Erreur pour une circonscription : ' . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Synchronisation terminée : $count circonscriptions mises à jour");

            // Vider le cache des circonscriptions
            $this->info('🔄 Vidage du cache des circonscriptions...');
            $this->call('hemicycle:clear-cache', ['--type' => ['circonscriptions']]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur : ' . $e->getMessage());
            return 1;
        }
    }
}
