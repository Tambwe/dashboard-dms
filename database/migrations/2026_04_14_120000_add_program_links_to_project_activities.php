<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->foreignId('program_indicator_id')
                ->nullable()
                ->after('activity_name')
                ->constrained('program_indicators')
                ->nullOnDelete();

            $table->foreignId('program_activity_id')
                ->nullable()
                ->after('program_indicator_id')
                ->constrained('program_activities')
                ->nullOnDelete();

            $table->foreignId('program_sub_activity_id')
                ->nullable()
                ->after('program_activity_id')
                ->constrained('program_sub_activities')
                ->nullOnDelete();

            $table->index(['program_indicator_id', 'program_activity_id'], 'proj_act_program_indicator_activity_idx');
            $table->index('program_sub_activity_id', 'proj_act_program_sub_activity_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_activities', function (Blueprint $table) {
            $table->dropIndex('proj_act_program_indicator_activity_idx');
            $table->dropIndex('proj_act_program_sub_activity_idx');
            $table->dropConstrainedForeignId('program_sub_activity_id');
            $table->dropConstrainedForeignId('program_activity_id');
            $table->dropConstrainedForeignId('program_indicator_id');
        });
    }
};
