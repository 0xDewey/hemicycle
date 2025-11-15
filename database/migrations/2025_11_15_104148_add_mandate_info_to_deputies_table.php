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
            $table->date('mandate_start_date')->nullable()->after('groupe_politique');
            $table->date('mandate_end_date')->nullable()->after('mandate_start_date');
            $table->boolean('is_active')->default(true)->after('mandate_end_date');
            $table->string('cause_mandat')->nullable()->after('mandate_end_date');
            $table->string('ref_circonscription')->nullable()->after('cause_mandat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deputies', function (Blueprint $table) {
            $table->dropColumn(['mandate_start_date', 'mandate_end_date', 'is_active', 'cause_mandat', 'ref_circonscription']);
        });
    }
};
