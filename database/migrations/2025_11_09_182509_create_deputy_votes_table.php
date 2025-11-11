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
        Schema::create('deputy_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deputy_id')->constrained()->onDelete('cascade');
            $table->foreignId('vote_id')->constrained()->onDelete('cascade');
            $table->string('acteur_ref'); // PA508, etc.
            $table->string('mandat_ref'); // PM722736, etc.
            $table->enum('position', ['pour', 'contre', 'abstention', 'non_votant', 'non_votant_volontaire']);
            $table->string('cause_position')->nullable(); // MG (membre du gouvernement), etc.
            $table->boolean('par_delegation')->default(false);
            $table->string('num_place')->nullable();
            $table->timestamps();

            // Index pour performance
            $table->index('acteur_ref');
            $table->index('position');
            $table->unique(['deputy_id', 'vote_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deputy_votes');
    }
};
