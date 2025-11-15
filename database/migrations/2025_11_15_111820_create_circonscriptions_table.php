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
        Schema::create('circonscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->string('code_departement', 10); 
            $table->integer('numero'); 
            $table->string('nom')->nullable(); 
            $table->text('geojson')->nullable(); 
            $table->timestamps();

            $table->index(['code_departement', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('circonscriptions');
    }
};
