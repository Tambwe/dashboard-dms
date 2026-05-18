<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_sector_objectives', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('program_strategic_objectives', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->foreignId('program_sector_objective_id')->constrained('program_sector_objectives')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['program_sector_objective_id', 'code'], 'prog_strat_obj_sector_code_idx');
        });

        Schema::table('program_indicators', function (Blueprint $table) {
            $table->foreignId('program_strategic_objective_id')
                ->nullable()
                ->after('label')
                ->constrained('program_strategic_objectives')
                ->nullOnDelete();

            $table->index(['program_strategic_objective_id', 'code'], 'program_indicators_objective_code_idx');
        });
    }

    public function down(): void
    {
        Schema::table('program_indicators', function (Blueprint $table) {
            $table->dropIndex('program_indicators_objective_code_idx');
            $table->dropConstrainedForeignId('program_strategic_objective_id');
        });

        Schema::dropIfExists('program_strategic_objectives');
        Schema::dropIfExists('program_sector_objectives');
    }
};