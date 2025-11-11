<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('political_groups', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // PO123456
            $table->string('code')->nullable(); // Code court
            $table->string('libelle'); // Nom complet
            $table->string('libelle_abrege')->nullable(); // Nom abrégé
            $table->string('couleur_associee')->nullable(); // Couleur hexadécimale
            $table->integer('position_politique')->nullable(); // Position sur l'échiquier politique
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->json('meta')->nullable(); // Données complètes JSON
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('political_groups');
    }
};
