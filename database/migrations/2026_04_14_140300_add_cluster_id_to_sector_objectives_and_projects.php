<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('program_sector_objectives', 'cluster_id')) {
            Schema::table('program_sector_objectives', function (Blueprint $table) {
                $table->unsignedBigInteger('cluster_id')->nullable()->after('id');
                $table->foreign('cluster_id', 'pso_cluster_fk')
                    ->references('id')
                    ->on('clusters')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('projects', 'cluster_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('cluster_id')->nullable()->after('organisation_id');
                $table->foreign('cluster_id', 'project_cluster_fk')
                    ->references('id')
                    ->on('clusters')
                    ->nullOnDelete();
            });
        }

        Schema::dropIfExists('cluster_program_sector_objective');
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('project_cluster_fk');
            $table->dropColumn('cluster_id');
        });

        Schema::table('program_sector_objectives', function (Blueprint $table) {
            $table->dropForeign('pso_cluster_fk');
            $table->dropColumn('cluster_id');
        });

        // Recréer la pivot si on revient en arrière
        Schema::create('cluster_program_sector_objective', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_id');
            $table->unsignedBigInteger('program_sector_objective_id');
            $table->timestamps();
            $table->unique(['cluster_id', 'program_sector_objective_id'], 'cluster_sector_objective_unique');
            $table->foreign('cluster_id', 'cps_cluster_fk')->references('id')->on('clusters')->cascadeOnDelete();
            $table->foreign('program_sector_objective_id', 'cps_sector_obj_fk')->references('id')->on('program_sector_objectives')->cascadeOnDelete();
        });
    }
};
