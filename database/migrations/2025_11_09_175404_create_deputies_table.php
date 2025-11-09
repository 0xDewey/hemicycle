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
        Schema::create('deputies', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('circonscription')->nullable();
            $table->string('departement')->nullable();
            $table->string('groupe_politique')->nullable();
            $table->string('photo')->nullable();
            $table->string('slug')->unique();
            $table->json('meta')->nullable(); // Données complémentaires (emails, site, etc.)
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deputies');
    }
};
