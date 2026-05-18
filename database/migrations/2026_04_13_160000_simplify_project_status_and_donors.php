<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer les tables pivot (statut unique + bailleurs en JSON)
        Schema::dropIfExists('project_status_project');
        Schema::dropIfExists('project_donor_project');
        Schema::dropIfExists('project_donors');

        // Ajouter la colonne JSON pour les bailleurs
        Schema::table('projects', function (Blueprint $table) {
            $table->json('donors_json')->nullable()->after('funding_amount');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('donors_json');
        });

        Schema::create('project_donors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('project_donor_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_donor_id')->constrained('project_donors')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'project_donor_id']);
        });

        Schema::create('project_status_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_status_id')->constrained('project_statuses')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'project_status_id']);
        });
    }
};
