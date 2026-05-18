<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('code')->unique();
            $table->string('label');
            $table->string('unit')->nullable();
            $table->string('frequency')->nullable();
            $table->string('owner')->nullable();
            $table->string('verification_source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'code']);
        });

        Schema::create('program_activities', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('code')->unique();
            $table->string('label');
            $table->foreignId('program_indicator_id')->constrained('program_indicators')->cascadeOnDelete();
            $table->string('program_axis')->nullable();
            $table->string('project_lead')->nullable();
            $table->string('status')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->timestamps();

            $table->index(['program_indicator_id', 'status']);
        });

        Schema::create('program_sub_activities', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('code')->unique();
            $table->string('label');
            $table->foreignId('program_activity_id')->constrained('program_activities')->cascadeOnDelete();
            $table->string('site_name')->nullable();
            $table->string('province')->nullable();
            $table->string('territoire')->nullable();
            $table->string('health_zone')->nullable();
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['program_activity_id', 'status']);
            $table->index(['province', 'territoire', 'health_zone']);
        });

        Schema::create('program_activity_plans', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->unsignedSmallInteger('program_year');
            $table->string('quarter', 2)->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->foreignId('program_indicator_id')->nullable()->constrained('program_indicators')->nullOnDelete();
            $table->foreignId('program_activity_id')->nullable()->constrained('program_activities')->nullOnDelete();
            $table->foreignId('program_sub_activity_id')->nullable()->constrained('program_sub_activities')->nullOnDelete();
            $table->decimal('target_value', 14, 2)->default(0);
            $table->decimal('achieved_value', 14, 2)->default(0);
            $table->decimal('achievement_rate', 8, 4)->nullable();
            $table->decimal('planned_budget', 14, 2)->default(0);
            $table->decimal('executed_budget', 14, 2)->default(0);
            $table->decimal('budget_variance', 14, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['program_year', 'quarter', 'month']);
            $table->index(['program_indicator_id', 'program_activity_id', 'program_sub_activity_id'], 'program_plan_links_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_activity_plans');
        Schema::dropIfExists('program_sub_activities');
        Schema::dropIfExists('program_activities');
        Schema::dropIfExists('program_indicators');
    }
};
