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
        Schema::table('political_groups', function (Blueprint $table) {
            $table->string('nom')->nullable()->after('uid');
            $table->string('sigle')->nullable()->after('nom');
            $table->string('couleur')->nullable()->after('sigle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('political_groups', function (Blueprint $table) {
            $table->dropColumn(['nom', 'sigle', 'couleur']);
        });
    }
};
