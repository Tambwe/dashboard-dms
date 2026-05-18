<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('activity_name');
            $table->decimal('activity_cost', 15, 2)->nullable();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->nullOnDelete();
            $table->foreignId('territoire_id')->nullable()->constrained('territoires')->nullOnDelete();
            $table->foreignId('commune_id')->nullable()->constrained('communes')->nullOnDelete();
            $table->unsignedInteger('girls_0_17')->default(0);
            $table->unsignedInteger('girls_18_59')->default(0);
            $table->unsignedInteger('girls_60_plus')->default(0);
            $table->unsignedInteger('boys_0_17')->default(0);
            $table->unsignedInteger('boys_18_59')->default(0);
            $table->unsignedInteger('boys_60_plus')->default(0);
            $table->unsignedInteger('persons_with_disabilities')->default(0);
            $table->text('comment')->nullable();
            $table->date('reporting_date')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'reporting_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activities');
    }
};
