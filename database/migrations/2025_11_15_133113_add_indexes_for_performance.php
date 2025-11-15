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
        Schema::table('deputies', function (Blueprint $table) {
            // Index pour les requêtes fréquentes
            $table->index(['departement', 'is_active'], 'idx_deputies_dept_active');
            $table->index(['circonscription', 'is_active'], 'idx_deputies_circ_active');
            $table->index(['groupe_politique', 'is_active'], 'idx_deputies_group_active');
            $table->index('slug', 'idx_deputies_slug');
        });

        Schema::table('circonscriptions', function (Blueprint $table) {
            // Index pour les requêtes par département
            $table->index('code_departement', 'idx_circonscriptions_dept');
            $table->index(['code_departement', 'numero'], 'idx_circonscriptions_dept_num');
        });

        Schema::table('deputy_votes', function (Blueprint $table) {
            // Index pour les requêtes fréquentes de votes
            $table->index(['deputy_id', 'position'], 'idx_deputy_votes_deputy_pos');
            $table->index(['vote_id', 'position'], 'idx_deputy_votes_vote_pos');
        });

        Schema::table('votes', function (Blueprint $table) {
            // Index pour les filtres et tri
            $table->index(['date_scrutin', 'resultat'], 'idx_votes_date_result');
            $table->index('type', 'idx_votes_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deputies', function (Blueprint $table) {
            $table->dropIndex('idx_deputies_dept_active');
            $table->dropIndex('idx_deputies_circ_active');
            $table->dropIndex('idx_deputies_group_active');
            $table->dropIndex('idx_deputies_slug');
        });

        Schema::table('circonscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_circonscriptions_dept');
            $table->dropIndex('idx_circonscriptions_dept_num');
        });

        Schema::table('deputy_votes', function (Blueprint $table) {
            $table->dropIndex('idx_deputy_votes_deputy_pos');
            $table->dropIndex('idx_deputy_votes_vote_pos');
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex('idx_votes_date_result');
            $table->dropIndex('idx_votes_type');
        });
    }
};
