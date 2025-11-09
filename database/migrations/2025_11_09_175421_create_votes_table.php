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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique(); // ID unique du scrutin
            $table->integer('numero')->nullable();
            $table->string('type')->nullable(); // Type de scrutin
            $table->date('date_scrutin')->nullable();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->integer('pour')->default(0);
            $table->integer('contre')->default(0);
            $table->integer('abstention')->default(0);
            $table->string('resultat')->nullable(); // adopté/rejeté
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
        Schema::dropIfExists('votes');
    }
};
