<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->json('beneficiaries_by_status')
                ->nullable()
                ->after('statut_beneficiaire');
        });
    }

    public function down(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->dropColumn('beneficiaries_by_status');
        });
    }
};
