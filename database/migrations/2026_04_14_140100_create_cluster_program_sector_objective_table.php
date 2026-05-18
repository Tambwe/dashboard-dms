<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cluster_program_sector_objective', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_id');
            $table->unsignedBigInteger('program_sector_objective_id');
            $table->timestamps();

            $table->unique(['cluster_id', 'program_sector_objective_id'], 'cluster_sector_objective_unique');
            $table->foreign('cluster_id', 'cps_cluster_fk')
                ->references('id')
                ->on('clusters')
                ->cascadeOnDelete();
            $table->foreign('program_sector_objective_id', 'cps_sector_obj_fk')
                ->references('id')
                ->on('program_sector_objectives')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cluster_program_sector_objective');
    }
};
