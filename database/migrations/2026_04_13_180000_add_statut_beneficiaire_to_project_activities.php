<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->string('statut_beneficiaire', 50)->nullable()->after('commune_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->dropColumn('statut_beneficiaire');
        });
    }
};
