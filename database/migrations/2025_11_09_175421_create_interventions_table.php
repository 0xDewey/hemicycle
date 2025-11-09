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
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // ID unique de l'intervention
            $table->foreignId('deputy_id')->nullable()->constrained('deputies')->onDelete('cascade');
            $table->string('deputy_uid')->nullable(); // UID du député
            $table->dateTime('date_intervention')->nullable();
            $table->string('type')->nullable(); // Question, débat, etc.
            $table->string('seance')->nullable();
            $table->text('contenu')->nullable();
            $table->json('meta')->nullable(); // Données complémentaires
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
